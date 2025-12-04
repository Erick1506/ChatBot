<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CertificadoGenerado extends Model
{
    protected $table = 'certificados_generados';
    
    protected $fillable = [
        'serial',
        'nit_empresa',
        'nombre_empresa',
        'tipo_certificado',
        'cantidad_registros',
        'valor_total',
        'usuario_generador',
        'canal_generacion',
        'criterios_busqueda',
        'ruta_archivo',
        'nombre_archivo',
        'hash_archivo',
        'descargado',
        'fecha_descarga',
        'ip_descarga'
    ];
    
    protected $casts = [
        'criterios_busqueda' => 'array',
        'valor_total' => 'decimal:2',
        'descargado' => 'boolean',
        'fecha_descarga' => 'datetime',
        'cantidad_registros' => 'integer'
    ];
    
    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->hash_archivo)) {
                $model->hash_archivo = self::generarHashUnico();
            }
        });
    }
    
    /**
     * Generar hash único para el archivo
     */
    public static function generarHashUnico(): string
    {
        return Hash::make(Str::random(40) . microtime(true));
    }
    
    /**
     * Generar serial manualmente (backup)
     */
    public static function generarSerialManual(): string
    {
        return 'CERT-' . date('Y') . str_pad(date('m'), 2, '0', STR_PAD_LEFT) . 
               '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Relación con la empresa (opcional)
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'nit_empresa', 'nit');
    }
    
    /**
     * Marcar como descargado
     */
    public function marcarDescargado($ip = null)
    {
        $this->descargado = true;
        $this->fecha_descarga = now();
        $this->ip_descarga = $ip ?? request()->ip();
        $this->save();
    }
    
    /**
     * Scope para buscar por NIT
     */
    public function scopePorNit($query, $nit)
    {
        return $query->where('nit_empresa', $nit);
    }
    
    /**
     * Scope para buscar por serial
     */
    public function scopePorSerial($query, $serial)
    {
        return $query->where('serial', $serial);
    }
    
    /**
     * Scope para certificados no descargados
     */
    public function scopeNoDescargados($query)
    {
        return $query->where('descargado', false);
    }
    
    /**
     * Scope para certificados recientes
     */
    public function scopeRecientes($query, $dias = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($dias));
    }
    
    /**
     * Formatear fecha de creación
     */
    public function getFechaCreacionFormateadaAttribute()
    {
        return $this->created_at->format('d/m/Y H:i');
    }
    
    /**
     * Verificar si el certificado es reciente (menos de 7 días)
     */
    public function getEsRecienteAttribute()
    {
        return $this->created_at->diffInDays(now()) <= 7;
    }
    
    /**
     * Obtener criterios de búsqueda formateados
     */
    public function getCriteriosFormateadosAttribute()
    {
        $criterios = $this->criterios_busqueda ?? [];
        
        if (empty($criterios)) {
            return 'Consulta general';
        }
        
        $texto = '';
        if (isset($criterios['ticket'])) {
            $texto .= "Ticket: {$criterios['ticket']}";
        }
        if (isset($criterios['year'])) {
            $texto .= "Año: {$criterios['year']}";
        }
        
        return $texto ?: 'Consulta general';
    }
}