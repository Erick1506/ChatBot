<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\UserFlowService;
use Illuminate\Support\Facades\Log;

class ProcessMessageAction
{
    private HandleAuthFlowAction $handleAuthFlowAction;
    private HandleCertificateFlowAction $handleCertificateFlowAction;
    private HandleConsultaCertificadosAction $handleConsultaCertificadosAction;
    
    public function __construct(
        private MessageService $messageService,
        private StateService $stateService,
        private TemplateService $templateService,
        private UserFlowService $userFlowService
    ) {
        // Crear manualmente los Actions dependientes usando app()
        $this->handleAuthFlowAction = new HandleAuthFlowAction(
            $this->messageService,
            $this->stateService,
            $this->templateService,
            app()->make(\App\Services\WhatsApp\AuthService::class)
        );
        
        $this->handleCertificateFlowAction = new HandleCertificateFlowAction(
            $this->messageService,
            $this->stateService,
            $this->templateService,
            app()->make(\App\Services\WhatsApp\CertificateService::class)
        );
        
        $this->handleConsultaCertificadosAction = new HandleConsultaCertificadosAction(
            $this->messageService,
            $this->stateService,
            $this->templateService,
            app()->make(\App\Services\WhatsApp\CertificateService::class)
        );
    }

    public function execute(array $messageData): void
    {
        $userPhone = $messageData['userPhone'];
        $messageText = $messageData['messageText'];

        Log::info("=== PROCESS MESSAGE INICIADO ===");
        Log::info("Procesando mensaje - Usuario: {$userPhone}, Texto: {$messageText}");

        // Ignorar números de prueba
        if ($this->userFlowService->isTestNumber($userPhone)) {
            Log::info("🔧 Ignorando mensaje de prueba de Meta: {$userPhone}");
            return;
        }

        // Determinar si enviar plantilla
        $needTemplate = $this->userFlowService->shouldSendWelcomeTemplate($userPhone);
        $sentTemplate = false;
        if ($needTemplate) {
            Log::info("🔔 Enviando plantilla welcome_short a {$userPhone}");
            $sentTemplate = $this->messageService->sendTemplate($userPhone, 'welcome_short');
        }

        $this->routeMessage($userPhone, $messageText, $sentTemplate);
    }

    private function routeMessage(string $userPhone, string $messageText, bool $suppressWelcome): void
    {
        // Normalizar mensaje
        $normalized = $this->userFlowService->normalizeMessage($messageText);
        
        $userState = $this->stateService->getState($userPhone);
        $isAuthenticated = $userState['authenticated'] ?? false;

        // Si está en flujos de autenticación
        if ($this->stateService->isInAuthFlow($userPhone)) {
            Log::info("Estado de autenticación detectado — manejando por flujo de auth");
            $this->handleAuthFlowAction->execute($userPhone, $normalized['raw'], $userState);
            return;
        }

        // Flujos de certificados - SOLO si está autenticado
        if ($isAuthenticated && $this->stateService->isInCertificateFlow($userPhone)) {
            Log::info("Estado activo detectado — manejando por flujo de certificado");
            $this->handleCertificateFlowAction->execute($userPhone, $normalized['lower'], $userState);
            return;
        }

        // Flujos de consulta de certificados - SOLO si está autenticado
        if ($isAuthenticated && $this->stateService->isInConsultaCertificadosFlow($userPhone)) {
            Log::info("Estado de consulta de certificados detectado");
            $this->handleConsultaCertificadosAction->execute($userPhone, $normalized['lower'], $userState);
            return;
        }

        // Comandos globales / menú
        $command = $this->userFlowService->detectCommand($normalized);
        
        if ($command === 'menu') {
            Log::info("🤖 Comando MENU/HOLA recibido - suppressWelcome={$suppressWelcome}");
            
            // Mostrar menú basado en estado de autenticación
            $menuText = $this->getMenuBasedOnAuth($userState);
            
            if (!$suppressWelcome) {
                $this->messageService->sendText($userPhone, $menuText);
            } else {
                $this->messageService->sendText($userPhone, $this->templateService->getMenu(true));
            }
            
            // Actualizar estado sin entrar en flujo de certificados
            $this->stateService->updateState($userPhone, [
                'step' => 'main_menu',
                'authenticated' => $isAuthenticated
            ]);
            return;
        }

        if ($command === 'generar_certificado') {
            Log::info("🤖 Usuario solicitó iniciar flujo de Generar Certificado");
            
            // Verificar autenticación primero
            if ($isAuthenticated) {
                // Si ya está autenticado, ir directamente al flujo de certificados
                $this->stateService->updateState($userPhone, [
                    'step' => 'choosing_certificate_type',
                    'authenticated' => true,
                    'empresa_nit' => $userState['empresa_nit'] ?? null
                ]);
                
                // Mostrar opciones de certificado
                $this->messageService->sendText($userPhone, $this->templateService->getCertificateOptions());
            } else {
                // Si no está autenticado, iniciar autenticación
                $this->handleAuthFlowAction->startAuthentication($userPhone);
            }
            return;
        }

        if ($command === 'autenticar' || $command === '5') {
            Log::info("🔐 Usuario solicitó autenticarse");
            
            if ($isAuthenticated) {
                // Si ya está autenticado, ofrecer cerrar sesión
                $this->messageService->sendText($userPhone,
                    "🔓 *Ya estás autenticado*\n\n" .
                    "Si deseas cerrar sesión, escribe *CERRAR SESION*.\n\n" .
                    "O escribe *MENU* para ver las opciones."
                );
            } else {
                $this->handleAuthFlowAction->startAuthentication($userPhone);
            }
            return;
        }

        if ($command === 'cerrar_sesion' || $command === 'logout') {
            $this->handleLogout($userPhone, $userState);
            return;
        }

        if ($command === 'requisitos') {
            Log::info("🤖 Usuario solicitó Requisitos");
            $this->messageService->sendText($userPhone, $this->templateService->getRequirements());
            return;
        }

        if ($command === 'soporte') {
            Log::info("🤖 Usuario solicitó Soporte");
            $this->messageService->sendText($userPhone, $this->templateService->getSupportInfo());
            return;
        }

        if ($command === 'registro') {
            Log::info("🤖 Usuario solicitó información de registro");
            $this->messageService->sendText($userPhone, $this->templateService->getRegistrationInfo());
            return;
        }

        if ($command === 'consultar_certificados') {
            Log::info("🔍 Usuario quiere consultar certificados generados");
            
            // Verificar si el usuario está autenticado primero
            if (!$isAuthenticated) {
                Log::info("🔒 Usuario no autenticado, redirigiendo a autenticación");
                $this->messageService->sendText($userPhone,
                    "🔒 *Consulta de Certificados*\n\n" .
                    "Para consultar tus certificados, primero debes autenticarte.\n\n" .
                    "Escribe *AUTENTICAR* para iniciar sesión o *MENU* para ver las opciones."
                );
                return;
            }
            
            // Verificar que tenga NIT
            $nit = $userState['empresa_nit'] ?? null;
            if (!$nit) {
                Log::warning("❌ Usuario autenticado pero sin NIT para consultar certificados");
                $this->messageService->sendText($userPhone,
                    "❌ *Error en la consulta*\n\n" .
                    "No se encontró información de empresa en tu perfil.\n" .
                    "Por favor, autentícate nuevamente escribiendo *AUTENTICAR*."
                );
                return;
            }
            
            // Iniciar flujo de consulta
            $this->handleConsultaCertificadosAction->execute($userPhone, 'consultar', $userState);
            return;
        }

        // Si no se reconoce
        Log::info("❓ No se reconoció comando global, enviando ayuda corta");
        $this->messageService->sendText($userPhone, $this->templateService->getUnknownCommand());
    }

    private function getMenuBasedOnAuth(array $userState): string
    {
        $isAuthenticated = $userState['authenticated'] ?? false;
        
        if ($isAuthenticated) {
            $userName = $userState['representante_legal'] ?? $userState['nombre_contacto'] ?? 'Usuario';
            $nit = $userState['empresa_nit'] ?? 'N/A';
            
            return "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\n" .
                   "👋 ¡Hola *{$userName}*!\n" .
                   "🏢 NIT: *{$nit}*\n\n" .
                   "Selecciona una opción:\n\n" .
                   "✅ *1* - Generar Certificado\n" .
                   "✅ *2* - Consultar Certificados\n" .
                   "• *3* - Requisitos\n" .
                   "• *4* - Soporte\n" .
                   "🔓 *5* - Cerrar Sesión\n" .
                   "• *6* - Registro\n\n" .
                   "Escribe el número o nombre de la opción.";
        } else {
            return $this->templateService->getMenu();
        }
    }

    private function handleLogout(string $userPhone, array $userState): void
    {
        $isAuthenticated = $userState['authenticated'] ?? false;
        
        if ($isAuthenticated) {
            $userName = $userState['representante_legal'] ?? $userState['nombre_contacto'] ?? 'Usuario';
            
            Log::info("🚪 Usuario cerrando sesión: {$userPhone}");
            
            $this->messageService->sendText($userPhone,
                "✅ *SESIÓN CERRADA*\n\n" .
                "Adiós *{$userName}*. Has cerrado sesión exitosamente.\n\n" .
                "Para usar las funciones de certificados, deberás autenticarte nuevamente."
            );
            
            // Limpiar estado pero mantener algunos datos básicos
            $this->stateService->clearState($userPhone);
            
            // Mostrar menú no autenticado
            $this->messageService->sendText($userPhone, $this->templateService->getMenu());
        } else {
            $this->messageService->sendText($userPhone,
                "ℹ️ *No estás autenticado*\n\n" .
                "Para cerrar sesión primero necesitas iniciar sesión.\n\n" .
                "Escribe *AUTENTICAR* para iniciar sesión."
            );
        }
    }
}