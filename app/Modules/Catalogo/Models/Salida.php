<?php

namespace App\Modules\Catalogo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Salida extends Model
{
    protected $table = 'salida';
    protected $primaryKey = 'id_salida';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'fecha',
        'hora',
        'capacidad',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
        'capacidad' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(ProductoTuristico::class, 'id_producto');
    }

    /**
     * Obtiene la disponibilidad en tiempo real (ocupados y disponibles).
     */
    public function getDisponibilidadAttribute(): object
    {
        $vista = DB::table('vw_disponibilidad_salida')
            ->where('id_salida', $this->id_salida)
            ->first();

        if ($vista) {
            return (object) [
                'capacidad' => $vista->capacidad,
                'ocupados' => $vista->cupos_ocupados,
                'disponibles' => $vista->cupos_disponibles,
            ];
        }

        return (object) [
            'capacidad' => $this->capacidad,
            'ocupados' => 0,
            'disponibles' => $this->capacidad,
        ];
    }
}
