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

        Log::info("📱 Estado usuario: " . ($isAuthenticated ? "Autenticado" : "No autenticado"));
        Log::info("📱 Paso actual: " . ($userState['step'] ?? 'Ninguno'));

        // VERIFICAR PRIMERO SI ESTÁ EN FLUJO DE AUTENTICACIÓN
        // Esta es la clave: verificar si el paso actual está relacionado con autenticación
        $currentStep = $userState['step'] ?? '';
        $authSteps = ['auth_username', 'awaiting_username', 'auth_password', 'awaiting_password'];
        
        if (in_array($currentStep, $authSteps)) {
            Log::info("🔐 Estado de autenticación detectado ({$currentStep}) — manejando por flujo de auth");
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
        
        Log::info("🔍 Comando detectado: " . ($command ?? "Ninguno"));

        if ($command === 'menu') {
            Log::info("🤖 Comando MENU/HOLA recibido - suppressWelcome={$suppressWelcome}");
            
            // Mostrar menú mejorado basado en autenticación
            if ($isAuthenticated) {
                $userName = $userState['representante_legal'] ?? $userState['nombre_contacto'] ?? 'Usuario';
                $nit = $userState['empresa_nit'] ?? 'N/A';
                
                $welcomeMsg = "👋 ¡Hola *{$userName}*! (NIT: *{$nit}*)\n\n";
                $welcomeMsg .= "Selecciona una opción:\n\n";
                $welcomeMsg .= "• *Generar Certificado*\n";
                $welcomeMsg .= "• *Consultar Certificados*\n";
                $welcomeMsg .= "• *Requisitos*\n";
                $welcomeMsg .= "• *Soporte*\n";
                $welcomeMsg .= "• *Cerrar Sesión*\n";
                $welcomeMsg .= "• *Registro*\n\n";
                $welcomeMsg .= "Escribe el nombre de la opción.";
                
                $this->messageService->sendText($userPhone, $welcomeMsg);
            } else {
                // Usar el menú estándar para no autenticados
                if (!$suppressWelcome) {
                    $this->messageService->sendText($userPhone, 
                        "📌 *MENÚ PRINCIPAL - Chatbot FIC*\n\n" .
                        "¡Bienvenido! Escribe el nombre de una opción:\n\n" .
                        "• *Requisitos*\n" .
                        "• *Soporte*\n" .
                        "• *Autenticarse*\n" .
                        "• *Registro*\n\n" 
                    );
                }
            }
            
            $this->stateService->updateState($userPhone, ['step' => 'main_menu']);
            return;
        }

        if ($command === 'generar_certificado') {
            Log::info("🤖 Usuario solicitó iniciar flujo de Generar Certificado");
            
            // Verificar si el usuario está autenticado
            if (!$isAuthenticated) {
                Log::warning("❌ Usuario no autenticado intentando generar certificado");
                
                // Pedir autenticación primero
                $this->messageService->sendText($userPhone,
                    "🔐 *Autenticación requerida*\n\n" .
                    "Para generar certificados, primero debes autenticarte.\n\n" .
                    "Por favor, ingresa tu *USUARIO*:"
                );
                
                // Iniciar flujo de autenticación
                $this->stateService->updateState($userPhone, [
                    'step' => 'auth_username',
                    'authenticated' => false,
                    'requested_action' => 'generar_certificado'
                ]);
                return;
            }
            
            // Si ya está autenticado, iniciar flujo de certificados
            $this->stateService->updateState($userPhone, [
                'step' => 'choosing_certificate_type',
                'authenticated' => true,
                'empresa_nit' => $userState['empresa_nit'] ?? null,
                'representante_legal' => $userState['representante_legal'] ?? null
            ]);
            
            // Mostrar opciones de certificados
            $this->messageService->sendText($userPhone, $this->templateService->getCertificateOptions());
            return;
        }

        // Comando: autenticar (opción 5)
        if ($command === 'autenticar') {
            Log::info("🔐 Usuario solicitó autenticarse");
            
            if ($isAuthenticated) {
                $this->messageService->sendText($userPhone,
                    "✅ *Ya estás autenticado*\n\n" .
                    "Si deseas cerrar sesión, escribe *CERRAR SESION*.\n\n" .
                    "O escribe *MENU* para ver las opciones."
                );
            } else {
                // Iniciar autenticación
                $this->messageService->sendText($userPhone, 
                    "🔐 *VALIDACIÓN DE USUARIO*\n\n" .
                    "⚠️ *Debes validar tu información antes de generar o consultar certificados.*\n\n" .
                    "Por favor, ingresa tu *USUARIO*:"
                );
                
                $this->stateService->updateState($userPhone, [
                    'step' => 'auth_username',
                    'authenticated' => false
                ]);
            }
            return;
        }

        // Comando: cerrar sesión
        if ($command === 'cerrar_sesion') {
            $this->handleAuthFlowAction->logout($userPhone);
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
                    "🔐 *Autenticación requerida*\n\n" .
                    "Para consultar tus certificados, primero debes autenticarte.\n\n" .
                    "Por favor, ingresa tu *USUARIO*:"
                );
                
                // Iniciar flujo de autenticación
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

        // Si no se reconoce
        Log::info("❓ No se reconoció comando global, enviando ayuda corta");
        
        // Verificar si está en algún flujo especial
        if (!empty($currentStep)) {
            $this->messageService->sendText($userPhone,
                "🤔 *No entendí*\n\n" .
                "Parece que estás en medio de un proceso.\n\n" .
                "Si deseas cancelar, escribe *MENU* para volver al inicio.\n" .
                "O continúa con el proceso actual."
            );
        } else {
            $this->messageService->sendText($userPhone, 
                "🤔 *No entendí*\n\n" .
                "Comandos disponibles:\n\n" .
                "• *MENU* - Ver opciones principales\n" .
                "• *CERRAR SESION* (si estás autenticado)"
            );
        }
    }
}