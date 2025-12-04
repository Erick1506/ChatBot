<?php

use Illuminate\Support\Facades\Route;

use App\Models\Empresa;
use App\Models\CertificadoFIC;
use Carbon\Carbon;

Route::get('/preview-certificado', function () {

    // ---- DATOS DE PRUEBA ----
    $constructor = Empresa::first();
    $certificados = CertificadoFIC::limit(3)->get();
    $total = $certificados->sum('valor_pagado');
    $fecha_emision = Carbon::now();

    return view('certificados.plantilla', compact(
        'constructor',
        'certificados',
        'total',
        'fecha_emision'
    ));
});








Route::get('/', function() { 
    return response('SERVIDOR FUNCIONANDO!!, Link del chat Bot:  https://wa.me/message/5ZDQ2UBX3UHLO1', 200); });
