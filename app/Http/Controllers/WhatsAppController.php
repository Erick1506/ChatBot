<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookService;

class WhatsAppController extends Controller
{
    public function __construct(
        private WebhookService $webhookService
    ) {}

    // Verificar el webhook (Meta GET challenge)
    public function verifyWebhook(Request $request)
    {
        return $this->webhookService->verify($request);
    }

    // Recibir mensajes y statuses desde Meta
    public function webhook(Request $request)
    {
        return $this->webhookService->handle($request);
    }
}