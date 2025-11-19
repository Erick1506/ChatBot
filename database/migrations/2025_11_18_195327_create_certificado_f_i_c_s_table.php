<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_certificados_fic_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('certificados_fic', function (Blueprint $table) {
            $table->id();
            $table->integer('numero_orden');
            $table->string('licencia_contrato')->nullable();
            $table->string('nombre_obra');
            $table->string('ciudad_ejecucion');
            $table->decimal('valor_pago', 15, 2)->default(0);
            $table->string('periodo'); // 2025-01
            $table->date('fecha');
            $table->string('ticket');
            $table->timestamps();
            
            // Índices
            $table->index('numero_orden');
            $table->index('periodo');
            $table->index('ticket');
            $table->index('fecha');
        });
    }

    public function down()
    {
        Schema::dropIfExists('certificados_fic');
    }
};