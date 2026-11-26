<?php

namespace Tests\Feature;

use App\Modules\AsistenteIA\Services\RecomendadorIaService;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AsistenteIaTest extends TestCase
{
    protected ?Usuario $usuario = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::where('nombre_usuario', 'admin')->first();
        if (!$this->usuario) {
            $rol = Rol::firstOrCreate(['nombre' => 'GERENTE'], ['descripcion' => 'Administrador']);
            $persona = Persona::firstOrCreate(
                ['tipo_documento' => 'DNI', 'numero_documento' => '00000001'],
                ['primer_nombre' => 'Admin', 'apellido_paterno' => 'Sistema', 'email' => 'admin@test.com', 'activo' => true]
            );
            $this->usuario = Usuario::create([
                'id_persona' => $persona->id_persona,
                'id_rol' => $rol->id_rol,
                'nombre_usuario' => 'admin_test_' . uniqid(),
                'clave_hash' => Hash::make('password123'),
                'activo' => true,
            ]);
        }
    }

    public function test_usuario_no_autenticado_es_redirigido_a_login(): void
    {
        $response = $this->get('/asistente-ia');
        $response->assertRedirect('/login');
    }

    public function test_usuario_autenticado_puede_cargar_la_interfaz_del_asistente(): void
    {
        $response = $this->actingAs($this->usuario)->get('/asistente-ia');

        $response->assertStatus(200);
        $response->assertSee('Inti');
        $response->assertSee('Asistente');
        $response->assertSee('Destinos Activos');
        $response->assertSee('Preguntas Frecuentes');
    }

    public function test_consulta_falla_si_el_mensaje_esta_vacio(): void
    {
        $response = $this->actingAs($this->usuario)->postJson('/asistente-ia/consultar', [
            'mensaje' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mensaje']);
    }

    public function test_motor_heuristico_recomienda_geiseres_ante_consulta_de_altura_o_termas(): void
    {
        $response = $this->actingAs($this->usuario)->postJson('/asistente-ia/consultar', [
            'mensaje' => 'Quiero viajar a conocer fuentes termales y los géiseres en la altura',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'exito' => true,
        ]);

        $contenido = $response->json('respuesta');
        $this->assertTrue(
            str_contains($contenido, 'Géiseres') || str_contains($contenido, 'Candarave'),
            'La respuesta debe recomendar los Géiseres de Candarave'
        );
        $this->assertNotEmpty($response->json('tours'));
    }

    public function test_motor_heuristico_recomienda_miculla_para_viajes_en_familia(): void
    {
        $response = $this->actingAs($this->usuario)->postJson('/asistente-ia/consultar', [
            'mensaje' => 'Viajo en familia con niños pequeños y busco un tour tranquilo',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'exito' => true,
        ]);

        $contenido = $response->json('respuesta');
        $this->assertTrue(
            str_contains($contenido, 'Miculla') || str_contains($contenido, 'Pachía'),
            'La respuesta debe recomendar Miculla y Pachía para familias'
        );
    }

    public function test_servicio_obtiene_contexto_turistico_desde_la_base_de_datos(): void
    {
        /** @var RecomendadorIaService $service */
        $service = app(RecomendadorIaService::class);
        $contexto = $service->obtenerContextoTuristico();

        $this->assertArrayHasKey('destinos', $contexto);
        $this->assertArrayHasKey('tours', $contexto);
        $this->assertArrayHasKey('salidas', $contexto);
        $this->assertGreaterThan(0, count($contexto['destinos']));
    }
}
