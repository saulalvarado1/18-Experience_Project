<?php

namespace App\Modules\Seguridad\Models;

use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    protected $table = 'persona';
    protected $primaryKey = 'id_persona';
    public $timestamps = false;

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'nacionalidad',
        'genero',
        'telefono',
        'correo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }
}
