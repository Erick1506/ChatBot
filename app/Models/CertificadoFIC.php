<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificadoFIC extends Model
{
    protected $table = 'certificados_fic';
    
    protected $fillable = [
        'numero_orden',
        'NRO_LICENCIA_CONTRATO',
        'NOMBRE_OBRA',
        'CIUDAD_OBRA',
        'VALOR_PAGADO',
        'PERIODO_PAGADO',
        'FECHA_PAGO',
        'TICKETID',
        'NIT_EMPRESA',
        'NOMBRE_EMPRESA',
        'codigo_verificacion',
        'numero_certificado',
        'regional_sena',
        'generado_por'
    ];
    
    protected $casts = [
        'FECHA_PAGO' => 'date',
        'VALOR_PAGADO' => 'decimal:2'
    ];

    // Scopes corregidos
    public function scopePorNit($query, $nit)
    {
        return $query->where('NIT_EMPRESA', $nit);
    }

    public function scopePorTicket($query, $ticket)
    {
        return $query->where('TICKETID', $ticket);
    }

    public function scopePorVigencia($query, $year)
    {
        return $query->where('PERIODO_PAGADO', 'like', $year . '-%');
    }

    public function scopePorAnioFecha($query, $year)
    {
        return $query->whereYear('FECHA_PAGO', $year);
    }
}