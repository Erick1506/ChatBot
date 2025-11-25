<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificadoFICController; 
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\EmpresaController;



Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});




// Webhook de WhatsApp
Route::get('/whatsapp/webhook', [WhatsAppController::class, 'verifyWebhook']);
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);


// Rutas de usuarios
Route::post('/usuarios/validar', [EmpresaController::class, 'validarCredenciales']);
Route::post('/usuarios', [EmpresaController::class, 'crear']);
Route::get('/usuarios/{nit}', [EmpresaController::class, 'obtenerPorNit']);
Route::put('/usuarios/{nit}', [EmpresaController::class, 'actualizar']);