<?php

namespace App\Modules\Seguridad\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditoria';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'accion',
        'entidad',
        'id_registro',
        'fecha_hora',
        'detalle',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario');
    }

    public static function registrar(int $idUsuario, string $accion, string $entidad, ?int $idRegistro = null, ?string $detalle = null): self
    {
        return self::create([
            'id_usuario' => $idUsuario,
            'accion' => $accion,
            'entidad' => $entidad,
            'id_registro' => $idRegistro,
            'fecha_hora' => now(),
            'detalle' => $detalle,
        ]);
    }
}
