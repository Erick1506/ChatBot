<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\AuthService;
use Illuminate\Support\Facades\Log;

class HandleAuthFlowAction
{
    private AuthService $authService;
    
    public function __construct(
        private MessageService $messageService,
        private StateService $stateService,
        private TemplateService $templateService
    ) {
        // Crear AuthService manualmente
        $this->authService = new AuthService();
    }

    public function startAuthentication(string $userPhone): void
    {
        Log::info("🔐 Iniciando autenticación para usuario: {$userPhone}");
        $this->messageService->sendText($userPhone, $this->templateService->getAuthPrompt());
        $this->stateService->updateState($userPhone, [
            'step' => 'auth_username',
            'authenticated' => false
        ]);
    }

    public function execute(string $userPhone, string $messageText, array $userState): void
    {
        Log::info("=== HANDLE AUTH FLOW INICIADO ===");
        Log::info("Paso actual: " . ($userState['step'] ?? 'none'));
        Log::info("Mensaje: {$messageText}");

        $step = $userState['step'] ?? '';

        switch ($step) {
            case 'auth_username':
                Log::info("👤 Usuario ingresando username: {$messageText}");
                $this->processUsername($userPhone, $messageText, $userState);
                break;

            case 'auth_password':
                Log::info("🔐 Usuario ingresando password");
                $this->processPassword($userPhone, $messageText, $userState);
                break;

            default:
                Log::info("🔀 Estado de auth no reconocido, reiniciando");
                $this->startAuthentication($userPhone);
                break;
        }
    }

    private function processUsername(string $userPhone, string $username, array $userState): void
    {
        // Si el usuario escribe "atras" o "menu", volver al menú principal
        $lowerUsername = strtolower(trim($username));
        if (in_array($lowerUsername, ['atras', 'menu', 'cancelar', 'volver'])) {
            Log::info("🔙 Usuario cancelando autenticación");
            $this->messageService->sendText($userPhone, 
                "❌ Autenticación cancelada.\n\n" .
                "Escribe *MENU* para ver las opciones."
            );
            $this->stateService->clearState($userPhone);
            return;
        }

        $empresa = $this->authService->validateUsername($username);

        if (!$empresa) {
            $this->messageService->sendText($userPhone, $this->templateService->getUserNotFound());
            $this->stateService->clearState($userPhone);
            return;
        }

        Log::info("✅ Usuario encontrado: " . $empresa->representante_legal);

        $message = "✅ *Usuario encontrado*\n\n";
        $message .= "👤 *" . $empresa->representante_legal . "*\n";
        $message .= "🏢 NIT: *" . $empresa->nit . "*\n\n";
        $message .= "Ahora ingresa tu *CONTRASEÑA*:";

        $this->messageService->sendText($userPhone, $message);
        
        // Guardar también la acción solicitada si existe
        $requestedAction = $userState['requested_action'] ?? null;
        
        $this->stateService->updateState($userPhone, [
            'step' => 'auth_password',
            'auth_username' => $username,
            'empresa_id' => $empresa->id,
            'empresa_nit' => $empresa->nit,
            'representante_legal' => $empresa->representante_legal,
            'requested_action' => $requestedAction // Mantener la acción solicitada
        ]);
    }

    private function processPassword(string $userPhone, string $password, array $userState): void
    {
        $username = $userState['auth_username'] ?? null;

        // Si el usuario escribe "atras" o "menu", volver a pedir usuario
        $lowerPassword = strtolower(trim($password));
        if (in_array($lowerPassword, ['atras', 'menu', 'cancelar', 'volver'])) {
            Log::info("🔙 Usuario volviendo a ingresar usuario");
            $this->messageService->sendText($userPhone, 
                "Por favor, ingresa tu *USUARIO* nuevamente:"
            );
            $this->stateService->updateState($userPhone, [
                'step' => 'auth_username',
                'auth_username' => null,
                'requested_action' => $userState['requested_action'] ?? null
            ]);
            return;
        }

        if (!$username) {
            Log::error("❌ No se encontró username en el estado");
            $this->messageService->sendText($userPhone, $this->templateService->getErrorSystem());
            $this->stateService->clearState($userPhone);
            return;
        }

        $empresa = $this->authService->validateUsername($username);

        if (!$empresa) {
            Log::error("❌ Empresa no encontrada para usuario: {$username}");
            $this->messageService->sendText($userPhone, $this->templateService->getErrorSystem());
            $this->stateService->clearState($userPhone);
            return;
        }

        if (!$this->authService->validatePassword($empresa, $password)) {
            $this->messageService->sendText($userPhone, $this->templateService->getWrongPassword());
            // Volver a pedir contraseña
            $this->stateService->updateState($userPhone, [
                'step' => 'auth_password',
                'auth_username' => $username,
                'empresa_id' => $empresa->id,
                'empresa_nit' => $empresa->nit,
                'representante_legal' => $empresa->representante_legal,
                'requested_action' => $userState['requested_action'] ?? null
            ]);
            return;
        }

        Log::info("✅ Autenticación exitosa para: " . $empresa->representante_legal);

        // Obtener acción solicitada si existe
        $requestedAction = $userState['requested_action'] ?? null;
        
        // Enviar mensaje de éxito de autenticación
        $this->messageService->sendText($userPhone, 
            $this->templateService->getAuthSuccess($empresa->representante_legal, $empresa->nit)
        );

        if ($requestedAction === 'generar_certificado') {
            Log::info("🔄 Redirigiendo a generación de certificado después de autenticación");
            
            // Redirigir al flujo de certificados
            $this->stateService->updateState($userPhone, [
                'step' => 'choosing_certificate_type',
                'authenticated' => true,
                'empresa_nit' => $empresa->nit,
                'representante_legal' => $empresa->representante_legal,
                'requested_action' => null // Limpiar la acción solicitada
            ]);
            
            // Mostrar opciones de certificados
            $this->messageService->sendText($userPhone, 
                "📄 *GENERAR CERTIFICADO FIC*\n\n" .
                "Por favor indica el *tipo* de certificado escribiendo su nombre o número:\n\n" .
                "• *TICKET* - Certificado específico por número de ticket\n" .
                "• *NIT* - Todos los certificados asociados a tu NIT\n" .
                "• *VIGENCIA* - Certificado filtrado por año de vigencia\n\n" .
                "Ejemplo: responde *NIT* para buscar todos tus certificados."
            );
            
        } elseif ($requestedAction === 'consultar_certificados') {
            Log::info("🔍 Redirigiendo a consulta de certificados después de autenticación");
            
            // Actualizar estado para consulta
            $this->stateService->updateState($userPhone, [
                'step' => 'consulting_certificates',
                'authenticated' => true,
                'empresa_nit' => $empresa->nit,
                'representante_legal' => $empresa->representante_legal,
                'requested_action' => null,
                'consulta_page' => 1
            ]);
            
            // Mostrar información de consulta
            $this->messageService->sendText($userPhone,
                "🔍 *CONSULTAR CERTIFICADOS*\n\n" .
                "Ahora puedes consultar y descargar certificados que ya has generado.\n\n" .
                "Buscando tus certificados generados..."
            );
            
            // Aquí podrías llamar al HandleConsultaCertificadosAction
            // O simplemente mostrar un mensaje y dejar que el usuario envíe "consultar" de nuevo
            $this->messageService->sendText($userPhone,
                "Por favor, escribe *CONSULTAR* nuevamente para ver tus certificados."
            );
            
        } else {
            // Si no hay acción específica, mostrar menú con opciones para autenticados
            $this->stateService->updateState($userPhone, [
                'step' => 'main_menu',
                'authenticated' => true,
                'empresa_nit' => $empresa->nit,
                'representante_legal' => $empresa->representante_legal
            ]);
            
            // Mostrar menú especial para autenticados
            $this->messageService->sendText($userPhone,
                "👋 ¡Hola *{$empresa->representante_legal}*! (NIT: *{$empresa->nit}*)\n\n" .
                "✅ *Ya estás autenticado*\n\n" .
                "Ahora puedes usar todas las funciones:\n\n" .
                "• Escribe *1* o *GENERAR CERTIFICADO* para crear un nuevo certificado\n" .
                "• Escribe *2* o *CONSULTAR CERTIFICADOS* para ver tus certificados\n" .
                "• Escribe *3* o *REQUISITOS* para ver los requisitos\n" .
                "• Escribe *4* o *SOPORTE* para contactar soporte\n" .
                "• Escribe *CERRAR SESION* para salir\n" .
                "• Escribe *MENU* para ver todas las opciones"
            );
        }
    }

    /**
     * Método para cerrar sesión
     */
    public function logout(string $userPhone): void
    {
        $userState = $this->stateService->getState($userPhone);
        $isAuthenticated = $userState['authenticated'] ?? false;
        
        if ($isAuthenticated) {
            $userName = $userState['representante_legal'] ?? $userState['auth_username'] ?? 'Usuario';
            
            Log::info("🚪 Usuario cerrando sesión: {$userPhone}");
            
            $this->messageService->sendText($userPhone,
                "✅ *SESIÓN CERRADA*\n\n" .
                "Adiós *{$userName}*. Has cerrado sesión exitosamente.\n\n" .
                "Para usar las funciones de certificados, deberás autenticarte nuevamente."
            );
            
            // Limpiar estado completamente
            $this->stateService->clearState($userPhone);
            
            // Mostrar menú no autenticado
            $this->messageService->sendText($userPhone, 
                "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\n" .
                "¡Bienvenido! Selecciona una opción:\n\n" .
                "• *1* - Generar Certificado\n" .
                "• *2* - Consultar Certificados\n" .
                "• *3* - Requisitos\n" .
                "• *4* - Soporte\n" .
                "🔐 *5* - Autenticarse\n" .
                "• *6* - Registro\n\n" .
                "🔒 *Nota:* Las opciones 1 y 2 requieren autenticación.\n" .
                "Usa la opción *5* para autenticarte primero.\n\n" .
                "Escribe el número o nombre de la opción."
            );
        } else {
            $this->messageService->sendText($userPhone,
                "ℹ️ *No estás autenticado*\n\n" .
                "Para cerrar sesión primero necesitas iniciar sesión.\n\n" .
                "Escribe *5* o *AUTENTICAR* para iniciar sesión."
            );
        }
    }

    /**
     * Método para verificar si el usuario está autenticado
     */
    public function isAuthenticated(string $userPhone): bool
    {
        $userState = $this->stateService->getState($userPhone);
        return $userState['authenticated'] ?? false;
    }

    /**
     * Método para obtener información del usuario autenticado
     */
    public function getAuthenticatedUser(string $userPhone): ?array
    {
        $userState = $this->stateService->getState($userPhone);
        
        if (!($userState['authenticated'] ?? false)) {
            return null;
        }

        return [
            'username' => $userState['auth_username'] ?? null,
            'empresa_nit' => $userState['empresa_nit'] ?? null,
            'representante_legal' => $userState['representante_legal'] ?? null,
            'empresa_id' => $userState['empresa_id'] ?? null
        ];
    }
}