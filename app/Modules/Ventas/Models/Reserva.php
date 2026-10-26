<?php

namespace App\Modules\Ventas\Models;

use App\Modules\Finanzas\Models\Pago;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reserva';
    protected $primaryKey = 'id_reserva';
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'id_cotizacion',
        'id_usuario_counter',
        'fecha_reserva',
        'estado',
        'moneda',
        'total_contratado',
        'fecha_limite_pago',
        'observacion',
    ];

    protected $casts = [
        'fecha_reserva' => 'datetime',
        'fecha_limite_pago' => 'datetime',
        'total_contratado' => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'id_cotizacion');
    }

    public function usuarioCounter()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_counter');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleReserva::class, 'id_reserva');
    }
}
