<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\CertificadoFIC;
use Barryvdh\DomPDF\Facade\Pdf;

class WhatsAppController extends Controller
{
    // Verificar el webhook (requerido por Meta)
    public function verifyWebhook(Request $request)
    {
        \Log::info('=== VERIFY WEBHOOK INICIADO ===');
        \Log::info('Query parameters:', $request->query());
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');
        
        $expectedToken = config('services.whatsapp.verify_token');
        
        \Log::info("Mode: {$mode}, Token: {$token}, Expected: {$expectedToken}, Challenge: {$challenge}");

        // Verifica que coincida con tu verify token
        if ($mode === 'subscribe' && $token === $expectedToken) {
            \Log::info('✅ Webhook verificado exitosamente');
            return response($challenge, 200);
        }

        \Log::warning('❌ Webhook verification failed');
        return response('Forbidden', 403);
    }

    // Recibir mensajes de WhatsApp
    public function webhook(Request $request)
    {
        \Log::info('=== WEBHOOK INICIADO ===');
        \Log::info('Headers:', $request->headers->all());
        \Log::info('Webhook data recibida:', $request->all());

        $data = $request->all();

        // Verificar que es un mensaje válido
        if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            $userPhone = $message['from']; // Número del usuario
            $messageText = $message['text']['body'] ?? '';
            
            \Log::info("📱 Mensaje recibido - De: {$userPhone}, Texto: {$messageText}");
            \Log::info("📋 Detalles del mensaje:", $message);

            // Procesar el mensaje
            $this->processMessage($userPhone, $messageText);
        } else {
            \Log::warning('❌ No se encontró mensaje en el webhook');
            \Log::info('Estructura completa recibida:', $data);
        }

        \Log::info('=== WEBHOOK FINALIZADO ===');
        return response('OK', 200);
    }

    private function processMessage($userPhone, $messageText)
    {
        \Log::info("=== PROCESS MESSAGE INICIADO ===");
        \Log::info("Procesando mensaje - Usuario: {$userPhone}, Texto: {$messageText}");
        
        $messageText = strtolower(trim($messageText));
        
        // Obtener o inicializar el estado del usuario
        $userState = $this->getUserState($userPhone);
        \Log::info("Estado actual del usuario:", $userState);

        // Lógica del chatbot
        if ($messageText === 'hola' || $messageText === 'inicio' || $messageText === 'menu') {
            \Log::info("🤖 Enviando mensaje de bienvenida");
            $this->sendWelcomeMessage($userPhone);
            $this->updateUserState($userPhone, ['step' => 'main_menu']);
            return;
        }

        if ($messageText === '1' || str_contains($messageText, 'generar certificado')) {
            \Log::info("🤖 Usuario seleccionó Generar Certificado");
            $this->sendCertificateOptions($userPhone);
            $this->updateUserState($userPhone, ['step' => 'choosing_certificate_type']);
            return;
        }

        if ($messageText === '2' || str_contains($messageText, 'requisitos')) {
            \Log::info("🤖 Usuario seleccionó Requisitos");
            $this->sendRequirements($userPhone);
            return;
        }

        if ($messageText === '3' || str_contains($messageText, 'soporte')) {
            \Log::info("🤖 Usuario seleccionó Soporte");
            $this->sendSupportInfo($userPhone);
            return;
        }

        \Log::info("🔄 Iniciando manejo de flujo de certificados");
        // Manejar flujo de generación de certificados
        $this->handleCertificateFlow($userPhone, $messageText, $userState);
        
        \Log::info("=== PROCESS MESSAGE FINALIZADO ===");
    }

    private function handleCertificateFlow($userPhone, $messageText, $userState)
    {
        \Log::info("=== HANDLE CERTIFICATE FLOW INICIADO ===");
        \Log::info("Paso actual: " . ($userState['step'] ?? 'none'));
        \Log::info("Mensaje: {$messageText}");

        switch ($userState['step'] ?? '') {
            case 'choosing_certificate_type':
                \Log::info("🔀 Usuario eligiendo tipo de certificado");
                if ($messageText === '1' || str_contains($messageText, 'ticket')) {
                    \Log::info("🎫 Usuario seleccionó Ticket");
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_nit_ticket',
                        'type' => 'ticket'
                    ]);
                    $this->sendMessage($userPhone, "🪪 *Certificado por TICKET*\n\nPor favor ingresa el NIT de la empresa:");
                } elseif ($messageText === '2' || str_contains($messageText, 'nit')) {
                    \Log::info("🏢 Usuario seleccionó NIT");
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_nit_general',
                        'type' => 'nit'
                    ]);
                    $this->sendMessage($userPhone, "🏢 *Certificado por NIT*\n\nIngresa el NIT o cédula del empresario:");
                } elseif ($messageText === '3' || str_contains($messageText, 'vigencia')) {
                    \Log::info("📅 Usuario seleccionó Vigencia");
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_nit_vigencia',
                        'type' => 'vigencia'
                    ]);
                    $this->sendMessage($userPhone, "📅 *Certificado por VIGENCIA*\n\nPrimero ingresa el NIT o cédula del empresario:");
                } else {
                    \Log::info("❌ Opción no reconocida, reenviando opciones");
                    $this->sendCertificateOptions($userPhone);
                }
                break;

            case 'awaiting_nit_ticket':
                \Log::info("🔢 Usuario ingresando NIT para ticket: {$messageText}");
                $this->updateUserState($userPhone, [
                    'step' => 'awaiting_ticket',
                    'nit' => $messageText
                ]);
                $this->sendMessage($userPhone, "🎫 Ahora ingresa el número de *TICKET*:");
                break;

            case 'awaiting_ticket':
                \Log::info("🎟️ Usuario ingresando ticket: {$messageText}");
                $userState = $this->getUserState($userPhone);
                $this->generateAndSendCertificate($userPhone, 'nit_ticket', [
                    'nit' => $userState['nit'],
                    'ticket' => $messageText
                ]);
                break;

            case 'awaiting_nit_general':
                \Log::info("🔢 Usuario ingresando NIT general: {$messageText}");
                $this->generateAndSendCertificate($userPhone, 'nit_general', [
                    'nit' => $messageText
                ]);
                break;

            case 'awaiting_nit_vigencia':
                \Log::info("🔢 Usuario ingresando NIT para vigencia: {$messageText}");
                $this->updateUserState($userPhone, [
                    'step' => 'awaiting_year',
                    'nit' => $messageText
                ]);
                $this->sendMessage($userPhone, "📋 Ingresa el *AÑO* de la vigencia:\n\nEjemplo: 2025\n\nSolo se permiten 15 años atrás desde el actual.");
                break;

            case 'awaiting_year':
                \Log::info("📅 Usuario ingresando año: {$messageText}");
                $userState = $this->getUserState($userPhone);
                $year = intval($messageText);
                $currentYear = date('Y');

                if ($year > $currentYear || $year < ($currentYear - 15)) {
                    \Log::warning("❌ Año fuera de rango: {$year}");
                    $this->sendMessage($userPhone, "❌ *Año fuera de rango*\n\nSolo se permiten vigencias entre " . ($currentYear - 15) . " y $currentYear.");
                    return;
                }

                $this->generateAndSendCertificate($userPhone, 'nit_vigencia', [
                    'nit' => $userState['nit'],
                    'vigencia' => $year
                ]);
                break;

            default:
                \Log::info("🔀 Estado no reconocido, enviando mensaje de bienvenida");
                $this->sendWelcomeMessage($userPhone);
                break;
        }
        
        \Log::info("=== HANDLE CERTIFICATE FLOW FINALIZADO ===");
    }

    private function generateAndSendCertificate($userPhone, $type, $data)
    {
        \Log::info("=== GENERATE AND SEND CERTIFICATE INICIADO ===");
        \Log::info("Tipo: {$type}, Datos:", $data);

        try {
            $this->sendMessage($userPhone, "⏳ *Generando certificado...*\n\nPor favor espera unos segundos.");

            // Buscar certificados
            $certificados = $this->buscarCertificados($type, $data['nit'], $data['ticket'] ?? null, $data['vigencia'] ?? null);
            \Log::info("Certificados encontrados: " . $certificados->count());

            if ($certificados->isEmpty()) {
                \Log::warning("❌ No se encontraron certificados para los criterios");
                $this->sendMessage($userPhone, "❌ *No se encontraron certificados*\n\nNo hay certificados con los criterios especificados.");
                $this->clearUserState($userPhone);
                return;
            }

            // Generar PDF
            \Log::info("📄 Generando PDF...");
            $pdfPath = $this->generarPdf($certificados, $type);
            \Log::info("PDF generado en: {$pdfPath}");

            // Enviar PDF por WhatsApp
            \Log::info("📤 Enviando documento por WhatsApp...");
            $this->sendDocument($userPhone, $pdfPath, $this->generarNombreArchivo($certificados->first(), $type));

            $this->sendMessage($userPhone, "✅ *Certificado generado exitosamente!*\n\nTu certificado FIC ha sido generado y enviado.");
            
            // Ofrecer volver al menú
            $this->sendMessage($userPhone, "¿Necesitas algo más? Escribe *MENU* para ver las opciones.");

            $this->clearUserState($userPhone);

        } catch (\Exception $e) {
            \Log::error('❌ Error generando certificado WhatsApp: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            $this->sendMessage($userPhone, "❌ *Error del sistema*\n\nPor favor intenta nuevamente o contacta a soporte.");
            $this->clearUserState($userPhone);
        }
        
        \Log::info("=== GENERATE AND SEND CERTIFICATE FINALIZADO ===");
    }

    // Métodos auxiliares (reutilizar los que ya tenemos)
    private function buscarCertificados($tipo, $nit, $ticket = null, $vigencia = null)
    {
        \Log::info("🔍 Buscando certificados - Tipo: {$tipo}, NIT: {$nit}, Ticket: {$ticket}, Vigencia: {$vigencia}");
        
        $query = CertificadoFIC::where('constructor_nit', $nit);
        \Log::info("Query base construida, count: " . $query->count());
        
        switch ($tipo) {
            case 'nit_ticket':
                $result = $query->where('ticket', $ticket)->get();
                \Log::info("Resultado busqueda por ticket: " . $result->count());
                return $result;
            case 'nit_vigencia':
                $pattern = $vigencia . '-%';
                $result = $query->where('periodo', 'like', $pattern)->get();
                \Log::info("Resultado busqueda por vigencia {$pattern}: " . $result->count());
                return $result;
            case 'nit_general':
            default:
                $result = $query->get();
                \Log::info("Resultado busqueda general: " . $result->count());
                return $result;
        }
    }

    private function generarPdf($certificados, $tipo)
    {
        \Log::info("📊 Generando PDF para {$certificados->count()} certificados, tipo: {$tipo}");
        
        $constructor = $certificados->first();
        $total = $certificados->sum('valor_pago');
        
        \Log::info("Constructor: {$constructor->constructor_razon_social}, Total: {$total}");
        
        $datos = [
            'certificados' => $certificados,
            'constructor' => $constructor,
            'total' => $total,
            'fecha_emision' => now(),
            'tipo_busqueda' => $tipo
        ];
        
        $pdf = Pdf::loadView('certificados.plantilla', $datos)
                  ->setPaper('a4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'Arial',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true
                  ]);
        
        // Guardar temporalmente
        $fileName = $this->generarNombreArchivo($constructor, $tipo);
        $filePath = storage_path('app/temp/' . $fileName);
        
        \Log::info("Guardando PDF en: {$filePath}");
        
        // Asegurar que existe el directorio
        if (!file_exists(dirname($filePath))) {
            \Log::info("Creando directorio: " . dirname($filePath));
            mkdir(dirname($filePath), 0755, true);
        }
        
        $pdf->save($filePath);
        \Log::info("✅ PDF guardado exitosamente");
        
        return $filePath;
    }

    private function generarNombreArchivo($constructor, $tipo)
    {
        $fecha = now()->format('Y-m-d');
        $nit = $constructor->constructor_nit;
        $fileName = "Certificado_FIC_{$nit}_{$tipo}_{$fecha}.pdf";
        \Log::info("Nombre de archivo generado: {$fileName}");
        return $fileName;
    }

    // Mensajes predefinidos
    private function sendWelcomeMessage($userPhone)
    {
        \Log::info("👋 Enviando mensaje de bienvenida a {$userPhone}");
        $message = "👋 *Bienvenido al Chatbot FIC - SENA*\n\n";
        $message .= "Selecciona una opción:\n\n";
        $message .= "1️⃣ *Generar Certificado* - Obtener certificado FIC\n";
        $message .= "2️⃣ *Requisitos* - Información requerida\n";
        $message .= "3️⃣ *Soporte* - Contactar asistencia\n\n";
        $message .= "Responde con el *número* de la opción deseada.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendCertificateOptions($userPhone)
    {
        \Log::info("📄 Enviando opciones de certificado a {$userPhone}");
        $message = "📄 *GENERAR CERTIFICADO FIC*\n\n";
        $message .= "Selecciona el tipo de certificado:\n\n";
        $message .= "1️⃣ *Por TICKET* - Certificado específico\n";
        $message .= "2️⃣ *Por NIT* - Todos los certificados\n";
        $message .= "3️⃣ *Por VIGENCIA* - Por año específico\n\n";
        $message .= "Responde con el *número* de tu elección.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendRequirements($userPhone)
    {
        \Log::info("📋 Enviando requisitos a {$userPhone}");
        $message = "📋 *REQUISITOS PARA CERTIFICADOS FIC*\n\n";
        $message .= "• NIT o Cédula del empresario\n";
        $message .= "• Tipo de certificado (Ticket, NIT o Vigencia)\n";
        $message .= "• Para vigencia: año específico (máx. 15 años atrás)\n\n";
        $message .= "Escribe *MENU* para volver al inicio.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendSupportInfo($userPhone)
    {
        \Log::info("📞 Enviando info de soporte a {$userPhone}");
        $message = "📞 *SOPORTE TÉCNICO*\n\n";
        $message .= "Para asistencia técnica contacta:\n\n";
        $message .= "📧 Email: soporte@sena.edu.co\n";
        $message .= "🌐 Web: www.sena.edu.co\n\n";
        $message .= "Escribe *MENU* para volver al inicio.";

        $this->sendMessage($userPhone, $message);
    }

    // Métodos para enviar mensajes y documentos
    private function sendMessage($to, $message)
    {
        \Log::info("✉️ ENVIANDO MENSAJE - Para: {$to}");
        \Log::info("📝 Mensaje: {$message}");
        
        $url = 'https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/messages';
        \Log::info("🌐 URL: {$url}");
        
        \Log::info("🔑 Token: " . substr(config('services.whatsapp.access_token'), 0, 10) . "...");
        \Log::info("📞 Phone Number ID: " . config('services.whatsapp.phone_number_id'));

        try {
            $response = Http::withToken(config('services.whatsapp.access_token'))
                ->timeout(30)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'text' => ['body' => $message]
                ]);

            \Log::info("📡 Respuesta HTTP Status: " . $response->status());
            \Log::info("📡 Respuesta WhatsApp API:", $response->json());

            if ($response->successful()) {
                \Log::info("✅ Mensaje enviado exitosamente a {$to}");
            } else {
                \Log::error("❌ Error enviando mensaje: " . $response->body());
            }

        } catch (\Exception $e) {
            \Log::error("💥 Excepción enviando mensaje: " . $e->getMessage());
            \Log::error("📋 Stack trace: " . $e->getTraceAsString());
        }
    }

    private function sendDocument($to, $filePath, $fileName)
    {
        \Log::info("📎 ENVIANDO DOCUMENTO - Para: {$to}, Archivo: {$fileName}");
        \Log::info("📁 Ruta del archivo: {$filePath}");

        $url = 'https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/messages';

        try {
            // Subir el archivo a WhatsApp
            \Log::info("⬆️ Subiendo archivo a WhatsApp...");
            $mediaResponse = Http::withToken(config('services.whatsapp.access_token'))
                ->attach('file', file_get_contents($filePath), $fileName)
                ->post('https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/media', [
                    'messaging_product' => 'whatsapp',
                    'type' => 'document/pdf'
                ]);

            \Log::info("📡 Respuesta subida de archivo:", $mediaResponse->json());

            if (isset($mediaResponse->json()['id'])) {
                $mediaId = $mediaResponse->json()['id'];
                \Log::info("🆔 Media ID obtenido: {$mediaId}");

                // Enviar el documento
                \Log::info("📤 Enviando documento con media ID...");
                $sendResponse = Http::withToken(config('services.whatsapp.access_token'))
                    ->post($url, [
                        'messaging_product' => 'whatsapp',
                        'to' => $to,
                        'type' => 'document',
                        'document' => [
                            'id' => $mediaId,
                            'filename' => $fileName
                        ]
                    ]);

                \Log::info("📡 Respuesta envío de documento:", $sendResponse->json());
                \Log::info("✅ Documento enviado exitosamente");

            } else {
                \Log::error("❌ No se pudo obtener media ID");
            }

        } catch (\Exception $e) {
            \Log::error("💥 Excepción enviando documento: " . $e->getMessage());
            \Log::error("📋 Stack trace: " . $e->getTraceAsString());
        }

        // Limpiar archivo temporal
        if (file_exists($filePath)) {
            unlink($filePath);
            \Log::info("🧹 Archivo temporal eliminado: {$filePath}");
        }
    }

    // Manejo de estado del usuario (usando cache)
    private function getUserState($userPhone)
    {
        $state = cache("whatsapp_state_{$userPhone}") ?? [];
        \Log::info("📝 Obteniendo estado del usuario {$userPhone}:", $state);
        return $state;
    }

    private function updateUserState($userPhone, $state)
    {
        \Log::info("📝 Actualizando estado del usuario {$userPhone}:", $state);
        cache(["whatsapp_state_{$userPhone}" => array_merge($this->getUserState($userPhone), $state)]);
        \Log::info("✅ Estado actualizado");
    }

    private function clearUserState($userPhone)
    {
        \Log::info("🧹 Limpiando estado del usuario {$userPhone}");
        cache()->forget("whatsapp_state_{$userPhone}");
        \Log::info("✅ Estado limpiado");
    }
}