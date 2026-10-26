<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizacion';
    protected $primaryKey = 'id_cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'id_cliente',
        'id_usuario_counter',
        'fecha_cotizacion',
        'estado',
        'moneda',
        'total',
        'vigencia_hasta',
        'observacion',
    ];

    protected $casts = [
        'fecha_cotizacion' => 'datetime',
        'vigencia_hasta' => 'datetime',
        'total' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'id_cliente');
    }

    public function usuarioCounter()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_counter');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleCotizacion::class, 'id_cotizacion');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_cotizacion');
    }

    public function reserva()
    {
        return $this->hasOne(Reserva::class, 'id_cotizacion');
    }
}
