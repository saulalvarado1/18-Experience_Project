<?php

namespace App\Modules\AsistenteIA\Models;

use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Database\Eloquent\Model;

class AsistenteConsulta extends Model
{
    protected $table = 'asistente_consulta';
    protected $primaryKey = 'id_consulta';
    public $timestamps = false; // Manejamos creado_en por defecto en DB

    protected $fillable = [
        'id_usuario',
        'pregunta',
        'categoria_detectada',
        'motor_usado',
        'creado_en',
    ];

    protected $casts = [
        'creado_en' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }
}
