<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CertificadoFICController; // ← AÑADIR ESTE IMPORT

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para el chatbot - SIN middleware de autenticación
Route::post('/chatbot/generar-certificado', [CertificadoFICController::class, 'apiGenerarCertificado']);