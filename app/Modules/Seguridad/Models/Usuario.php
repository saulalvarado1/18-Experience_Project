<?php

namespace App\Modules\Seguridad\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'id_persona',
        'id_rol',
        'nombre_usuario',
        'password_hash',
        'activo',
        'fecha_creacion',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_creacion' => 'datetime',
        'ultimo_acceso' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol');
    }

    public function tieneRol(string ...$roles): bool
    {
        if (!$this->rol) {
            return false;
        }

        $rolActual = strtoupper($this->rol->nombre);
        foreach ($roles as $r) {
            if ($rolActual === strtoupper($r)) {
                return true;
            }
        }

        return false;
    }
}
