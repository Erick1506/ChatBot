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
            $this->templateService
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
        $currentStep = $userState['step'] ?? '';

        Log::info("📱 Estado usuario: " . ($isAuthenticated ? "Autenticado" : "No autenticado"));
        Log::info("📱 Paso actual: {$currentStep}");

        // ========== VERIFICACIÓN DE FLUJOS ACTIVOS ==========

        // 1. Verificar si está en flujo de autenticación
        $authSteps = ['auth_username', 'awaiting_username', 'auth_password', 'awaiting_password'];
        if (in_array($currentStep, $authSteps)) {
            Log::info("🔐 Estado de autenticación detectado ({$currentStep}) — manejando por flujo de auth");
            $this->handleAuthFlowAction->execute($userPhone, $normalized['raw'], $userState);
            return;
        }

        // 2. IMPORTANTE: Si NO está autenticado, NO puede estar en flujos de certificado
        // Limpiar cualquier estado de certificado si no está autenticado
        $certificateSteps = [
            'choosing_certificate_type', 'awaiting_ticket', 'awaiting_year', 
            'consulting_certificates', 'selecting_certificate', 'confirm_download'
        ];
        
        if (!$isAuthenticated && in_array($currentStep, $certificateSteps)) {
            Log::warning("⚠️ Usuario no autenticado en estado de certificado: {$currentStep}. Limpiando estado.");
            $this->stateService->clearState($userPhone);
            $this->messageService->sendText($userPhone, 
                "🔒 *Sesión expirada*\n\n" .
                "Tu sesión ha expirado o no estás autenticado.\n\n" .
                $this->templateService->getMenu()
            );
            return;
        }

        // 3. Flujos de certificados - SOLO si está autenticado
        if ($isAuthenticated && $this->stateService->isInCertificateFlow($userPhone)) {
            Log::info("Estado activo detectado — manejando por flujo de certificado");
            $this->handleCertificateFlowAction->execute($userPhone, $normalized['lower'], $userState);
            return;
        }

        // 4. Flujos de consulta de certificados - SOLO si está autenticado
        if ($isAuthenticated && $this->stateService->isInConsultaCertificadosFlow($userPhone)) {
            Log::info("Estado de consulta de certificados detectado");
            $this->handleConsultaCertificadosAction->execute($userPhone, $normalized['lower'], $userState);
            return;
        }

        // ========== COMANDOS GLOBALES / MENÚ ==========
        $command = $this->userFlowService->detectCommand($normalized);
        Log::info("🔍 Comando detectado: " . ($command ?? "Ninguno"));

        // COMANDO: MENU
        if ($command === 'menu') {
            Log::info("🤖 Comando MENU/HOLA recibido - suppressWelcome={$suppressWelcome}");
            
            if ($isAuthenticated) {
                // Menú para usuarios autenticados
                $userName = $userState['representante_legal'] ?? $userState['nombre_contacto'] ?? 'Usuario';
                $nit = $userState['empresa_nit'] ?? 'N/A';
                
                $this->messageService->sendText($userPhone,
                    $this->templateService->getAuthenticatedMenu($userName, $nit)
                );
            } else {
                // Menú para usuarios NO autenticados
                if (!$suppressWelcome) {
                    $this->messageService->sendText($userPhone, $this->templateService->getMenu());
                } else {
                    $this->messageService->sendText($userPhone, $this->templateService->getMenu(true));
                }
            }
            
            $this->stateService->updateState($userPhone, ['step' => 'main_menu']);
            return;
        }

        // COMANDO: GENERAR CERTIFICADO
        if ($command === 'generar_certificado') {
            Log::info("🤖 Usuario solicitó iniciar flujo de Generar Certificado");
            
            if (!$isAuthenticated) {
                Log::warning("❌ Usuario no autenticado intentando generar certificado");
                
                $this->messageService->sendText($userPhone,
                    $this->templateService->getAuthenticationRequired('generar certificados')
                );
                
                // Iniciar flujo de autenticación con acción solicitada
                $this->stateService->updateState($userPhone, [
                    'step' => 'auth_username',
                    'authenticated' => false,
                    'requested_action' => 'generar_certificado'
                ]);
                return;
            }
            
            // Usuario autenticado - iniciar flujo de certificados
            $this->stateService->updateState($userPhone, [
                'step' => 'choosing_certificate_type',
                'authenticated' => true,
                'empresa_nit' => $userState['empresa_nit'] ?? null,
                'representante_legal' => $userState['representante_legal'] ?? null
            ]);
            
            $this->messageService->sendText($userPhone, $this->templateService->getCertificateOptions());
            return;
        }

        // COMANDO: CONSULTAR CERTIFICADOS
        if ($command === 'consultar_certificados') {
            Log::info("🔍 Usuario quiere consultar certificados generados");
            
            if (!$isAuthenticated) {
                Log::info("🔒 Usuario no autenticado, redirigiendo a autenticación");
                
                $this->messageService->sendText($userPhone,
                    $this->templateService->getAuthenticationRequired('consultar certificados')
                );
                
                $this->stateService->updateState($userPhone, [
                    'step' => 'auth_username',
                    'authenticated' => false,
                    'requested_action' => 'consultar_certificados'
                ]);
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

        // COMANDO: AUTENTICAR
        if ($command === 'autenticar') {
            Log::info("🔐 Usuario solicitó autenticarse");
            
            if ($isAuthenticated) {
                $userName = $userState['representante_legal'] ?? $userState['nombre_contacto'] ?? 'Usuario';
                $nit = $userState['empresa_nit'] ?? 'N/A';
                $this->messageService->sendText($userPhone,
                    $this->templateService->getAlreadyAuthenticated($userName, $nit)
                );
            } else {
                // Iniciar autenticación
                $this->messageService->sendText($userPhone, $this->templateService->getAuthPrompt());
                
                $this->stateService->updateState($userPhone, [
                    'step' => 'auth_username',
                    'authenticated' => false
                ]);
            }
            return;
        }

        // COMANDO: CERRAR SESIÓN
        if ($command === 'cerrar_sesion') {
            if (!$isAuthenticated) {
                $this->messageService->sendText($userPhone,
                    $this->templateService->getNoAuthenticationMessage()
                );
                return;
            }
            
            // Si está autenticado, llamar al logout
            $this->handleAuthFlowAction->logout($userPhone);
            return;
        }

        // COMANDO: REQUISITOS
        if ($command === 'requisitos') {
            Log::info("🤖 Usuario solicitó Requisitos");
            $this->messageService->sendText($userPhone, $this->templateService->getRequirements());
            return;
        }

        // COMANDO: SOPORTE
        if ($command === 'soporte') {
            Log::info("🤖 Usuario solicitó Soporte");
            $this->messageService->sendText($userPhone, $this->templateService->getSupportInfo());
            return;
        }

        // COMANDO: REGISTRO
        if ($command === 'registro') {
            Log::info("🤖 Usuario solicitó información de registro");
            $this->messageService->sendText($userPhone, $this->templateService->getRegistrationInfo());
            return;
        }

        // ========== SI NO SE RECONOCE EL COMANDO ==========
        Log::info("❓ No se reconoció comando global, enviando ayuda corta");
        
        if (!empty($currentStep)) {
            $this->messageService->sendText($userPhone,
                "🤔 *No entendí*\n\n" .
                "Parece que estás en medio de un proceso.\n\n" .
                "Si deseas cancelar, escribe *MENU* para volver al inicio.\n" .
                "O continúa con el proceso actual."
            );
        } else {
            $this->messageService->sendText($userPhone, $this->templateService->getUnknownCommand());
        }
    }
}