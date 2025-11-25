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
// RUTAS DE CERTIFICADOS FIC
// =========================================================================
Route::prefix('certificados')->group(function () {
    Route::get('{nit}', [CertificadoFICController::class, 'obtenerPorNit']);
    Route::get('ticket/{ticket}', [CertificadoFICController::class, 'obtenerPorTicket']);
    Route::get('vigencia/{nit}/{year}', [CertificadoFICController::class, 'obtenerPorVigencia']);
    Route::post('generar-pdf', [CertificadoFICController::class, 'generarPDF']);
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

// Ruta temporal de diagnóstico del webhook
Route::get('/webhook-test', function (Request $request) {
    $testToken = 'chatbotwhatsapp';
    $testUrl = url('/api/whatsapp/webhook') . '?hub.mode=subscribe&hub.verify_token=' . $testToken . '&hub.challenge=123';
    
    return response()->json([
        'diagnostic' => [
            'app_url' => config('app.url'),
            'current_time' => now()->toISOString(),
            'webhook_test_url' => $testUrl,
            'expected_token' => $testToken,
            'should_return' => '123'
        ],
        'instructions' => 'Copy the webhook_test_url and test it in browser or curl'
    ]);
});