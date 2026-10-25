<?php

namespace App\Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicio';
    protected $primaryKey = 'id_servicio';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->belongsToMany(ProductoTuristico::class, 'producto_servicio', 'id_servicio', 'id_producto')
            ->withPivot('incluido', 'precio_adicional', 'observacion');
    }
}
