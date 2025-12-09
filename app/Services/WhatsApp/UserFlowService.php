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
        $text = $normalizedMessage['lower'] ?? '';
        $raw = $normalizedMessage['raw'] ?? '';
        
        if (empty($text)) {
            return null;
        }
        
        // Primero verificar números específicos
        if ($text === '1' || $text === 'generar') {
            return 'generar_certificado';
        }
        
        if ($text === '2' || $text === 'consultar') {
            return 'consultar_certificados';
        }
        
        if ($text === '3' || str_contains($text, 'requisito')) {
            return 'requisitos';
        }
        
        if ($text === '4' || str_contains($text, 'soporte')) {
            return 'soporte';
        }
        
        if ($text === '5' || str_contains($text, 'autenticar') || str_contains($text, 'login') || str_contains($text, 'sesion')) {
            return 'autenticar';
        }
        
        if ($text === '6' || str_contains($text, 'registro') || str_contains($text, 'inscribir') || str_contains($text, 'registrarse')) {
            return 'registro';
        }
        
        if (str_contains($text, 'cerrar sesion') || str_contains($text, 'logout') || str_contains($text, 'salir')) {
            return 'cerrar_sesion';
        }
        
        // Comandos para menú principal
        if (str_contains($text, 'menu') || 
            str_contains($text, 'hola') || 
            str_contains($text, 'inicio') ||
            str_contains($text, 'opciones') ||
            str_contains($text, 'ayuda') ||
            $text === 'hi' || $text === 'hello') {
            return 'menu';
        }
        
        // Comando para generar certificado
        if (str_contains($text, 'generar') || 
            str_contains($text, 'certificado') ||
            str_contains($text, 'crear') ||
            str_contains($text, 'nuevo')) {
            return 'generar_certificado';
        }
        
        // Comando para consultar certificados
        if (str_contains($text, 'consultar') || 
            str_contains($text, 'ver') ||
            str_contains($text, 'listar') ||
            str_contains($text, 'historial') ||
            str_contains($text, 'anteriores') ||
            str_contains($text, 'descargar')) {
            return 'consultar_certificados';
        }
        
        return null;
    }
}