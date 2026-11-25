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

    public function getImagenUrlAttribute(): string
    {
        $map = [
            'TL-GEISER-001' => 'img/tours/geiseres-candarave.jpg',
            'TL-CITY-001'   => 'img/tours/city-tour-tacna.jpg',
            'TL-VALLE-002'  => 'img/tours/miculla-valle-viejo.jpg',
            'TL-PISCO-003'  => 'img/tours/ruta-pisco.jpg',
            'TL-TARATA-004' => 'img/tours/tarata-tikaco.jpg',
        ];

        return asset($map[$this->codigo] ?? 'img/logo-18-grados-sur.png');
    }

    public function getFotoDescripcionAttribute(): string
    {
        $descripciones = [
            'TL-GEISER-001' => 'Columnas de vapor geotérmico y fuentes termales naturales a más de 4,300 msnm en la cordillera de Candarave.',
            'TL-CITY-001'   => 'Paseo Cívico de Tacna: Arco Parabólico en cantería rosada de 18 metros y Catedral neoclásica.',
            'TL-VALLE-002'  => 'Puente Colgante de Miculla (siglo XIX) suspendido sobre el río Palca y zona arqueológica de petroglifos.',
            'TL-PISCO-003'  => 'Viñedos y bodegas tradicionales del Valle de Magollo con cata de pisco puro tacneño y macerados.',
            'TL-TARATA-004' => 'Andenería preínca de Tarata y baños termomedicinales de Tikaco con vistas a la cordillera.',
        ];

        return $descripciones[$this->codigo] ?? ($this->descripcion ?? 'Fotografía oficial del recorrido turístico');
    }

    public function getBadgeCategoriaAttribute(): string
    {
        $categorias = [
            'TL-GEISER-001' => 'Aventura & Termas',
            'TL-CITY-001'   => 'Más Popular',
            'TL-VALLE-002'  => 'Cultura & Caminata',
            'TL-PISCO-003'  => 'Enoturismo & Cata',
            'TL-TARATA-004' => 'Naturaleza & Relax',
        ];

        return $categorias[$this->codigo] ?? 'Tour Local';
    }

    public function getRatingAttribute(): string
    {
        $ratings = [
            'TL-GEISER-001' => '4.9',
            'TL-CITY-001'   => '4.8',
            'TL-VALLE-002'  => '4.9',
            'TL-PISCO-003'  => '4.7',
            'TL-TARATA-004' => '4.9',
        ];

        return $ratings[$this->codigo] ?? '4.8';
    }

    public function getResenasCountAttribute(): int
    {
        $counts = [
            'TL-GEISER-001' => 64,
            'TL-CITY-001'   => 128,
            'TL-VALLE-002'  => 92,
            'TL-PISCO-003'  => 45,
            'TL-TARATA-004' => 78,
        ];

        return $counts[$this->codigo] ?? 50;
    }
}
