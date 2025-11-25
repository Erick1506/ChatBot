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

// Ruta de prueba con más detalles del sistema
Route::get('/test', function () {
    return response()->json([
        'message' => '✅ API Laravel WhatsApp Bot funcionando correctamente',
        'timestamp' => now()->toISOString(),
        'system' => [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timezone' => config('app.timezone'),
        ],
        'endpoints' => [
            'webhook_whatsapp' => '/api/whatsapp/webhook',
            'health_check' => '/api/health',
            'validar_usuario' => '/api/usuarios/validar',
            'certificados' => '/api/certificados/{nit}'
        ]
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


// Ruta de diagnóstico para WhatsApp
Route::get('/whatsapp-debug', function() {
    $config = config('services.whatsapp');
    
    return response()->json([
        'whatsapp_configuration' => [
            'verify_token_from_config' => $config['verify_token'] ?? 'NOT_FOUND',
            'verify_token_from_env' => env('WHATSAPP_VERIFY_TOKEN'),
            'access_token_exists' => !empty($config['access_token']),
            'phone_number_id' => $config['phone_number_id'] ?? 'NOT_FOUND',
            'full_config' => $config
        ],
        'environment_check' => [
            'app_env' => app()->environment(),
            'app_url' => config('app.url'),
            'variables_loaded' => !empty(env('WHATSAPP_VERIFY_TOKEN'))
        ]
    ]);
});