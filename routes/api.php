<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificadoFICController; 
use App\Http\Controllers\WhatsAppController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para el chatbot - SIN middleware de autenticación
Route::post('/chatbot/generar-certificado', [CertificadoFICController::class, 'apiGenerarCertificado']);



// Webhook de WhatsApp
Route::get('/whatsapp/webhook', [WhatsAppController::class, 'verifyWebhook']);
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);

Route::get('/test-whatsapp-send', function() {
    $whatsapp = new App\Http\Controllers\WhatsAppController();
    
    // Reemplaza con TU número de WhatsApp real (con código de país)
    $testPhone = '573014368807'; // Ejemplo: Colombia
    
    $whatsapp->sendMessage($testPhone, "🤖 *TEST BOT* \n\nEste es un mensaje de prueba desde tu servidor Laravel!");
    
    return response()->json(['status' => 'Mensaje enviado']);
});