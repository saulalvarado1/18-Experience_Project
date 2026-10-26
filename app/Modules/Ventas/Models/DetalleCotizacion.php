<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use Illuminate\Database\Eloquent\Model;

class DetalleCotizacion extends Model
{
    protected $table = 'detalle_cotizacion';
    protected $primaryKey = 'id_detalle_cotizacion';
    public $timestamps = false;

    protected $fillable = [
        'id_cotizacion',
        'id_producto',
        'id_salida',
        'cantidad_pasajeros',
        'precio_unitario',
        'monto_adicional',
        'subtotal',
        'observacion',
    ];

    protected $casts = [
        'cantidad_pasajeros' => 'integer',
        'precio_unitario' => 'decimal:2',
        'monto_adicional' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function producto()
    {
        return $this->belongsTo(ProductoTuristico::class, 'id_producto');
    }

    public function salida()
    {
        return $this->belongsTo(Salida::class, 'id_salida');
    }
}
