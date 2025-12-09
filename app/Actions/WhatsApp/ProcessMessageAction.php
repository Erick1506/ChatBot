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
            $this->templateService
        );
        
        $this->handleCertificateFlowAction = new HandleCertificateFlowAction(
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
        $currentStep = $userState['step'] ?? '';

        Log::info("📱 Estado: " . ($isAuthenticated ? "Autenticado" : "No autenticado") . ", Paso: {$currentStep}");

        // ========== VERIFICACIÓN DE FLUJOS ACTIVOS ==========

        // 1. Flujo de autenticación
        $authSteps = ['auth_username', 'auth_password'];
        if (in_array($currentStep, $authSteps)) {
            Log::info("🔐 Flujo de autenticación detectado");
            $this->handleAuthFlowAction->execute($userPhone, $normalized['raw'], $userState);
            return;
        }

        // 2. Flujos de certificados (solo si autenticado)
        $certificateSteps = [
            'choosing_certificate_type', 'awaiting_ticket', 'awaiting_year', 
            'consulting_certificates', 'confirm_download'
        ];
        
        if (!$isAuthenticated && in_array($currentStep, $certificateSteps)) {
            Log::warning("⚠️ Usuario no autenticado en estado de certificado");
            $this->stateService->clearState($userPhone);
            $this->messageService->sendText($userPhone, 
                "🔒 *Sesión expirada*\n\n" . $this->templateService->getMenu()
            );
            return;
        }

        // 3. Si está autenticado y en flujo de certificado
        if ($isAuthenticated && in_array($currentStep, $certificateSteps)) {
            Log::info("📄 Flujo de certificado detectado");
            $this->handleCertificateFlowAction->execute($userPhone, $normalized['lower'], $userState);
            return;
        }

        // ========== COMANDOS PRINCIPALES ==========
        $command = $this->userFlowService->detectCommand($normalized);
        Log::info("🔍 Comando: " . ($command ?? "Ninguno"));

        // COMANDO: MENU
        if ($command === 'menu') {
            $this->handleMenuCommand($userPhone, $isAuthenticated, $userState, $suppressWelcome);
            return;
        }

        // COMANDO: GENERAR CERTIFICADO
        if ($command === 'generar_certificado') {
            $this->handleGenerarCertificado($userPhone, $isAuthenticated, $userState);
            return;
        }

        // COMANDO: CONSULTAR CERTIFICADOS
        if ($command === 'consultar_certificados') {
            $this->handleConsultarCertificados($userPhone, $isAuthenticated, $userState);
            return;
        }

        // COMANDO: AUTENTICAR
        if ($command === 'autenticar') {
            $this->handleAutenticar($userPhone, $isAuthenticated, $userState);
            return;
        }

        // COMANDO: CERRAR SESIÓN
        if ($command === 'cerrar_sesion') {
            $this->handleAuthFlowAction->logout($userPhone);
            return;
        }

        // COMANDOS SIMPLES
        $simpleCommands = [
            'requisitos' => 'getRequirements',
            'soporte' => 'getSupportInfo',
            'registro' => 'getRegistrationInfo'
        ];
        
        if (isset($simpleCommands[$command])) {
            $method = $simpleCommands[$command];
            $this->messageService->sendText($userPhone, $this->templateService->$method());
            return;
        }

        // ========== COMANDO NO RECONOCIDO ==========
        $this->handleUnknownCommand($userPhone, $currentStep);
    }

    // Métodos helper para simplificar
    private function handleMenuCommand(string $userPhone, bool $isAuthenticated, array $userState, bool $suppressWelcome): void
    {
        if ($isAuthenticated) {
            $userName = $userState['representante_legal'] ?? 'Usuario';
            $nit = $userState['empresa_nit'] ?? 'N/A';
            $this->messageService->sendText($userPhone,
                "👋 ¡Hola *{$userName}*! (NIT: *{$nit}*)\n\n" .
                "Selecciona una opción:\n\n" .
                "• *Generar Certificado*\n" .
                "• *Consultar Certificados*\n" .
                "• *Requisitos*\n" .
                "• *Soporte*\n" .
                "• *Cerrar Sesión*\n" .
                "• *Registro*\n\n" .
                "Escribe el nombre de la opción."
            );
        } else {
            $this->messageService->sendText($userPhone, $this->templateService->getMenu(!$suppressWelcome));
        }
        
        $this->stateService->updateState($userPhone, ['step' => 'main_menu']);
    }

    private function handleGenerarCertificado(string $userPhone, bool $isAuthenticated, array $userState): void
    {
        if (!$isAuthenticated) {
            $this->messageService->sendText($userPhone, $this->templateService->getAuthPrompt());
            $this->stateService->updateState($userPhone, [
                'step' => 'auth_username',
                'authenticated' => false,
                'requested_action' => 'generar_certificado'
            ]);
            return;
        }
        
        $this->stateService->updateState($userPhone, [
            'step' => 'choosing_certificate_type',
            'authenticated' => true,
            'empresa_nit' => $userState['empresa_nit'] ?? null
        ]);
        
        $this->messageService->sendText($userPhone, $this->templateService->getCertificateOptions());
    }

    private function handleConsultarCertificados(string $userPhone, bool $isAuthenticated, array $userState): void
    {
        if (!$isAuthenticated) {
            $this->messageService->sendText($userPhone, $this->templateService->getAuthPrompt());
            $this->stateService->updateState($userPhone, [
                'step' => 'auth_username',
                'authenticated' => false,
                'requested_action' => 'consultar_certificados'
            ]);
            return;
        }
        
        $this->stateService->updateState($userPhone, [
            'step' => 'consulting_certificates',
            'consulta_page' => 1
        ]);
        
        $this->handleCertificateFlowAction->execute($userPhone, 'consultar', $userState);
    }

    private function handleAutenticar(string $userPhone, bool $isAuthenticated, array $userState): void
    {
        if ($isAuthenticated) {
            $userName = $userState['representante_legal'] ?? 'Usuario';
            $nit = $userState['empresa_nit'] ?? 'N/A';
            $this->messageService->sendText($userPhone,
                "✅ *Ya estás autenticado*\n\n" .
                "Hola *{$userName}* (NIT: *{$nit}*)\n\n" .
                "Escribe *MENU* para ver las opciones."
            );
        } else {
            $this->handleAuthFlowAction->startAuthentication($userPhone);
        }
    }

    private function handleUnknownCommand(string $userPhone, string $currentStep): void
    {
        if (!empty($currentStep)) {
            $this->messageService->sendText($userPhone,
                "🤔 *No entendí*\n\n" .
                "Escribe *MENU* para ver las opciones."
            );
        } else {
            $this->messageService->sendText($userPhone, $this->templateService->getUnknownCommand());
        }
    }
}