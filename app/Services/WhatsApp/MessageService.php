<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessageService
{
    public function __construct(
        private StateService $stateService
    ) {}

    public function sendText(string $to, string $message): bool
    {
        Log::info("✉️ ENVIANDO MENSAJE - Para: {$to}");
        Log::info("📝 Mensaje: {$message}");

        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración de WhatsApp incompleta. Revisa services.whatsapp.phone_number_id y access_token');
            return false;
        }

        $url = 'https://graph.facebook.com/v24.0/' . $phoneNumberId . '/messages';

        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'text' => ['body' => $message]
                ]);

            Log::info("📡 Respuesta HTTP Status: " . $response->status());

            if ($response->successful()) {
                Log::info("✅ Mensaje enviado exitosamente a {$to}");
                $this->stateService->setLastInteraction($to);
                $this->logOutboundMessage($to, $message, $response->json());
                return true;
            } else {
                Log::error("❌ Error enviando mensaje: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción enviando mensaje: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function sendTemplate(string $to, string $templateName, string $languageCode = 'es_CO'): bool
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración de WhatsApp incompleta para sendTemplate');
            return false;
        }

        $url = "https://graph.facebook.com/v24.0/{$phoneNumberId}/messages";

        $body = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode]
            ]
        ];

        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($url, $body);

            Log::info("📡 sendTemplate status: " . $response->status());

            if ($response->successful()) {
                $this->stateService->setLastInteraction($to);
                return true;
            } else {
                Log::error("❌ sendTemplate failed: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción en sendTemplate: " . $e->getMessage());
            return false;
        }
    }

    public function sendDocument(string $to, string $filePath, string $fileName): bool
    {
        Log::info("📎 ENVIANDO DOCUMENTO - Para: {$to}, Archivo: {$fileName}");
        Log::info("📁 Ruta del archivo: {$filePath}");

        $phoneNumberId = config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID');
        $accessToken = config('services.whatsapp.access_token') ?? env('WHATSAPP_TOKEN');

        if (empty($phoneNumberId) || empty($accessToken)) {
            Log::error('❌ Configuración de WhatsApp incompleta. Revisa services.whatsapp.phone_number_id y access_token');
            return false;
        }

        try {
            // Subir media a Meta
            $mediaResponse = Http::withToken($accessToken)
                ->attach('file', file_get_contents($filePath), $fileName)
                ->post('https://graph.facebook.com/v24.0/' . $phoneNumberId . '/media', [
                    'messaging_product' => 'whatsapp',
                    'type' => 'document/pdf'
                ]);

            Log::info("📡 Respuesta subida de archivo:", $mediaResponse->json());

            if (!isset($mediaResponse->json()['id'])) {
                Log::error("❌ No se pudo obtener media ID");
                return false;
            }

            $mediaId = $mediaResponse->json()['id'];
            Log::info("🆔 Media ID obtenido: {$mediaId}");

            // Enviar documento usando media ID
            $url = 'https://graph.facebook.com/v24.0/' . $phoneNumberId . '/messages';
            $sendResponse = Http::withToken($accessToken)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'document',
                    'document' => [
                        'id' => $mediaId,
                        'filename' => $fileName
                    ]
                ]);

            Log::info("📡 Respuesta envío de documento:", $sendResponse->json());
            
            if ($sendResponse->successful()) {
                Log::info("✅ Documento enviado exitosamente");
                $this->stateService->setLastInteraction($to);
                $this->logOutboundMessage($to, '[document] ' . $fileName, $sendResponse->json());
                return true;
            } else {
                Log::error("❌ Error al enviar documento: " . $sendResponse->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("💥 Excepción enviando documento: " . $e->getMessage());
            Log::error("📋 Stack trace: " . $e->getTraceAsString());
            return false;
        } finally {
            if (file_exists($filePath)) {
                unlink($filePath);
                Log::info("🧹 Archivo temporal eliminado: {$filePath}");
            }
        }
    }

    private function logOutboundMessage(string $to, string $message, array $response): void
    {
        try {
            if (class_exists(\App\Models\WhatsappMessage::class)) {
                \App\Models\WhatsappMessage::create([
                    'message_id' => $response['messages'][0]['id'] ?? null,
                    'from_number' => config('services.whatsapp.phone_number_id') ?? env('WHATSAPP_PHONE_ID'),
                    'to_phone_number_id' => $to,
                    'direction' => 'outbound',
                    'message' => $message,
                    'payload' => json_encode($response)
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo guardar outbound en BD: ' . $e->getMessage());
        }
    }
}