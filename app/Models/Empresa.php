<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Empresa extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'nit';
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'nit',
        'representante_legal',
        'correo',
        'telefono',
        'direccion',
        'Usuario',
        'Contraseña'
    ];
    
    protected $hidden = [
        'Contraseña'
    ];
    
    // Relación con certificados
    public function certificados()
    {
        return $this->hasMany(CertificadoFIC::class, 'constructor_nit', 'nit');
    }
    
    // Método para validar contraseña
    public function verificarContraseña ($contraseña)
    {
        // Si la contraseña está hasheada con bcrypt
        if (strlen($this->Contraseña) === 60 && strpos($this->Contraseña, '$2y$') === 0) {
            return Hash::check($contraseña, $this->Contraseña);
        }
        
        // Si es texto plano (no recomendado en producción)
        return $this->Contraseña === $contraseña;
    }
    
    // Método para hashear contraseña antes de guardar
    public function setContraseñaAttribute($value)
    {
        $this->attributes['Contraseña'] = Hash::make($value);
    }
    
    // Verificar si existe un usuario
    public static function existeUsuario($usuario)
    {
        return self::where('Usuario', $usuario)->exists();
    }
    
    // Buscar usuario por nombre de usuario
    public static function buscarPorUsuario($usuario)
    {
        return self::where('Usuario', $usuario)->first();
    }
}