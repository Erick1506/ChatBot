<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class StateService
{
    public function getState(string $userPhone): array
    {
        return Cache::get("wh_user_state_{$userPhone}", []);
    }

    public function updateState(string $userPhone, array $data): void
    {
        $current = $this->getState($userPhone);
        Cache::put("wh_user_state_{$userPhone}", array_merge($current, $data), now()->addHours(24));
    }

    public function clearState(string $userPhone): void
    {
        Cache::forget("wh_user_state_{$userPhone}");
    }

    public function getLastInteraction(string $userPhone): ?Carbon
    {
        $key = "wh_last_interaction_{$userPhone}";
        $val = Cache::get($key);
        return $val ? Carbon::parse($val) : null;
    }

    public function setLastInteraction(string $userPhone, $time = null): void
    {
        $key = "wh_last_interaction_{$userPhone}";
        Cache::put($key, ($time ?? now())->toISOString(), now()->addDays(30));
    }

    public function getUserState(string $userPhone): array
    {
        return $this->getState($userPhone);
    }

    public function isInAuthFlow(string $userPhone): bool
    {
        $state = $this->getState($userPhone);
        $authSteps = ['awaiting_username', 'awaiting_password'];
        return !empty($state['step']) && in_array($state['step'], $authSteps);
    }

    public function isInCertificateFlow(string $userPhone): bool
    {
        $state = $this->getState($userPhone);
        $certificateSteps = [
            'choosing_certificate_type',
            'awaiting_nit_ticket',
            'awaiting_ticket',
            'awaiting_nit_general',
            'awaiting_nit_vigencia',
            'awaiting_year'
        ];
        return !empty($state['step']) && in_array($state['step'], $certificateSteps);
    }
}