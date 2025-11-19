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
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Verifica que coincida con tu verify token
        if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    // Recibir mensajes de WhatsApp
    public function webhook(Request $request)
    {
        $data = $request->all();

        \Log::info('WhatsApp Webhook recibido:', $data);

        // Verificar que es un mensaje válido
        if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            $userPhone = $message['from']; // Número del usuario
            $messageText = $message['text']['body'] ?? '';

            // Procesar el mensaje
            $this->processMessage($userPhone, $messageText);
        }

        return response('OK', 200);
    }

    private function processMessage($userPhone, $messageText)
    {
        $messageText = strtolower(trim($messageText));
        
        // Obtener o inicializar el estado del usuario
        $userState = $this->getUserState($userPhone);

        // Lógica del chatbot
        if ($messageText === 'hola' || $messageText === 'inicio' || $messageText === 'menu') {
            $this->sendWelcomeMessage($userPhone);
            $this->updateUserState($userPhone, ['step' => 'main_menu']);
            return;
        }

        if ($messageText === '1' || str_contains($messageText, 'generar certificado')) {
            $this->sendCertificateOptions($userPhone);
            $this->updateUserState($userPhone, ['step' => 'choosing_certificate_type']);
            return;
        }

        if ($messageText === '2' || str_contains($messageText, 'requisitos')) {
            $this->sendRequirements($userPhone);
            return;
        }

        if ($messageText === '3' || str_contains($messageText, 'soporte')) {
            $this->sendSupportInfo($userPhone);
            return;
        }

        // Manejar flujo de generación de certificados
        $this->handleCertificateFlow($userPhone, $messageText, $userState);
    }

    private function handleCertificateFlow($userPhone, $messageText, $userState)
    {
        switch ($userState['step'] ?? '') {
            case 'choosing_certificate_type':
                if ($messageText === '1' || str_contains($messageText, 'ticket')) {
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_nit_ticket',
                        'type' => 'ticket'
                    ]);
                    $this->sendMessage($userPhone, "🪪 *Certificado por TICKET*\n\nPor favor ingresa el NIT de la empresa:");
                } elseif ($messageText === '2' || str_contains($messageText, 'nit')) {
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_nit_general',
                        'type' => 'nit'
                    ]);
                    $this->sendMessage($userPhone, "🏢 *Certificado por NIT*\n\nIngresa el NIT o cédula del empresario:");
                } elseif ($messageText === '3' || str_contains($messageText, 'vigencia')) {
                    $this->updateUserState($userPhone, [
                        'step' => 'awaiting_nit_vigencia',
                        'type' => 'vigencia'
                    ]);
                    $this->sendMessage($userPhone, "📅 *Certificado por VIGENCIA*\n\nPrimero ingresa el NIT o cédula del empresario:");
                } else {
                    $this->sendCertificateOptions($userPhone);
                }
                break;

            case 'awaiting_nit_ticket':
                $this->updateUserState($userPhone, [
                    'step' => 'awaiting_ticket',
                    'nit' => $messageText
                ]);
                $this->sendMessage($userPhone, "🎫 Ahora ingresa el número de *TICKET*:");
                break;

            case 'awaiting_ticket':
                $userState = $this->getUserState($userPhone);
                $this->generateAndSendCertificate($userPhone, 'nit_ticket', [
                    'nit' => $userState['nit'],
                    'ticket' => $messageText
                ]);
                break;

            case 'awaiting_nit_general':
                $this->generateAndSendCertificate($userPhone, 'nit_general', [
                    'nit' => $messageText
                ]);
                break;

            case 'awaiting_nit_vigencia':
                $this->updateUserState($userPhone, [
                    'step' => 'awaiting_year',
                    'nit' => $messageText
                ]);
                $this->sendMessage($userPhone, "📋 Ingresa el *AÑO* de la vigencia:\n\nEjemplo: 2025\n\nSolo se permiten 15 años atrás desde el actual.");
                break;

            case 'awaiting_year':
                $userState = $this->getUserState($userPhone);
                $year = intval($messageText);
                $currentYear = date('Y');

                if ($year > $currentYear || $year < ($currentYear - 15)) {
                    $this->sendMessage($userPhone, "❌ *Año fuera de rango*\n\nSolo se permiten vigencias entre " . ($currentYear - 15) . " y $currentYear.");
                    return;
                }

                $this->generateAndSendCertificate($userPhone, 'nit_vigencia', [
                    'nit' => $userState['nit'],
                    'vigencia' => $year
                ]);
                break;

            default:
                $this->sendWelcomeMessage($userPhone);
                break;
        }
    }

    private function generateAndSendCertificate($userPhone, $type, $data)
    {
        try {
            $this->sendMessage($userPhone, "⏳ *Generando certificado...*\n\nPor favor espera unos segundos.");

            // Buscar certificados
            $certificados = $this->buscarCertificados($type, $data['nit'], $data['ticket'] ?? null, $data['vigencia'] ?? null);

            if ($certificados->isEmpty()) {
                $this->sendMessage($userPhone, "❌ *No se encontraron certificados*\n\nNo hay certificados con los criterios especificados.");
                $this->clearUserState($userPhone);
                return;
            }

            // Generar PDF
            $pdfPath = $this->generarPdf($certificados, $type);

            // Enviar PDF por WhatsApp
            $this->sendDocument($userPhone, $pdfPath, $this->generarNombreArchivo($certificados->first(), $type));

            $this->sendMessage($userPhone, "✅ *Certificado generado exitosamente!*\n\nTu certificado FIC ha sido generado y enviado.");
            
            // Ofrecer volver al menú
            $this->sendMessage($userPhone, "¿Necesitas algo más? Escribe *MENU* para ver las opciones.");

            $this->clearUserState($userPhone);

        } catch (\Exception $e) {
            \Log::error('Error generando certificado WhatsApp: ' . $e->getMessage());
            $this->sendMessage($userPhone, "❌ *Error del sistema*\n\nPor favor intenta nuevamente o contacta a soporte.");
            $this->clearUserState($userPhone);
        }
    }

    // Métodos auxiliares (reutilizar los que ya tenemos)
    private function buscarCertificados($tipo, $nit, $ticket = null, $vigencia = null)
    {
        $query = CertificadoFIC::where('constructor_nit', $nit);
        
        switch ($tipo) {
            case 'nit_ticket':
                return $query->where('ticket', $ticket)->get();
            case 'nit_vigencia':
                return $query->where('periodo', 'like', $vigencia . '-%')->get();
            case 'nit_general':
            default:
                return $query->get();
        }
    }

    private function generarPdf($certificados, $tipo)
    {
        $constructor = $certificados->first();
        $total = $certificados->sum('valor_pago');
        
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
        
        // Asegurar que existe el directorio
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }
        
        $pdf->save($filePath);
        
        return $filePath;
    }

    private function generarNombreArchivo($constructor, $tipo)
    {
        $fecha = now()->format('Y-m-d');
        $nit = $constructor->constructor_nit;
        return "Certificado_FIC_{$nit}_{$tipo}_{$fecha}.pdf";
    }

    // Mensajes predefinidos
    private function sendWelcomeMessage($userPhone)
    {
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
        $message = "📋 *REQUISITOS PARA CERTIFICADOS FIC*\n\n";
        $message .= "• NIT o Cédula del empresario\n";
        $message .= "• Tipo de certificado (Ticket, NIT o Vigencia)\n";
        $message .= "• Para vigencia: año específico (máx. 15 años atrás)\n\n";
        $message .= "Escribe *MENU* para volver al inicio.";

        $this->sendMessage($userPhone, $message);
    }

    private function sendSupportInfo($userPhone)
    {
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
        $url = 'https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/messages';

        $response = Http::withToken(config('services.whatsapp.access_token'))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'text' => ['body' => $message]
            ]);

        \Log::info('Respuesta WhatsApp:', $response->json());
    }

    private function sendDocument($to, $filePath, $fileName)
    {
        $url = 'https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/messages';

        // Subir el archivo a WhatsApp
        $mediaResponse = Http::withToken(config('services.whatsapp.access_token'))
            ->attach('file', file_get_contents($filePath), $fileName)
            ->post('https://graph.facebook.com/v17.0/' . config('services.whatsapp.phone_number_id') . '/media', [
                'messaging_product' => 'whatsapp',
                'type' => 'document/pdf'
            ]);

        $mediaId = $mediaResponse->json()['id'];

        // Enviar el documento
        Http::withToken(config('services.whatsapp.access_token'))
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'document',
                'document' => [
                    'id' => $mediaId,
                    'filename' => $fileName
                ]
            ]);

        // Limpiar archivo temporal
        unlink($filePath);
    }

    // Manejo de estado del usuario (usando cache)
    private function getUserState($userPhone)
    {
        return cache("whatsapp_state_{$userPhone}") ?? [];
    }

    private function updateUserState($userPhone, $state)
    {
        cache(["whatsapp_state_{$userPhone}" => array_merge($this->getUserState($userPhone), $state)]);
    }

    private function clearUserState($userPhone)
    {
        cache()->forget("whatsapp_state_{$userPhone}");
    }
}