<?php

namespace App\Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoTuristico extends Model
{
    protected $table = 'producto_turistico';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo_producto',
        'descripcion',
        'itinerario',
        'duracion_dias',
        'precio_base',
        'moneda',
        'porcentaje_reserva',
        'minimo_pasajeros',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'precio_base' => 'decimal:2',
        'porcentaje_reserva' => 'decimal:2',
        'duracion_dias' => 'integer',
        'minimo_pasajeros' => 'integer',
    ];

    public function destinos()
    {
        return $this->belongsToMany(Destino::class, 'producto_destino', 'id_producto', 'id_destino')
            ->withPivot('id_producto_destino', 'orden_visita', 'observacion')
            ->orderByPivot('orden_visita', 'asc');
    }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'producto_servicio', 'id_producto', 'id_servicio')
            ->withPivot('id_producto_servicio', 'incluido', 'precio_adicional', 'observacion');
    }

    public function salidas()
    {
        return $this->hasMany(Salida::class, 'id_producto');
    }
}
