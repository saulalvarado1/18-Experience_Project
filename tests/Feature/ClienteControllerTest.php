<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\DB;

class ClienteControllerTest extends TestCase
{
    protected Usuario $counterUser;
    protected Usuario $contabilidadUser;

    protected function setUp(): void
    {
        parent::setUp();

        $rolCounter = Rol::where('nombre', 'COUNTER')->first();
        $rolContabilidad = Rol::where('nombre', 'CONTABILIDAD')->first();

        // Buscar usuarios existentes de prueba
        $this->counterUser = Usuario::where('id_rol', $rolCounter->id_rol)->first();
        $this->contabilidadUser = Usuario::where('id_rol', $rolContabilidad->id_rol)->first();
    }

    public function test_counter_can_access_clientes_index(): void
    {
        $response = $this->actingAs($this->counterUser)->get('/clientes');

        $response->assertStatus(200);
        $response->assertSee('Módulo de Clientes');
        $response->assertSee('NUEVO CLIENTE');
    }

    public function test_contabilidad_is_forbidden_from_clientes_index(): void
    {
        $response = $this->actingAs($this->contabilidadUser)->get('/clientes');

        $response->assertStatus(403);
    }

    public function test_counter_can_register_new_client(): void
    {
        DB::beginTransaction();

        try {
            $numDni = '78912345';

            $response = $this->actingAs($this->counterUser)->post('/clientes', [
                'tipo_documento' => 'DNI',
                'numero_documento' => $numDni,
                'nombres' => 'Camila Andrea',
                'apellidos' => 'Vargas Rojas',
                'fecha_nacimiento' => '1998-07-20',
                'nacionalidad' => 'PERUANA',
                'genero' => 'F',
                'telefono' => '952888999',
                'correo' => 'camila@example.com',
                'tipo_cliente' => 'DIRECTO',
            ]);

            $response->assertRedirect('/clientes');
            $response->assertSessionHas('success');

            // Verificar que exista en BD
            $persona = Persona::where('numero_documento', $numDni)->first();
            $this->assertNotNull($persona);
            $this->assertEquals('Camila Andrea', $persona->nombres);

            $cliente = Cliente::where('id_persona', $persona->id_persona)->first();
            $this->assertNotNull($cliente);
            $this->assertTrue($cliente->activo);

        } finally {
            DB::rollBack();
        }
    }

    public function test_counter_can_view_client_history(): void
    {
        $cliente = Cliente::first();
        $this->assertNotNull($cliente);

        $response = $this->actingAs($this->counterUser)->get("/clientes/{$cliente->id_cliente}/historial");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'cliente' => ['nombre', 'documento', 'telefono'],
            'reservas',
            'cotizaciones',
            'pagos',
        ]);
    }
}
