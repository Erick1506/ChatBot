<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificadoFICController; 
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\EmpresaController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// =========================================================================
// WEBHOOK DE WHATSAPP (Sin autenticación - acceso público)
// =========================================================================
Route::prefix('whatsapp')->group(function () {
    Route::get('webhook', [WhatsAppController::class, 'verifyWebhook']);
    Route::post('webhook', [WhatsAppController::class, 'webhook']);
});

// =========================================================================
// RUTAS DE USUARIOS/EMPRESAS
// =========================================================================
Route::prefix('usuarios')->group(function () {
    Route::post('validar', [EmpresaController::class, 'validarCredenciales']);
    Route::post('', [EmpresaController::class, 'crear']);
    Route::get('{nit}', [EmpresaController::class, 'obtenerPorNit']);
    Route::put('{nit}', [EmpresaController::class, 'actualizar']);
});

// =========================================================================
// RUTAS DE HEALTH CHECK PARA RENDER (Sin autenticación)
// =========================================================================

// Health check básico para Render
Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'timestamp' => now()->toISOString(),
        'service' => 'Laravel WhatsApp Bot - FIC SENA',
        'environment' => app()->environment(),
        'version' => '1.0.0'
    ]);
});


// Ruta para verificar la configuración de WhatsApp
Route::get('/whatsapp/config', function () {
    return response()->json([
        'whatsapp_configured' => !empty(config('services.whatsapp.access_token')),
        'phone_number_id' => config('services.whatsapp.phone_number_id'),
        'verify_token' => config('services.whatsapp.verify_token'),
        'has_access_token' => !empty(config('services.whatsapp.access_token'))
    ]);
});


