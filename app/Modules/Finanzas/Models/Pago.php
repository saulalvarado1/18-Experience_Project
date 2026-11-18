<?php

namespace App\Modules\Finanzas\Models;

use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pago';
    protected $primaryKey = 'id_pago';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion',
        'id_metodo_pago',
        'id_usuario_registro',
        'id_usuario_validacion',
        'fecha_pago',
        'moneda',
        'monto_base',
        'recargo',
        'monto_total_pagado',
        'estado',
        'numero_operacion',
        'ruta_comprobante',
        'fecha_validacion',
        'observacion',
        'monto_devuelto',
        'monto_base_devuelto',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'fecha_validacion' => 'datetime',
        'monto_base' => 'decimal:2',
        'recargo' => 'decimal:2',
        'monto_total_pagado' => 'decimal:2',
        'monto_devuelto' => 'decimal:2',
        'monto_base_devuelto' => 'decimal:2',
    ];

    public function devoluciones()
    {
        return $this->hasMany(DevolucionPago::class, 'id_pago');
    }

    /** Monto (incluido recargo) que aún puede devolverse al cliente. */
    public function montoDevolvible(): float
    {
        return max(0.0, round((float) $this->monto_total_pagado - (float) $this->monto_devuelto, 2));
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'id_metodo_pago');
    }

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_registro');
    }

    public function usuarioValidacion()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_validacion');
    }
}
