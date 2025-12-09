<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class StateService
{
    /**
     * Obtener estado del usuario
     */
    public function getState(string $userPhone): array
    {
        return Cache::get("wh_user_state_{$userPhone}", []);
    }

    /**
     * Actualizar estado del usuario
     */
    public function updateState(string $userPhone, array $data): void
    {
        $current = $this->getState($userPhone);
        Cache::put("wh_user_state_{$userPhone}", array_merge($current, $data), now()->addHours(24));
    }

    /**
     * Limpiar estado del usuario
     */
    public function clearState(string $userPhone): void
    {
        Cache::forget("wh_user_state_{$userPhone}");
    }

    /**
     * Obtener última interacción
     */
    public function getLastInteraction(string $userPhone): ?Carbon
    {
        $key = "wh_last_interaction_{$userPhone}";
        $val = Cache::get($key);
        return $val ? Carbon::parse($val) : null;
    }

    /**
     * Establecer última interacción
     */
    public function setLastInteraction(string $userPhone, $time = null): void
    {
        $key = "wh_last_interaction_{$userPhone}";
        Cache::put($key, ($time ?? now())->toISOString(), now()->addDays(30));
    }

    /**
     * Alias de getState (para compatibilidad)
     */
    public function getUserState(string $userPhone): array
    {
        return $this->getState($userPhone);
    }

    /**
     * Verificar si el usuario está en flujo de consulta de certificados
     */
    public function isInConsultaCertificadosFlow(string $userPhone): bool
    {
        $state = $this->getState($userPhone);
        
        if (empty($state) || !isset($state['step'])) {
            return false;
        }
        
        $step = $state['step'];
        return in_array($step, [
            'consulting_certificates',
            'selecting_certificate', 
            'confirm_download',
            'menu_consulta',
            'seleccionar_certificado',
            'confirmar_descarga'
        ]);
    }
    
    /**
     * Verificar si el usuario está en flujo de certificados
     */
    public function isInCertificateFlow(string $userPhone): bool
    {
        $state = $this->getState($userPhone);
        
        if (empty($state) || !isset($state['step'])) {
            return false;
        }
        
        $step = $state['step'];
        return in_array($step, [
            'choosing_certificate_type',
            'awaiting_ticket',
            'awaiting_year',
            'generating_certificate',
            'main_menu'
        ]);
    }
    
    /**
     * Verificar si el usuario está en flujo de autenticación
     */
    public function isInAuthFlow(string $userPhone): bool
    {
        $state = $this->getState($userPhone);
        
        if (empty($state) || !isset($state['step'])) {
            return false;
        }
        
        $step = $state['step'];
        return in_array($step, [
            'awaiting_company_code',
            'awaiting_password',
            'validating_credentials',
            'authenticated',
            'auth_username',
            'auth_password'
        ]);
    }
    
    /**
     * Limpiar estado inconsistente
     */
    public function clearInconsistentState(string $userPhone): void
    {
        $state = $this->getState($userPhone);
        
        // Si tiene authenticated=true pero falta información esencial
        if (($state['authenticated'] ?? false) === true && 
            (empty($state['empresa_nit']) || empty($state['representante_legal']))) {
            
            Log::warning("🔄 Limpiando estado inconsistente para: {$userPhone}");
            Log::warning("Estado inconsistente: " . json_encode($state));
            $this->clearState($userPhone);
        }
    }
}