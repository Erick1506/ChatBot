<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // <-- IMPORTAR Carbon

class CertificadoFIC extends Model
{
    protected $table = 'certificados_fic';
    
    protected $fillable = [
        'id',
        'nombre_empresa',
        'nitempresa',
        'nro_licencia_contrato',
        'nombre_obra',
        'ciudad_obra',
        'valor_pagado',
        'periodo_pagado',
        'fecha_pago',
        'ticketid',
    ];
    
    protected $casts = [
        'fecha_pago' => 'date',    // convierte a Carbon automáticamente
        'valor_pagado' => 'decimal:2',
    ];

    // Scopes
    public function scopePorNit($query, $nit)
    {
        return $query->where('nitempresa', $nit);
    }

    public function scopePorTicket($query, $ticket)
    {
        return $query->where('ticketid', $ticket);
    }

    public function scopePorVigencia($query, $year)
    {
        return $query->where('periodo_pagado', 'like', $year . '-%');
    }

    public function scopePorAnioFecha($query, $year)
    {
        return $query->whereYear('fecha_pago', $year);
    }
    
    // Relación con empresa (misma namespace App\Models, por eso funciona)
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nitempresa', 'nit');
    }

    /**
     * Accessor: fecha_formateada -> "09/ENE/2025"
     * Uso en blade: {{ $certificado->fecha_formateada }}
     */
    public function getFechaFormateadaAttribute()
    {
        // Si el cast 'fecha_pago' está en null retornamos cadena vacía
        /** @var Carbon|null $dt */
        $dt = $this->fecha_pago ?? null;
        if (!$dt) {
            return '';
        }

        // día con 2 dígitos
        $d = $dt->format('d');
        $y = $dt->format('Y');

        // abreviaturas en español
        $abbr = [
            1 => 'ENE', 2 => 'FEB', 3 => 'MAR', 4 => 'ABR',
            5 => 'MAY', 6 => 'JUN', 7 => 'JUL', 8 => 'AGO',
            9 => 'SEP', 10 => 'OCT', 11 => 'NOV', 12 => 'DIC'
        ];

        $mIndex = (int) $dt->format('n'); // 1..12
        $mAbbr = $abbr[$mIndex] ?? strtoupper($dt->format('M'));

        return sprintf('%s-%s-%s', $d, $mAbbr, $y);
    }
}