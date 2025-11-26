<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HandleWhatsappWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        // extraer con seguridad
        $entry = $this->payload['entry'][0] ?? null;
        $change = $entry['changes'][0] ?? null;
        $value = $change['value'] ?? null;
        $messages = $value['messages'] ?? null;
        $metadata = $value['metadata'] ?? ($value['contacts'][0]['wa_id'] ?? null);

        if (! $messages || ! is_array($messages)) {
            Log::info('No hay mensajes procesables en payload', $this->payload);
            return;
        }

        $message = $messages[0];
        $from = $message['from'] ?? null;
        $text = $message['text']['body'] ?? null;
        $phone_number_id = $value['metadata']['phone_number_id'] ?? env('WHATSAPP_PHONE_NUMBER_ID');

        if (! $from || ! $text) {
            Log::warning('Faltan campos from/text', ['message' => $message]);
            return;
        }

        Log::info("Procesando mensaje de {$from}: {$text}");

        // Aquí pones la lógica de tu bot: respuestas automáticas, consultas, etc.
        $reply = "Recibí: " . substr($text, 0, 100); // ejemplo simple

        // Llamada a Graph API
        $token = env('WHATSAPP_ACCESS_TOKEN');
        $url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";

        $body = [
            "messaging_product" => "whatsapp",
            "to" => $from,
            "text" => ["body" => $reply]
        ];

        $response = Http::withToken($token)
            ->post($url, $body);

        if ($response->failed()) {
            Log::error('Error al enviar mensaje a Graph API', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        } else {
            Log::info('Respuesta enviada correctamente', ['to' => $from, 'body' => $response->body()]);
        }
    }
}
