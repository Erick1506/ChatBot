<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() { 
    return response('SERVIDOR FUNCIONANDO!!, Link del chat Bot:  https://wa.me/message/5ZDQ2UBX3UHLO1', 200); });
