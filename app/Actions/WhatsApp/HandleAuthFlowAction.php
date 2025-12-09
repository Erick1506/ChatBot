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

        $this->messageService->sendText($userPhone, 
            "✅ *Usuario encontrado*\n\n" .
            "👤 *" . $empresa->representante_legal . "*\n" .
            "🏢 NIT: *" . $empresa->nit . "*\n\n" .
            "Ahora ingresa tu *CONTRASEÑA*:"
        );
        
        $requestedAction = $userState['requested_action'] ?? null;
        
        $this->stateService->updateState($userPhone, [
            'step' => 'auth_password',
            'auth_username' => $username,
            'empresa_id' => $empresa->id,
            'empresa_nit' => $empresa->nit,
            'representante_legal' => $empresa->representante_legal,
            'requested_action' => $requestedAction
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
            
            $this->stateService->updateState($userPhone, [
                'step' => 'choosing_certificate_type',
                'authenticated' => true,
                'empresa_nit' => $empresa->nit,
                'representante_legal' => $empresa->representante_legal,
                'requested_action' => null
            ]);
            
            $this->messageService->sendText($userPhone, $this->templateService->getCertificateOptions());
            
        } else {
            // Mostrar menú de autenticado
            $this->stateService->updateState($userPhone, [
                'step' => 'main_menu',
                'authenticated' => true,
                'empresa_nit' => $empresa->nit,
                'representante_legal' => $empresa->representante_legal
            ]);
            
            $this->messageService->sendText($userPhone,
                $this->templateService->getAuthenticatedMenu($empresa->representante_legal, $empresa->nit)
            );
        }
    }

    public function logout(string $userPhone): void
    {
        $userState = $this->stateService->getState($userPhone);
        $isAuthenticated = $userState['authenticated'] ?? false;
        
        if ($isAuthenticated) {
            $userName = $userState['representante_legal'] ?? $userState['auth_username'] ?? 'Usuario';
            
            Log::info("🚪 Usuario cerrando sesión: {$userPhone}");
            
            // Usar el método del TemplateService
            $this->messageService->sendText($userPhone,
                $this->templateService->getLogoutMessage($userName)
            );
            
            // Limpiar estado completamente
            $this->stateService->clearState($userPhone);
            
            // Mostrar menú principal
            $this->messageService->sendText($userPhone, $this->templateService->getMenu());
            
        } else {
            $this->messageService->sendText($userPhone,
                $this->templateService->getNoAuthenticationMessage()
            );
        }
    }
}