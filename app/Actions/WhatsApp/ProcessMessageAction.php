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
            app()->make(\App\Services\WhatsApp\AuthService::class)  // Usar app() helper
        );
        
        $this->handleCertificateFlowAction = new HandleCertificateFlowAction(
            $this->messageService,
            $this->stateService,
            $this->templateService,
            app()->make(\App\Services\WhatsApp\CertificateService::class)  // Usar app() helper
        );
        
        $this->handleConsultaCertificadosAction = new HandleConsultaCertificadosAction(
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
        }

        $this->routeMessage($userPhone, $messageText, $sentTemplate);
    }

    private function routeMessage(string $userPhone, string $messageText, bool $suppressWelcome): void
    {
        // Normalizar mensaje
        $normalized = $this->userFlowService->normalizeMessage($messageText);
        
        $userState = $this->stateService->getState($userPhone);

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

        // Flujos de consulta de certificados
        if ($this->stateService->isInConsultaCertificadosFlow($userPhone)) {
            Log::info("Estado de consulta de certificados detectado");
            $this->handleConsultaCertificadosAction->execute($userPhone, $normalized['lower'], $userState);
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
            Log::info("🤖 Usuario solicitó iniciar flujo de Generar Certificado");
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

        if ($command === 'consultar_certificados') {
            Log::info("🔍 Usuario quiere consultar certificados generados");
            
            // Verificar si el usuario está autenticado primero
            if (!$userState || !isset($userState['authenticated']) || !$userState['authenticated']) {
                Log::info("🔒 Usuario no autenticado, redirigiendo a autenticación");
                $this->messageService->sendText($userPhone,
                    "🔒 *Consulta de Certificados*\n\n" .
                    "Para consultar tus certificados, primero debes autenticarte.\n\n" .
                    "Escribe *MENU* y selecciona 'Generar Certificado' para autenticarte."
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
                    "Por favor, autentícate nuevamente escribiendo *MENU*."
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
}