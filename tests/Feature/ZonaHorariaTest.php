<?php

namespace Tests\Feature;

use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La app y PostgreSQL deben usar la misma zona horaria (America/Lima): las columnas son
 * "timestamp without time zone" y mezclan valores de now() de Laravel con DEFAULT CURRENT_TIMESTAMP.
 */
class ZonaHorariaTest extends TestCase
{
    public function test_app_y_base_de_datos_usan_hora_de_peru()
    {
        $this->assertSame('America/Lima', config('app.timezone'));
        $this->assertSame('America/Lima', DB::selectOne('show timezone')->TimeZone);
    }

    public function test_hora_de_laravel_y_de_postgres_coinciden_al_guardar()
    {
        DB::beginTransaction();
        try {
            $usuario = Usuario::firstOrFail();

            // Una fila con now() de Laravel y otra con el DEFAULT de la base.
            $deLaravel = Auditoria::registrar($usuario->id_usuario, 'TEST_ZONA', 'test', null, 'laravel');
            $idDefault = DB::table('auditoria')->insertGetId(
                ['id_usuario' => $usuario->id_usuario, 'accion' => 'TEST_ZONA', 'entidad' => 'test', 'detalle' => 'default'],
                'id_auditoria'
            );
            $deBase = DB::table('auditoria')->where('id_auditoria', $idDefault)->value('fecha_hora');

            $diferencia = abs(strtotime($deLaravel->fresh()->fecha_hora) - strtotime($deBase));
            $this->assertLessThan(60, $diferencia, "Laravel y PostgreSQL guardan horas distintas ({$diferencia} s de diferencia).");
        } finally {
            DB::rollBack();
        }
    }
}
