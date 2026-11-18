<?php

namespace App\Modules\Finanzas\Models;

use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Database\Eloquent\Model;

class DevolucionPago extends Model
{
    protected $table = 'devolucion_pago';
    protected $primaryKey = 'id_devolucion';
    public $timestamps = false;

    protected $fillable = [
        'id_pago',
        'monto',
        'monto_base',
        'numero_operacion',
        'motivo',
        'id_usuario',
        'fecha',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'monto_base' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }
}
