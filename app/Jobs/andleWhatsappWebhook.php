<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappMessage;
use App\Http\Controllers\WhatsAppController;

class HandleWhatsappWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    // control de reintentos/backoff
    public $tries = 3;
    public $backoff = [5, 30, 120]; // segundos

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        $entry = $this->payload['entry'][0] ?? null;
        $change = $entry['changes'][0] ?? null;
        $value = $change['value'] ?? null;
        $messages = $value['messages'] ?? null;
        $metadata = $value['metadata'] ?? null;

        if (! $messages || ! is_array($messages)) {
            Log::info('No hay mensajes procesables en payload', $this->payload);
            return;
        }

        $message = $messages[0];
        $from = preg_replace('/\D+/', '', $message['from'] ?? '');
        $text = $message['text']['body'] ?? null;
        $messageId = $message['id'] ?? null;
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? env('WHATSAPP_PHONE_NUMBER_ID');

        if (! $from || $text === null) {
            Log::warning('Faltan campos from/text', ['message' => $message]);
            return;
        }

        // Dedupe adicional
        if ($messageId && WhatsappMessage::where('message_id', $messageId)->exists()) {
            Log::info("Mensaje ya procesado (job dedupe): {$messageId}");
            return;
        }

        Log::info("Procesando mensaje (job) de {$from}: {$text}");

        // ---- Ejecutar la lógica principal del bot: reutiliza el método processMessage del controller ----
        // Inyectar (resolver) controller y llamar a processMessage para mantener tu lógica original
        try {
            $controller = app()->make(WhatsAppController::class);
            // processMessage espera ($userPhone, $messageText)
            $controller->processMessage($from, $text);
        } catch (\Throwable $e) {
            Log::error("Exception al ejecutar processMessage: " . $e->getMessage());
            // dejar que la cola reintente si es error servidor
            throw $e;
        }

        // Guardar outbound(s) generadas: el controller ya crea los envíos mediante sendMessage() que registra logs,
        // pero queremos guardar en BD cuando Graph API retorne el id. Para eso, si tu función sendMessage guarda en BD,
        // entonces aquí no es necesario guardar nuevamente. Si no, podríamos capturar la respuesta; para simplicidad,
        // asumimos que sendMessage guarda outbound en BD cuando es exitosa.
    }

    public function failed(\Throwable $exception)
    {
        Log::error('HandleWhatsappWebhook failed: ' . $exception->getMessage());
    }
}
