<?php

namespace App\Actions\WhatsApp;

use App\Services\WhatsApp\MessageService;
use App\Services\WhatsApp\StateService;
use App\Services\WhatsApp\TemplateService;
use App\Services\WhatsApp\AuthService;
use Illuminate\Support\Facades\Log;

class HandleAuthFlowAction
{
    public function __construct(
        private MessageService $messageService,
        private StateService $stateService,
        private TemplateService $templateService,
        private AuthService $authService
    ) {}

    public function startAuthentication(string $userPhone): void
    {
        Log::info("🔐 Iniciando autenticación para usuario: {$userPhone}");
        $this->messageService->sendText($userPhone, $this->templateService->getAuthPrompt());
        $this->stateService->updateState($userPhone, ['step' => 'awaiting_username']);
    }

    public function execute(string $userPhone, string $messageText, array $userState): void
    {
        Log::info("=== HANDLE AUTH FLOW INICIADO ===");
        Log::info("Paso actual: " . ($userState['step'] ?? 'none'));
        Log::info("Mensaje: {$messageText}");

        $step = $userState['step'] ?? '';

        switch ($step) {
            case 'awaiting_username':
                Log::info("👤 Usuario ingresando username: {$messageText}");
                $this->processUsername($userPhone, $messageText);
                break;

            case 'awaiting_password':
                Log::info("🔐 Usuario ingresando password");
                $this->processPassword($userPhone, $messageText, $userState);
                break;

            default:
                Log::info("🔀 Estado de auth no reconocido, reiniciando");
                $this->startAuthentication($userPhone);
                break;
        }
    }

    private function processUsername(string $userPhone, string $username): void
    {
        $empresa = $this->authService->validateUsername($username);

        if (!$empresa) {
            $this->messageService->sendText($userPhone, $this->templateService->getUserNotFound());
            $this->stateService->clearState($userPhone);
            return;
        }

        $message = "✅ Usuario encontrado.\n\n";
        $message .= "👤 *" . $empresa->representante_legal . "*\n\n";
        $message .= "Ahora ingresa tu *CONTRASEÑA*:";

        $this->messageService->sendText($userPhone, $message);
        $this->stateService->updateState($userPhone, [
            'step' => 'awaiting_password',
            'username' => $username,
            'empresa_id' => $empresa->id,
            'nit' => $empresa->nit
        ]);
    }

    private function processPassword(string $userPhone, string $password, array $userState): void
    {
        $username = $userState['username'] ?? null;

        if (!$username) {
            Log::error("❌ No se encontró username en el estado");
            $this->messageService->sendText($userPhone, $this->templateService->getErrorSystem());
            $this->stateService->clearState($userPhone);
            return;
        }

        $empresa = $this->authService->validateUsername($username);

        if (!$empresa) {
            Log::error("❌ Empresa no encontrada para usuario: {$username}");
            $this->messageService->sendText($userPhone, $this->templateService->getErrorSystem());
            $this->stateService->clearState($userPhone);
            return;
        }

        if (!$this->authService->validatePassword($empresa, $password)) {
            $this->messageService->sendText($userPhone, $this->templateService->getWrongPassword());
            $this->stateService->updateState($userPhone, [
                'step' => 'awaiting_username',
                'username' => null
            ]);
            return;
        }

        Log::info("✅ Autenticación exitosa para: " . $empresa->representante_legal);

        $this->messageService->sendText($userPhone, $this->templateService->getAuthSuccess($empresa->representante_legal, $empresa->nit));
        $this->messageService->sendText($userPhone, $this->templateService->getCertificateOptions());

        $this->stateService->updateState($userPhone, [
            'step' => 'choosing_certificate_type',
            'authenticated' => true,
            'empresa_nit' => $empresa->nit,
            'representante_legal' => $empresa->representante_legal
        ]);
    }
}