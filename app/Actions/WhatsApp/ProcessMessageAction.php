<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\CertificateService;
use Illuminate\Support\Facades\Log;

class HandleConsultaCertificadosAction
{
<<<<<<< HEAD
=======
    private HandleAuthFlowAction $handleAuthFlowAction;
    private HandleCertificateFlowAction $handleCertificateFlowAction;
    
>>>>>>> 54f00eac319c0a97bc8db1a7a4fedd3e26e446b0
    public function __construct(
        private MessageService $messageService,
        private StateService $stateService,
        private TemplateService $templateService,
<<<<<<< HEAD
        private CertificateService $certificateService
    ) {}
    
    public function execute(string $userPhone, string $messageText, array $userState): void
=======
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
>>>>>>> 54f00eac319c0a97bc8db1a7a4fedd3e26e446b0
    {
        Log::info("🔍 === CONSULTA CERTIFICADOS INICIADA ===");
        Log::info("Usuario: {$userPhone}, Paso: " . ($userState['step'] ?? 'none'));
        
        if (!isset($userState['authenticated']) || !$userState['authenticated']) {
            $this->messageService->sendText($userPhone, $this->templateService->getNotAuthenticated());
            return;
        }
        
        $nit = $userState['empresa_nit'] ?? null;
        if (!$nit) {
            $this->messageService->sendText($userPhone, "❌ No se encontró NIT en tu perfil.");
            return;
        }
        
        $step = $userState['step'] ?? '';
        
        switch ($step) {
            case 'menu_consulta':
                $this->handleMenuConsulta($userPhone, $messageText, $nit);
                break;
                
            case 'seleccionar_certificado':
                $this->handleSeleccionCertificado($userPhone, $messageText, $nit, $userState);
                break;
                
            case 'confirmar_descarga':
                $this->handleConfirmarDescarga($userPhone, $messageText, $nit, $userState);
                break;
                
            default:
                $this->iniciarConsulta($userPhone, $nit);
                break;
        }
    }
    
    private function iniciarConsulta(string $userPhone, string $nit): void
    {
        // Buscar certificados generados
        $certificados = $this->certificateService->buscarCertificadosGenerados($nit, 10);
        
        if ($certificados->isEmpty()) {
            $this->messageService->sendText($userPhone, 
                "📭 *No hay certificados generados*\n\n" .
                "No se encontraron certificados generados para tu NIT.\n" .
                "Genera un certificado nuevo escribiendo *MENU*."
            );
            $this->stateService->clearState($userPhone);
            return;
        }
<<<<<<< HEAD
        
        // Preparar lista de certificados
        $mensaje = "📋 *Tus Certificados Generados*\n\n";
        $contador = 1;
        $listaCertificados = [];
        
        foreach ($certificados as $cert) {
            $listaCertificados[$contador] = [
                'id' => $cert->id,
                'serial' => $cert->serial,
                'ruta' => $cert->ruta_archivo,
            ];
            
            $fecha = $cert->created_at->format('d/m/Y');
            $hora = $cert->created_at->format('H:i');
            
            $mensaje .= "*{$contador}.* 📄 *{$cert->serial}*\n";
            $mensaje .= "   📅 {$fecha} ⏰ {$hora}\n";
            $mensaje .= "   🏢 {$cert->nombre_empresa}\n";
            $mensaje .= "   📊 {$cert->cantidad_registros} registros\n";
            $mensaje .= "   💰 $" . number_format($cert->valor_total, 0, ',', '.') . "\n";
            $mensaje .= "   👤 {$cert->usuario_generador}\n\n";
            
            $contador++;
        }
        
        $mensaje .= "Responde con el *número* del certificado que deseas descargar.\n";
        $mensaje .= "Escribe *0* para volver al menú principal.";
        
        $this->messageService->sendText($userPhone, $mensaje);
        
        // Guardar estado con lista de certificados
        $this->stateService->updateState($userPhone, [
            'step' => 'seleccionar_certificado',
            'lista_certificados' => $listaCertificados,
            'ultima_consulta' => now()->toDateTimeString(),
        ]);
    }
    
    private function handleSeleccionCertificado(string $userPhone, string $messageText, string $nit, array $userState): void
    {
        $seleccion = intval($messageText);
        
        if ($seleccion === 0) {
            $this->messageService->sendText($userPhone, $this->templateService->getMenu());
            $this->stateService->clearState($userPhone);
            return;
        }
        
        $listaCertificados = $userState['lista_certificados'] ?? [];
        
        if (!isset($listaCertificados[$seleccion])) {
            $this->messageService->sendText($userPhone, 
                "❌ *Selección inválida*\n\n" .
                "Por favor, elige un número de la lista anterior."
            );
            return;
        }
        
        $certificado = $listaCertificados[$seleccion];
        
        // Confirmar descarga
        $this->messageService->sendText($userPhone,
            "✅ *Certificado seleccionado*\n\n" .
            "Serial: *{$certificado['serial']}*\n\n" .
            "¿Deseas descargar este certificado?\n\n" .
            "Responde *SI* para confirmar o *NO* para cancelar."
        );
        
        $this->stateService->updateState($userPhone, [
            'step' => 'confirmar_descarga',
            'certificado_seleccionado' => $certificado,
        ]);
    }
    
    private function handleConfirmarDescarga(string $userPhone, string $messageText, string $nit, array $userState): void
    {
        $respuesta = strtolower(trim($messageText));
        
        if (in_array($respuesta, ['si', 'sí', 'yes', 'confirmar'])) {
            $certificado = $userState['certificado_seleccionado'] ?? null;
            
            if (!$certificado || !file_exists($certificado['ruta'])) {
                $this->messageService->sendText($userPhone, 
                    "❌ *Error al descargar*\n\n" .
                    "El certificado seleccionado no está disponible."
                );
                $this->stateService->clearState($userPhone);
                return;
            }
            
            // Enviar archivo
            $nombreArchivo = "Certificado_{$certificado['serial']}.pdf";
            $this->messageService->sendDocument($userPhone, $certificado['ruta'], $nombreArchivo);
            
            // Actualizar registro en BD
            $certGenerado = $this->certificateService->buscarCertificadoPorSerial($certificado['serial']);
            if ($certGenerado) {
                $certGenerado->marcarDescargado();
            }
            
            $this->messageService->sendText($userPhone,
                "✅ *Certificado descargado*\n\n" .
                "El certificado *{$certificado['serial']}* ha sido descargado exitosamente.\n\n" .
                "¿Necesitas algo más? Escribe *MENU* para ver las opciones."
            );
            
        } elseif (in_array($respuesta, ['no', 'cancelar', 'atras'])) {
            $this->messageService->sendText($userPhone, "❌ Descarga cancelada.");
        } else {
            $this->messageService->sendText($userPhone, 
                "❌ *Respuesta no reconocida*\n\n" .
                "Responde *SI* para confirmar o *NO* para cancelar."
            );
=======

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

        if (str_contains(strtolower($messageText), 'consultar') || str_contains(strtolower($messageText), 'certificados')) {
            Log::info("🔍 Usuario quiere consultar certificados generados");
            
            // Crear instancia del action de consulta
            $consultaAction = new HandleConsultaCertificadosAction(
                $this->messageService,
                $this->stateService,
                $this->templateService,
                new CertificateService() // O inyectarlo
            );
            
            $consultaAction->execute($from, $messageText, $userState);
>>>>>>> 54f00eac319c0a97bc8db1a7a4fedd3e26e446b0
            return;
        }
        
        $this->stateService->clearState($userPhone);
    }
    
    private function handleMenuConsulta(string $userPhone, string $messageText, string $nit): void
    {
        if (str_contains($messageText, 'listar')) {
            $this->iniciarConsulta($userPhone, $nit);
        } elseif (str_contains($messageText, 'estadisticas')) {
            $this->mostrarEstadisticas($userPhone, $nit);
        } elseif (str_contains($messageText, 'menu')) {
            $this->messageService->sendText($userPhone, $this->templateService->getMenu());
            $this->stateService->clearState($userPhone);
        } else {
            $this->messageService->sendText($userPhone,
                "📊 *Consulta de Certificados*\n\n" .
                "Elige una opción:\n\n" .
                "📋 *LISTAR* - Ver mis certificados generados\n" .
                "📈 *ESTADISTICAS* - Ver estadísticas\n" .
                "🔙 *MENU* - Volver al menú principal"
            );
        }
    }
    
    private function mostrarEstadisticas(string $userPhone, string $nit): void
    {
        $estadisticas = $this->certificateService->obtenerEstadisticas($nit);
        
        $mensaje = "📈 *Estadísticas de Certificados*\n\n";
        $mensaje .= "🏢 NIT: *{$nit}*\n\n";
        $mensaje .= "📄 *Total generados:* {$estadisticas['total']}\n";
        $mensaje .= "📅 *Última semana:* {$estadisticas['ultima_semana']}\n";
        $mensaje .= "💰 *Valor total:* $" . number_format($estadisticas['valor_total'], 0, ',', '.') . "\n\n";
        
        if (!empty($estadisticas['por_tipo'])) {
            $mensaje .= "*Distribución por tipo:*\n";
            foreach ($estadisticas['por_tipo'] as $tipo => $cantidad) {
                $tipoTexto = match($tipo) {
                    'nit_general' => 'General',
                    'nit_ticket' => 'Por Ticket',
                    'nit_vigencia' => 'Por Vigencia',
                    default => $tipo
                };
                $mensaje .= "  • {$tipoTexto}: {$cantidad}\n";
            }
        }
        
        $mensaje .= "\nEscribe *LISTAR* para ver tus certificados o *MENU* para volver.";
        
        $this->messageService->sendText($userPhone, $mensaje);
        
        $this->stateService->updateState($userPhone, [
            'step' => 'menu_consulta',
        ]);
    }
}