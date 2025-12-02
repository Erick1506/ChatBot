<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsApp\WebhookService;
use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\UserFlowService;
use App\Actions\WhatsApp\ProcessMessageAction;

class WhatsAppController extends Controller
{
    private WebhookService $webhookService;
    
    public function __construct()
    {
        // Crear todas las instancias manualmente
        $stateService = new StateService();
        $templateService = new TemplateService();
        $messageService = new MessageService($stateService);
        $userFlowService = new UserFlowService($stateService, $messageService);
        
        // Crear ProcessMessageAction con sus dependencias
        $processMessageAction = new ProcessMessageAction(
            $messageService,
            $stateService,
            $templateService,
            $userFlowService
        );
        
        // Crear WebhookService con ProcessMessageAction
        $this->webhookService = new WebhookService($processMessageAction);
    }
    
    public function verifyWebhook(Request $request)
    {
        return $this->webhookService->verify($request);
    }
    
    public function webhook(Request $request)
    {
        return $this->webhookService->handle($request);
    }
}