<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use Illuminate\Database\Eloquent\Model;

class DetalleReserva extends Model
{
    protected $table = 'detalle_reserva';
    protected $primaryKey = 'id_detalle_reserva';
    public $timestamps = false;

    protected $fillable = [
        'id_reserva',
        'id_producto',
        'id_salida',
        'cantidad_pasajeros',
        'precio_unitario',
        'subtotal',
        'punto_recojo',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'cantidad_pasajeros' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function producto()
    {
        return $this->belongsTo(ProductoTuristico::class, 'id_producto');
    }

    public function salida()
    {
        return $this->belongsTo(Salida::class, 'id_salida');
    }

    public function pasajeros()
    {
        return $this->hasMany(ReservaPasajero::class, 'id_detalle_reserva');
    }
}
