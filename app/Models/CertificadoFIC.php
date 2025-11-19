<?php
// app/Models/CertificadoFIC.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificadoFIC extends Model
{
    protected $table = 'certificados_fic';
    
    protected $fillable = [
        'numero_orden',
        'licencia_contrato',
        'nombre_obra',
        'ciudad_ejecucion',
        'valor_pago',
        'periodo',
        'fecha',
        'ticket',
        'constructor_nit',
        'constructor_razon_social',
        'codigo_verificacion',
        'numero_certificado',
        'regional_sena',
        'generado_por'
    ];
    
    protected $casts = [
        'fecha' => 'date',
        'valor_pago' => 'decimal:2'
    ];
    
    // Scope para búsqueda por NIT
    public function scopePorNit($query, $nit)
    {
        return $query->where('constructor_nit', $nit);
    }
    
    // Scope para búsqueda por ticket
    public function scopePorTicket($query, $ticket)
    {
        return $query->where('ticket', $ticket);
    }
    
    // Scope para búsqueda por vigencia (año del periodo)
    public function scopePorVigencia($query, $year)
    {
        return $query->where('periodo', 'like', $year . '-%');
    }
    
    // Scope para búsqueda por periodo exacto
    public function scopePorPeriodo($query, $periodo)
    {
        return $query->where('periodo', $periodo);
    }
    
    // Scope para búsqueda por año de fecha
    public function scopePorAnioFecha($query, $year)
    {
        return $query->whereYear('fecha', $year);
    }
}