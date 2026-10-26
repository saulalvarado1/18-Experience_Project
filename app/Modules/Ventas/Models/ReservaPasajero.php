<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Seguridad\Models\Persona;
use Illuminate\Database\Eloquent\Model;

class ReservaPasajero extends Model
{
    protected $table = 'reserva_pasajero';
    protected $primaryKey = 'id_reserva_pasajero';
    public $timestamps = false;

    protected $fillable = [
        'id_detalle_reserva',
        'id_persona',
        'restriccion_alimentaria',
        'condicion_particular',
        'observacion',
    ];

    public function detalleReserva()
    {
        return $this->belongsTo(DetalleReserva::class, 'id_detalle_reserva');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}
