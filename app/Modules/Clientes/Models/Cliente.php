<?php

namespace App\Modules\Clientes\Models;

use App\Modules\Seguridad\Models\Persona;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'cliente';
    protected $primaryKey = 'id_cliente';
    public $timestamps = false;

    protected $fillable = [
        'id_persona',
        'fecha_registro',
        'tipo_cliente',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_registro' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}
