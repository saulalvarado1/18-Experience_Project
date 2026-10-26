<?php

namespace App\Modules\Finanzas\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodo_pago';
    protected $primaryKey = 'id_metodo_pago';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'requiere_validacion',
        'aplica_recargo',
        'porcentaje_recargo',
        'activo',
    ];

    protected $casts = [
        'requiere_validacion' => 'boolean',
        'aplica_recargo' => 'boolean',
        'porcentaje_recargo' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
