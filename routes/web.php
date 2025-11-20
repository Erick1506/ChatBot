<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/login', function () {
    return view('login'); //resources/views/login.blade.php
});


// PRUEBA DE CONEXION A LA BD
Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return [
            'status' => 'success',
            'message' => 'Conexión exitosa a la BD',
            'database' => DB::connection()->getDatabaseName()
        ];
    } catch (\Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
});


Route::post('/generar-certificado', [CertificadoFICController::class, 'generarCertificado']);


Route::get('/debug-whatsapp', function() {
    return [
        'verify_token' => config('services.whatsapp.verify_token'),
        'phone_number_id' => config('services.whatsapp.phone_number_id'),
        'access_token_length' => strlen(config('services.whatsapp.access_token')),
        'app_url' => config('app.url')
    ];
});