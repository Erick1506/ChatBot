<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\CertificateService;
use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

class HandleCertificateFlowAction
{
    public function __construct(
        private MessageService $messageService,
        private StateService $stateService,
        private TemplateService $templateService,
        private CertificateService $certificateService
    ) {}

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

            case 'consulting_certificates':
                $this->handleConsultingCertificates($userPhone, $messageText, $nit, $userState);
                break;

            case 'selecting_certificate':
                $this->handleSelectingCertificate($userPhone, $messageText, $nit, $userState);
                break;

            case 'confirm_download':
                $this->handleConfirmDownload($userPhone, $messageText, $nit, $userState);
                break;

            default:
                Log::info("🔀 Estado no reconocido, enviando menú de certificados");
                $this->showCertificateMenu($userPhone, $nit);
                break;
        }
    }

    private function showCertificateMenu(string $userPhone, string $nit): void
    {
        Log::info("📋 Mostrando menú de certificados para NIT: {$nit}");
        
        $this->messageService->sendText($userPhone,
            "📋 *MENU DE CERTIFICADOS FIC*\n\n" .
            "Elige una opción:\n\n" .
            "• *GENERAR* - Crear un nuevo certificado\n" .
            "• *CONSULTAR* - Ver certificados generados\n" .
            "• *ESTADISTICAS* - Ver estadísticas\n" .
            "• *SALIR* - Volver al menú principal"
        );

        $this->stateService->updateState($userPhone, [
            'step' => 'choosing_certificate_type',
            'authenticated' => true,
            'empresa_nit' => $nit,
        ]);
    }

    private function handleCertificateType(string $userPhone, string $messageText, string $nit): void
    {
        $messageText = strtolower(trim($messageText));

        if (str_contains($messageText, 'generar')) {
            Log::info("🔄 Usuario quiere generar nuevo certificado");
            $this->showCertificateTypeOptions($userPhone);
            
        } elseif (str_contains($messageText, 'consultar')) {
            Log::info("📋 Usuario quiere consultar certificados generados");
            $this->startCertificateConsultation($userPhone, $nit);
            
        } elseif (str_contains($messageText, 'estadisticas') || str_contains($messageText, 'estadísticas')) {
            Log::info("📊 Usuario quiere ver estadísticas");
            $this->showStatistics($userPhone, $nit);
            
        } elseif (str_contains($messageText, 'salir') || str_contains($messageText, 'menu')) {
            Log::info("🔙 Usuario quiere salir al menú principal");
            $this->messageService->sendText($userPhone, $this->templateService->getMenu());
            $this->stateService->clearState($userPhone);
            
        } elseif (str_contains($messageText, 'ticket')) {
            Log::info("🎫 Usuario seleccionó Ticket");
            $this->stateService->updateState($userPhone, [
                'step' => 'awaiting_ticket',
                'certificate_type' => 'nit_ticket'
            ]);
            $this->messageService->sendText($userPhone, $this->templateService->getCertificatePrompt('ticket'));
            
        } elseif (str_contains($messageText, 'nit') && !str_contains($messageText, 'vigencia')) {
            Log::info("🏢 Usuario seleccionó NIT - Generando certificado general");
            $this->generateCertificate($userPhone, 'nit_general', ['nit' => $nit]);
            
        } elseif (str_contains($messageText, 'vigencia') || str_contains($messageText, 'vigente')) {
            Log::info("📅 Usuario seleccionó Vigencia");
            $this->stateService->updateState($userPhone, [
                'step' => 'awaiting_year',
                'certificate_type' => 'nit_vigencia'
            ]);
            $this->messageService->sendText($userPhone, $this->templateService->getCertificatePrompt('vigencia'));
            
        } else {
            Log::info("❌ Opción no reconocida en choosing_certificate_type");
            $this->showCertificateTypeOptions($userPhone);
        }
    }

    private function showCertificateTypeOptions(string $userPhone): void
    {
        $this->messageService->sendText($userPhone,
            "📄 *TIPO DE CERTIFICADO*\n\n" .
            "Elige el tipo de certificado que necesitas:\n\n" .
            "• *NIT* - Certificado general por NIT\n" .
            "• *TICKET* - Certificado por número de ticket\n" .
            "• *VIGENCIA* - Certificado por año de vigencia\n" .
            "🔙 *ATRAS* - Volver al menú anterior"
        );
    }

    private function handleTicket(string $userPhone, string $messageText, string $nit): void
    {
        Log::info("🎟️ Usuario ingresando ticket: {$messageText}");
        
        if (strtolower(trim($messageText)) === 'atras') {
            $this->showCertificateTypeOptions($userPhone);
            $this->stateService->updateState($userPhone, ['step' => 'choosing_certificate_type']);
            return;
        }
        
        $ticket = trim($messageText);
        
        if (empty($ticket)) {
            $this->messageService->sendText($userPhone, "❌ Por favor ingresa un número de ticket válido.");
            return;
        }
        
        $this->generateCertificate($userPhone, 'nit_ticket', [
            'nit' => $nit,
            'ticket' => $ticket
        ]);
    }

    private function handleYear(string $userPhone, string $messageText, string $nit): void
    {
        Log::info("📅 Usuario ingresando año: {$messageText}");
        
        if (strtolower(trim($messageText)) === 'atras') {
            $this->showCertificateTypeOptions($userPhone);
            $this->stateService->updateState($userPhone, ['step' => 'choosing_certificate_type']);
            return;
        }
        
        $year = intval(preg_replace('/[^0-9]/','',$messageText));
        
        if (!$this->certificateService->validateYear($year)) {
            $yearRange = $this->certificateService->getYearRange();
            Log::warning("❌ Año fuera de rango: {$year}");
            $this->messageService->sendText($userPhone, 
                "❌ *Año fuera de rango*\n\n" .
                "Solo se permiten vigencias entre {$yearRange['min']} y {$yearRange['max']}.\n" .
                "Por favor ingresa un año válido (ej: 2025)."
            );
            return;
        }

        $this->generateCertificate($userPhone, 'nit_vigencia', [
            'nit' => $nit,
            'year' => $year
        ]);
    }

    private function generateCertificate(string $userPhone, string $type, array $data): void
    {
        $pdfPath = null;
        
        try {
            Log::info("🎫 Iniciando generación de certificado tipo: {$type}");
            Log::info("📊 Datos: " . json_encode($data));
            
            // Enviar mensaje de procesamiento
            $this->messageService->sendText($userPhone, $this->templateService->getProcessingCertificate());

            // Buscar certificados
            $certificados = $this->searchCertificates($type, $data['nit'], $data['ticket'] ?? null, $data['year'] ?? null);

            if ($certificados->isEmpty()) {
                Log::warning("❌ No se encontraron certificados para los criterios");
                $this->messageService->sendText($userPhone, $this->templateService->getCertificateNotFound());
                $this->stateService->clearState($userPhone);
                return;
            }

            Log::info("✅ Encontrados {$certificados->count()} certificados");

            // Obtener información del usuario
            $userState = $this->stateService->getState($userPhone);
            $nombreUsuario = $this->getUserName($userPhone, $userState);
            
            // Crear objeto con datos de empresa
            $empresaData = (object)[
                'Usuario' => $nombreUsuario,
                'representante_legal' => $nombreUsuario,
                'nit' => $data['nit']
            ];

            // Generar PDF (ahora devuelve array)
            $resultadoPDF = $this->certificateService->generatePDF($certificados, $type, $empresaData);
            
            $pdfPath = $resultadoPDF['file_path'];
            $serial = $resultadoPDF['serial'];
            
            Log::info("📄 PDF generado: {$pdfPath}");
            Log::info("🔢 Serial asignado: {$serial}");

            // Enviar documento
            $fileName = "Certificado_{$serial}.pdf";
            $this->messageService->sendDocument($userPhone, $pdfPath, $fileName);

            // Informar al usuario del serial
            $this->messageService->sendText($userPhone,
                "✅ *Certificado generado exitosamente*\n\n" .
                "• *Serial:* {$serial}\n" .
                "¿Necesitas algo más? Escribe *MENU* para ver las opciones."
            );

            // Actualizar estado
            $this->stateService->updateState($userPhone, [
                'step' => 'main_menu',
                'authenticated' => true,
                'empresa_nit' => $userState['empresa_nit'] ?? null,
                'representante_legal' => $userState['representante_legal'] ?? null,
                'last_certificate_serial' => $serial
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error generando certificado WhatsApp: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Limpiar archivo temporal si existe
            if ($pdfPath && file_exists($pdfPath)) {
                @unlink($pdfPath);
                Log::info("🗑️ Archivo temporal eliminado por error: {$pdfPath}");
            }
            
            $this->messageService->sendText($userPhone, $this->templateService->getErrorSystem());
            $this->stateService->clearState($userPhone);
        }
    }

    private function getUserName(string $userPhone, array $userState): string
    {
        // Intentar obtener de varias fuentes
        $nombreUsuario = $userState['representante_legal'] ?? 
                        $userState['nombre_contacto'] ?? 
                        'Usuario WhatsApp';
        
        // Si no hay nombre, intentar obtener de la empresa
        if ($nombreUsuario === 'Usuario WhatsApp') {
            $nit = $userState['empresa_nit'] ?? null;
            if ($nit) {
                $empresa = Empresa::where('nit', $nit)->first();
                if ($empresa) {
                    $nombreUsuario = $empresa->representante_legal ?? $empresa->Usuario ?? 'Usuario WhatsApp';
                }
            }
        }
        
        return $nombreUsuario;
    }

    private function searchCertificates(string $type, string $nit, ?string $ticket = null, ?int $year = null)
    {
        switch ($type) {
            case 'nit_ticket':
                return $this->certificateService->searchByTicket($nit, $ticket);
            case 'nit_vigencia':
                return $this->certificateService->searchByVigencia($nit, $year);
            case 'nit_general':
            default:
                return $this->certificateService->searchByNit($nit);
        }
    }

    private function startCertificateConsultation(string $userPhone, string $nit): void
    {
        Log::info("🔍 Iniciando consulta de certificados para NIT: {$nit}");
        
        $this->stateService->updateState($userPhone, [
            'step' => 'consulting_certificates',
            'consulta_page' => 1
        ]);
        
        $this->listCertificates($userPhone, $nit, 1);
    }

    private function handleConsultingCertificates(string $userPhone, string $messageText, string $nit, array $userState): void
    {
        $messageText = strtolower(trim($messageText));
        
        if ($messageText === 'atras' || $messageText === 'menu') {
            $this->showCertificateMenu($userPhone, $nit);
            return;
        }
        
        if ($messageText === 'siguiente') {
            $page = ($userState['consulta_page'] ?? 1) + 1;
            $this->stateService->updateState($userPhone, ['consulta_page' => $page]);
            $this->listCertificates($userPhone, $nit, $page);
            return;
        }
        
        if ($messageText === 'anterior') {
            $page = max(1, ($userState['consulta_page'] ?? 1) - 1);
            $this->stateService->updateState($userPhone, ['consulta_page' => $page]);
            $this->listCertificates($userPhone, $nit, $page);
            return;
        }
        
        // Verificar si es una selección numérica
        $selection = intval($messageText);
        if ($selection > 0) {
            $this->selectCertificate($userPhone, $nit, $selection, $userState);
            return;
        }
        
        $this->messageService->sendText($userPhone,
            "❌ *Opción no válida*\n\n" .
            "Por favor selecciona un número de la lista, " .
            "o usa *ANTERIOR*/*SIGUIENTE* para navegar.\n" .
            "Escribe *ATRAS* para volver al menú."
        );
    }

    private function listCertificates(string $userPhone, string $nit, int $page = 1): void
    {
        $limit = 5;
        $offset = ($page - 1) * $limit;
        
        Log::info("📋 Listando certificados página {$page} para NIT: {$nit}");
        
        // Buscar certificados generados
        $certificados = $this->certificateService->buscarCertificadosGenerados($nit, $limit + 1);
        
        if ($certificados->isEmpty()) {
            $this->messageService->sendText($userPhone,
                "📭 *No hay certificados generados*\n\n" .
                "No se encontraron certificados generados para tu NIT.\n" .
                "Puedes generar uno nuevo seleccionando la opción *GENERAR*.\n\n" .
                "Escribe *ATRAS* para volver al menú."
            );
            return;
        }
        
        // Preparar lista paginada
        $total = $certificados->count();
        $hasNext = $total > $limit;
        $certificados = $certificados->slice($offset, $limit);
        
        $mensaje = "📋 *Tus Certificados Generados* - Página {$page}\n\n";
        
        $contador = 1;
        $listaCertificados = [];
        
        foreach ($certificados as $cert) {
            $listaCertificados[$contador] = [
                'id' => $cert->id,
                'serial' => $cert->serial,
                'ruta' => $cert->ruta_archivo,
                'nombre' => $cert->nombre_archivo,
            ];
            
            $fecha = $cert->created_at->format('d/m/Y');
            $hora = $cert->created_at->format('H:i');
            
            $tipoTexto = match($cert->tipo_certificado) {
                'nit_general' => 'General',
                'nit_ticket' => 'Ticket',
                'nit_vigencia' => 'Vigencia',
                default => $cert->tipo_certificado
            };
            
            $mensaje .= "*{$contador}.* 📄 *{$cert->serial}*\n";
            $mensaje .= "   • *Fecha y hora de generación:* {$fecha} ⏰ {$hora}\n";
            $mensaje .= "   • *Tipo:* {$tipoTexto}\n";
            $mensaje .= "   👤 *Usuario:* {$cert->usuario_generador}\n";
            
            $contador++;
        }
        
        $mensaje .= "Responde con el *número* del certificado que deseas descargar.\n\n";
        
        if ($page > 1) {
            $mensaje .= "📄 *ANTERIOR* - Página anterior\n";
        }
        
        if ($hasNext) {
            $mensaje .= "📄 *SIGUIENTE* - Página siguiente\n";
        }
        
        $mensaje .= "🔙 *ATRAS* - Volver al menú";
        
        $this->messageService->sendText($userPhone, $mensaje);
        
        // Guardar lista en el estado
        $this->stateService->updateState($userPhone, [
            'step' => 'consulting_certificates',
            'lista_certificados' => $listaCertificados,
            'consulta_page' => $page,
            'has_next_page' => $hasNext
        ]);
    }

    private function selectCertificate(string $userPhone, string $nit, int $selection, array $userState): void
    {
        $listaCertificados = $userState['lista_certificados'] ?? [];
        
        if (!isset($listaCertificados[$selection])) {
            $this->messageService->sendText($userPhone,
                "❌ *Selección inválida*\n\n" .
                "Por favor, elige un número de la lista anterior."
            );
            return;
        }
        
        $certificado = $listaCertificados[$selection];
        
        // Obtener información completa del certificado
        $certificadoCompleto = $this->certificateService->buscarCertificadoPorSerial($certificado['serial']);
        
        if (!$certificadoCompleto) {
            $this->messageService->sendText($userPhone,
                "❌ *Certificado no encontrado*\n\n" .
                "El certificado seleccionado ya no está disponible."
            );
            return;
        }
        
        // Mostrar detalles y pedir confirmación
        $fecha = $certificadoCompleto->created_at->format('d/m/Y H:i');
        
        $this->messageService->sendText($userPhone,
            "✅ *Certificado seleccionado*\n\n" .
            "• *Serial:* {$certificadoCompleto->serial}\n" .
            "• *Fecha generación:* {$fecha}\n" .
            "• *Tipo:* " . $this->getTipoTexto($certificadoCompleto->tipo_certificado) . "\n" .
            "👤 *Generado por:* {$certificadoCompleto->usuario_generador}\n\n" .
            "¿Deseas descargar este certificado?\n\n" .
            "Responde *SI* para confirmar o *NO* para cancelar."
        );
        
        $this->stateService->updateState($userPhone, [
            'step' => 'confirm_download',
            'certificado_seleccionado' => $certificadoCompleto->toArray(),
        ]);
    }

    private function getTipoTexto(string $tipo): string
    {
        return match($tipo) {
            'nit_general' => 'General por NIT',
            'nit_ticket' => 'Por Ticket',
            'nit_vigencia' => 'Por Vigencia',
            default => $tipo
        };
    }

    private function handleConfirmDownload(string $userPhone, string $messageText, string $nit, array $userState): void
    {
        $respuesta = strtolower(trim($messageText));
        
        if (in_array($respuesta, ['si', 'sí', 'yes', 'confirmar', 'descargar'])) {
            $certificado = $userState['certificado_seleccionado'] ?? null;
            
            if (!$certificado) {
                $this->messageService->sendText($userPhone, 
                    "❌ *Error al descargar*\n\n" .
                    "No se encontró información del certificado."
                );
                $this->stateService->clearState($userPhone);
                return;
            }
            
            $serial = $certificado['serial'] ?? null;
            $rutaArchivo = $certificado['ruta_archivo'] ?? null;
            
            if (!$serial || !$rutaArchivo || !file_exists($rutaArchivo)) {
                $this->messageService->sendText($userPhone,
                    "❌ *Archivo no disponible*\n\n" .
                    "El archivo del certificado ya no está disponible.\n" .
                    "Serial: {$serial}"
                );
                $this->stateService->clearState($userPhone);
                return;
            }
            
            // Enviar archivo
            $nombreArchivo = "Certificado_{$serial}.pdf";
            $this->messageService->sendDocument($userPhone, $rutaArchivo, $nombreArchivo);
            
            // Actualizar registro en BD
            $certGenerado = $this->certificateService->buscarCertificadoPorSerial($serial);
            if ($certGenerado) {
                $certGenerado->marcarDescargado();
                Log::info("✅ Certificado {$serial} marcado como descargado");
            }
            
            $this->messageService->sendText($userPhone,
                "✅ *Certificado descargado*\n\n" .
                "El certificado *{$serial}* ha sido descargado exitosamente.\n\n" .
                "¿Necesitas algo más? Escribe *MENU* para ver las opciones."
            );
            
        } elseif (in_array($respuesta, ['no', 'cancelar', 'atras'])) {
            $this->messageService->sendText($userPhone, 
                "❌ Descarga cancelada.\n\n" .
                "Puedes seleccionar otro certificado o escribir *ATRAS* para volver al menú."
            );
            
            $this->stateService->updateState($userPhone, [
                'step' => 'consulting_certificates',
                'consulta_page' => $userState['consulta_page'] ?? 1
            ]);
            
        } else {
            $this->messageService->sendText($userPhone, 
                "❌ *Respuesta no reconocida*\n\n" .
                "Responde *SI* para confirmar o *NO* para cancelar."
            );
            return;
        }
    }

    private function handleSelectingCertificate(string $userPhone, string $messageText, string $nit, array $userState): void
    {
        $this->handleConfirmDownload($userPhone, $messageText, $nit, $userState);
    }
}