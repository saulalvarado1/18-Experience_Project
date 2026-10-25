<?php

namespace App\Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;

class Destino extends Model
{
    protected $table = 'destino';
    protected $primaryKey = 'id_destino';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'altitud_msnm',
        'como_llegar',
        'temporada_recomendada',
        'recomendaciones',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'altitud_msnm' => 'decimal:2',
    ];

    public function productos()
    {
        return $this->belongsToMany(ProductoTuristico::class, 'producto_destino', 'id_destino', 'id_producto')
            ->withPivot('orden_visita', 'observacion');
    }
}
