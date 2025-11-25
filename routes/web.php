<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() { return response('Link del chat Bot:  https://wa.me/message/5ZDQ2UBX3UHLO1', 200); });


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


Route::get('/whatsapp/debug', function() {
    return response()->json([
        'token_exists' => !empty(config('services.whatsapp.access_token')),
        'token_length' => strlen(config('services.whatsapp.access_token')),
        'phone_id_exists' => !empty(config('services.whatsapp.phone_number_id')),
        'verify_token_exists' => !empty(config('services.whatsapp.verify_token')),
        'app_url' => config('app.url')
    ]);
});


