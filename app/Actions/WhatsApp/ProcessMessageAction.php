<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\UserFlowService;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsApp\CertificateService;

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
            $this->templateService,
            app()->make(\App\Services\WhatsApp\AuthService::class)  // Usar app() helper
        );
        
        $this->handleCertificateFlowAction = new HandleCertificateFlowAction(
            $this->messageService,
            $this->stateService,
            $this->templateService,
            app()->make(\App\Services\WhatsApp\CertificateService::class)  // Usar app() helper
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
            
            $this->stateService->updateState($userPhone, [
                'welcome_sent' => true,
                'welcome_sent_at' => time()
            ]);
        }

        $this->routeMessage($userPhone, $messageText, $sentTemplate);
    }

    private function routeMessage(string $userPhone, string $messageText, bool $suppressWelcome): void
    {
        // Normalizar mensaje
        $normalized = $this->userFlowService->normalizeMessage($messageText);
        
        $userState = $this->stateService->getState($userPhone);

        // ✅ NUEVO: Verificar si ya se envió plantilla en esta sesión
        $welcomeAlreadySent = isset($userState['welcome_sent']) && $userState['welcome_sent'] === true;
        
        // Si ya se envió plantilla, forzar suppressWelcome a true
        if ($welcomeAlreadySent) {
            Log::info("ℹ️ Plantilla ya enviada en esta sesión, suprimiendo bienvenida adicional");
            $suppressWelcome = true;
        }

        // Si está en flujos de autenticación
        if ($this->stateService->isInAuthFlow($userPhone)) {
            Log::info("Estado de autenticación detectado — manejando por flujo de auth");
            $this->handleAuthFlowAction->execute($userPhone, $normalized['raw'], $userState);
            return;
        }

        // Flujos de certificados
        if ($this->stateService->isInCertificateFlow($userPhone)) {
            Log::info("Estado activo detectado — manejando por flujo de certificado");
            $this->handleCertificateFlowAction->execute($userPhone, $normalized['lower'], $userState);
            return;
        }

        // Comandos globales / menú
        $command = $this->userFlowService->detectCommand($normalized);
        
        if ($command === 'menu') {
            Log::info("🤖 Comando MENU/HOLA recibido - suppressWelcome={$suppressWelcome}");
            
            if (!$suppressWelcome) {
                $this->messageService->sendText($userPhone, $this->templateService->getMenu());
            } else {
                $this->messageService->sendText($userPhone, $this->templateService->getMenu(true));
            }
            
            $this->stateService->updateState($userPhone, ['step' => 'main_menu']);
            return;
        }

        if ($command === 'generar_certificado') {
            Log::info("🤖 Usuario solicitó iniciar flujo de Generar Certificado - suppressWelcome={$suppressWelcome}");
            
            // ✅ NUEVO: Solo mostrar mini-welcome si NO se ha enviado plantilla
            if (!$suppressWelcome) {
                Log::info("📝 Mostrando mini-bienvenida para nuevo usuario");
                $this->messageService->sendText($userPhone, 
                    "👋 ¡Hola! Bienvenido al sistema de certificados FIC del SENA.\n\n" .
                    "Vamos a comenzar con la validación de tu usuario para generar el certificado."
                );
            }
            
            $this->handleAuthFlowAction->startAuthentication($userPhone);
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

        if (str_contains(strtolower($messageText), 'consultar') || str_contains(strtolower($messageText), 'certificados')) {
            Log::info("🔍 Usuario quiere consultar certificados generados");
            
            // Crear instancia del action de consulta
            $consultaAction = new HandleConsultaCertificadosAction(
                $this->messageService,
                $this->stateService,
                $this->templateService,
                new CertificateService()
            );
            
            $consultaAction->execute($userPhone, $messageText, $userState);
            return;
        }

        // Si no se reconoce
        Log::info("❓ No se reconoció comando global, enviando ayuda corta");
        $this->messageService->sendText($userPhone, $this->templateService->getUnknownCommand());
    }
}