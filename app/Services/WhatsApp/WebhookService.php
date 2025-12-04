<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Actions\WhatsApp\ProcessMessageAction;

class WebhookService
{
    public function __construct(
        private ProcessMessageAction $processMessageAction
    ) {}

    public function verify(Request $request)
    {
        Log::info('🔐 === WHATSAPP WEBHOOK VERIFICATION STARTED ===');
        Log::info('Raw query string: ' . $request->getQueryString());

        $mode = $request->query('hub_mode') ?? $request->query('hub.mode') ?? $request->get('hub.mode');
        $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token') ?? $request->get('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge') ?? $request->get('hub.challenge');

        $expectedToken = env('WHATSAPP_VERIFY_TOKEN', 'chatbotwhatsapp');

        Log::info("Parsed verify values: mode=" . var_export($mode, true) .
                  ", token=" . var_export($token, true) .
                  ", challenge=" . var_export($challenge, true));

        if (! $mode || ! $token || ! $challenge) {
            Log::error('❌ Faltan parámetros requeridos en verificación de webhook', $request->query());
            return response('Bad Request - Missing parameters', 400);
        }

        if ($mode !== 'subscribe') {
            Log::warning("❌ Modo incorrecto. Esperado: 'subscribe', Recibido: '{$mode}'");
            return response('Forbidden - Invalid mode', 403);
        }

        if (strcasecmp(trim($token), trim($expectedToken)) !== 0) {
            Log::warning('❌ Token de verificación incorrecto', ['received' => $token, 'expected' => $expectedToken]);
            return response('Forbidden - Token mismatch', 403);
        }

        Log::info('✅ WEBHOOK VERIFICADO EXITOSAMENTE! Devolviendo challenge: ' . $challenge);
        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function handle(Request $request)
    {
        Log::info('=== WEBHOOK INICIADO ===');

        $rawBody = $request->getContent();
        Log::info('📨 Raw body recibido: ' . $rawBody);

        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('❌ Error decodificando JSON: ' . json_last_error_msg());
            $data = $request->all();
            if (empty($data)) {
                Log::info('=== WEBHOOK FINALIZADO (ERROR JSON) ===');
                return response('Error en JSON', 400);
            }
        }

        Log::info('Webhook data recibida:', $data);

        // ✅ IMPORTANTE: Detectar y filtrar eventos duplicados de estado
        $entry = $data['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        
        // Si es evento de estado, ignorar inmediatamente
        if (isset($changes['value']['statuses'])) {
            Log::info('🔔 Status event recibido, ignorando.');
            return response()->json(['status' => 'ignored_status'], 200);
        }
        
        // Verificar que haya mensajes
        if (!isset($changes['value']['messages'])) {
            Log::info('📭 No hay mensajes en el webhook');
            return response()->json(['status' => 'no_messages'], 200);
        }
        
        $message = $changes['value']['messages'][0] ?? null;
        if (!$message) {
            Log::info('📭 Mensaje no encontrado en estructura');
            return response()->json(['status' => 'no_message_data'], 200);
        }

        // Extraer mensaje
        $messageData = $this->extractMessageData($data);
        if (!$messageData) {
            Log::warning('❌ No se encontró mensaje en el webhook');
            return response('No message found', 200);
        }

        // ✅ Agregar validación contra duplicados
        $messageId = $message['id'] ?? null;
        if ($messageId) {
            $cacheKey = 'whatsapp_msg_' . $messageId;
            if (cache()->has($cacheKey)) {
                Log::info('🔄 Mensaje duplicado detectado, ignorando: ' . $messageId);
                return response()->json(['status' => 'duplicate_ignored'], 200);
            }
            // Almacenar por 30 segundos
            cache()->put($cacheKey, true, 30);
        }

        // Procesar mensaje
        try {
            $this->processMessageAction->execute($messageData);
        } catch (\Exception $e) {
            Log::error('❌ Error procesando mensaje: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            // NO devuelvas error 500, devuelve 200 para que WhatsApp no reintente
            return response()->json(['status' => 'error_handled'], 200);
        }

        return response('Mensaje recibido', 200);
    }

    private function extractMessageData(array $data): ?array
    {
        $message = null;
        if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            Log::info('✅ Mensaje encontrado en estructura estándar');
        } elseif (isset($data['entry'][0]['changes'][0]['value']['message'])) {
            $message = $data['entry'][0]['changes'][0]['value']['message'];
            Log::info('✅ Mensaje encontrado en estructura alternativa (message)');
        } elseif (isset($data['entry'][0]['messaging'][0]['message'])) {
            $message = $data['entry'][0]['messaging'][0]['message'];
            Log::info('✅ Mensaje encontrado en estructura messaging');
        } elseif (isset($data['messages'][0])) {
            $message = $data['messages'][0];
            Log::info('✅ Mensaje encontrado en raíz messages');
        }

        if (!$message) {
            return null;
        }

        $rawFrom = $message['from'] ?? $message['wa_id'] ?? '';
        $userPhone = preg_replace('/\D+/', '', $rawFrom);

        $messageText = '';
        if (isset($message['text']['body'])) {
            $messageText = $message['text']['body'];
        } elseif (!empty($message['button']) && isset($message['button']['text'])) {
            $messageText = $message['button']['text'];
        } elseif (!empty($message['interactive'])) {
            $interactive = $message['interactive'];
            if (isset($interactive['button_reply']['title'])) {
                $messageText = $interactive['button_reply']['title'];
            } elseif (isset($interactive['list_reply']['title'])) {
                $messageText = $interactive['list_reply']['title'];
            }
        } elseif (isset($message['body'])) {
            $messageText = $message['body'];
        }

        Log::info("📱 Mensaje recibido - De: {$userPhone}, Texto: {$messageText}");

        if (empty($userPhone) || $messageText === '') {
            return null;
        }

        return [
            'userPhone' => $userPhone,
            'messageText' => $messageText,
            'messageData' => $message
        ];
    }
}