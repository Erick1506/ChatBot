<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\CertificateService;
use Illuminate\Support\Facades\Log;

class HandleCertificateFlowAction
{
    private CertificateService $certificateService;
    
    public function __construct(
        private MessageService $messageService,
        private StateService $stateService,
        private TemplateService $templateService
    ) {
        // Crear CertificateService manualmente
        $this->certificateService = new CertificateService();
    }

    public function execute(string $userPhone, string $messageText, array $userState): void
    {
        Log::info("=== HANDLE CERTIFICATE FLOW INICIADO ===");
        Log::info("Paso actual: " . ($userState['step'] ?? 'none'));
        Log::info("Mensaje: {$messageText}");

        if (!isset($userState['authenticated']) || !$userState['authenticated']) {
            Log::warning("❌ Usuario no autenticado intentando generar certificado");
            $this->messageService->sendText($userPhone, $this->templateService->getNotAuthenticated());
            return;
        }

        $nit = $userState['empresa_nit'] ?? null;
        if (!$nit) {
            Log::error("❌ No se encontró NIT en el estado del usuario autenticado");
            $this->messageService->sendText($userPhone, $this->templateService->getCompanyInfoNotFound());
            return;
        }

        $step = $userState['step'] ?? '';

        switch ($step) {
            case 'choosing_certificate_type':
                $this->handleCertificateType($userPhone, $messageText, $nit);
                break;

            case 'awaiting_ticket':
                $this->handleTicket($userPhone, $messageText, $nit);
                break;

            case 'awaiting_year':
                $this->handleYear($userPhone, $messageText, $nit);
                break;

            default:
                Log::info("🔀 Estado no reconocido, enviando mensaje de bienvenida");
                $this->messageService->sendText($userPhone, $this->templateService->getMenu());
                break;
        }
    }

    private function handleCertificateType(string $userPhone, string $messageText, string $nit): void
    {
        if (str_contains($messageText, 'ticket')) {
            Log::info("🎫 Usuario seleccionó Ticket");
            $this->stateService->updateState($userPhone, [
                'step' => 'awaiting_ticket',
                'type' => 'ticket'
            ]);
            $this->messageService->sendText($userPhone, $this->templateService->getCertificatePrompt('ticket'));
        } elseif (str_contains($messageText, 'nit') && !str_contains($messageText, 'vigencia')) {
            Log::info("🏢 Usuario seleccionó NIT - Generando certificado general");
            $this->generateCertificate($userPhone, 'nit_general', ['nit' => $nit]);
        } elseif (str_contains($messageText, 'vigencia') || str_contains($messageText, 'vigente')) {
            Log::info("📅 Usuario seleccionó Vigencia");
            $this->stateService->updateState($userPhone, [
                'step' => 'awaiting_year',
                'type' => 'vigencia'
            ]);
            $this->messageService->sendText($userPhone, $this->templateService->getCertificatePrompt('vigencia'));
        } else {
            Log::info("❌ Opción no reconocida en choosing_certificate_type, reenviando instrucciones");
            $this->messageService->sendText($userPhone, "No reconocí la opción. Responde con *TICKET*, *NIT* o *VIGENCIA* según corresponda.");
        }
    }

    private function handleTicket(string $userPhone, string $messageText, string $nit): void
    {
        Log::info("🎟️ Usuario ingresando ticket: {$messageText}");
        $this->generateCertificate($userPhone, 'nit_ticket', [
            'nit' => $nit,
            'ticket' => $messageText
        ]);
    }

    private function handleYear(string $userPhone, string $messageText, string $nit): void
    {
        Log::info("📅 Usuario ingresando año: {$messageText}");
        $year = intval(preg_replace('/[^0-9]/','',$messageText));
        
        if (!$this->certificateService->validateYear($year)) {
            $yearRange = $this->certificateService->getYearRange();
            Log::warning("❌ Año fuera de rango: {$year}");
            $this->messageService->sendText($userPhone, "❌ *Año fuera de rango*\n\nSolo se permiten vigencias entre {$yearRange['min']} y {$yearRange['max']}. Por favor ingresa un año válido (ej: 2025).");
            return;
        }

        $this->generateCertificate($userPhone, 'nit_vigencia', [
            'nit' => $nit,
            'vigencia' => $year
        ]);
    }

    private function generateCertificate(string $userPhone, string $type, array $data): void
    {
        try {
            $this->messageService->sendText($userPhone, $this->templateService->getProcessingCertificate());

            // Buscar certificados
            $certificados = $this->searchCertificates($type, $data['nit'], $data['ticket'] ?? null, $data['vigencia'] ?? null);

            if ($certificados->isEmpty()) {
                Log::warning("❌ No se encontraron certificados para los criterios");
                $this->messageService->sendText($userPhone, $this->templateService->getCertificateNotFound());
                $this->stateService->clearState($userPhone);
                return;
            }

            // OBTENER INFORMACIÓN DEL USUARIO PARA EL PDF
            $userState = $this->stateService->getState($userPhone);
            $nombreUsuario = $userState['representante_legal'] ?? 'Usuario WhatsApp';
            
            // Crear objeto con datos de empresa para el PDF
            $empresaData = (object)[
                'Usuario' => $nombreUsuario,
                'representante_legal' => $nombreUsuario,
                'nit' => $data['nit']
            ];

            // Generar PDF con 3 PARÁMETROS
            $pdfPath = $this->certificateService->generatePDF($certificados, $type, $empresaData);
            $resultadoPDF = $this->certificateService->generatePDF($certificados, $type, $empresaData);

            // Enviar documento
            $this->messageService->sendDocument($userPhone, $pdfPath, $fileName);

            $this->messageService->sendText($userPhone, $this->templateService->getCertificateGenerated());
            $this->messageService->sendText($userPhone, "¿Necesitas algo más? Escribe *MENU* para ver las opciones.");

            // Actualizar estado
            $this->stateService->updateState($userPhone, [
                'step' => 'main_menu',
                'authenticated' => true,
                'empresa_nit' => $userState['empresa_nit'] ?? null,
                'representante_legal' => $userState['representante_legal'] ?? null
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error generando certificado WhatsApp: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            $this->messageService->sendText($userPhone, $this->templateService->getErrorSystem());
            $this->stateService->clearState($userPhone);
        }
    }

    private function searchCertificates(string $type, string $nit, ?string $ticket = null, ?int $vigencia = null)
    {
        switch ($type) {
            case 'nit_ticket':
                return $this->certificateService->searchByTicket($nit, $ticket);
            case 'nit_vigencia':
                return $this->certificateService->searchByVigencia($nit, $vigencia);
            case 'nit_general':
            default:
                return $this->certificateService->searchByNit($nit);
        }
    }
}