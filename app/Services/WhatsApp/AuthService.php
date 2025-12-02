<?php

namespace App\Services\WhatsApp;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;

class AuthService
{
    public function validateUsername(string $username): ?Empresa
    {
        Log::info("🔍 Buscando usuario en BD: {$username}");
        $empresa = Empresa::buscarPorUsuario($username);
        
        if (!$empresa) {
            Log::warning("❌ Usuario no encontrado: {$username}");
        } else {
            Log::info("✅ Usuario encontrado: " . $empresa->representante_legal);
        }
        
        return $empresa;
    }

    public function validatePassword(Empresa $empresa, string $password): bool
    {
        return $empresa->verificarContraseña($password);
    }

    public function getCompanyById(int $id): ?Empresa
    {
        return Empresa::find($id);
    }
}