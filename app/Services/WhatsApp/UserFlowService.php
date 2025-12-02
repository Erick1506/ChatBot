<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class UserFlowService
{
    public function __construct(
        private StateService $stateService,
        private MessageService $messageService
    ) {}

    public function shouldSendWelcomeTemplate(string $userPhone): bool
    {
        $last = $this->stateService->getLastInteraction($userPhone);
        if (!$last) {
            return true;
        }
        return Carbon::now()->diffInHours($last) >= 24;
    }

    public function isTestNumber(string $userPhone): bool
    {
        $testNumbers = [
            '16315551181',
            '16505551111',
        ];
        return in_array($userPhone, $testNumbers);
    }

    public function normalizeMessage(string $message): array
    {
        $raw = trim($message);
        $lower = strtolower($raw);
        
        return [
            'raw' => $raw,
            'lower' => $lower,
            'clean' => preg_replace('/[^a-z0-9áéíóúüñ\s]/i', '', $raw)
        ];
    }

    public function detectCommand(array $normalizedMessage): ?string
    {
        $lower = $normalizedMessage['lower'];
        
        if ($lower === 'menu' || 
            str_contains($lower, 'inicio') || 
            str_contains($lower, 'hola')) {
            return 'menu';
        }
        
        if (str_contains($lower, 'generar certificado') || 
            $lower === 'generar' || 
            str_contains($lower, 'certificado')) {
            return 'generar_certificado';
        }
        
        if (str_contains($lower, 'requisitos')) {
            return 'requisitos';
        }
        
        if (str_contains($lower, 'soporte') || 
            str_contains($lower, 'ayuda') || 
            str_contains($lower, 'contacto')) {
            return 'soporte';
        }
        
        if (str_contains($lower, 'registro') || 
            str_contains($lower, 'registrarse') || 
            str_contains($lower, 'información de usuario') || 
            str_contains($lower, 'informacion de usuario')) {
            return 'registro';
        }
        
        return null;
    }
}