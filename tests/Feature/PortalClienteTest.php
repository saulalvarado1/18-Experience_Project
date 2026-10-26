<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalClienteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function getUsuario(string $rolNombre): Usuario
    {
        return Usuario::whereHas('rol', fn($q) => $q->where('nombre', $rolNombre))->firstOrFail();
    }

    public function test_tourist_can_self_register_and_is_logged_in()
    {
        $response = $this->post('/registro', [
            'tipo_documento' => 'DNI',
            'numero_documento' => '71829304',
            'nombres' => 'Alejandro',
            'apellidos' => 'Vargas Pinto',
            'telefono' => '952887766',
            'correo' => 'alejandro.turista.' . rand(100, 999) . '@test.com',
            'password' => 'Turista2026*',
            'password_confirmation' => 'Turista2026*',
        ]);

        $response->assertRedirect('/mi-portal');
        $this->assertAuthenticated();

        $usuario = auth()->user();
        $this->assertEquals('CLIENTE', $usuario->rol->nombre);
        $this->assertDatabaseHas('persona', ['numero_documento' => '71829304']);
        $this->assertDatabaseHas('cliente', ['id_persona' => $usuario->id_persona]);
    }

    public function test_client_portal_dashboard_accessible_only_for_client()
    {
        $clienteUser = $this->getUsuario('CLIENTE');

        $response = $this->actingAs($clienteUser)->get('/mi-portal');
        $response->assertStatus(200);
        $response->assertSee('Portal de Autoconsulta del Turista');
    }

    public function test_client_can_accept_quote_and_upload_voucher()
    {
        Storage::fake('public');

        $counter = $this->getUsuario('COUNTER');
        $clienteUser = $this->getUsuario('CLIENTE');
        $cliente = Cliente::where('id_persona', $clienteUser->id_persona)->firstOrFail();
        $producto = ProductoTuristico::where('activo', 1)->firstOrFail();

        // 1. Counter emite una cotización para este cliente
        $cotService = app(CotizacionService::class);
        $cot = $cotService->crear(
            ['id_cliente' => $cliente->id_cliente],
            [['id_producto' => $producto->id_producto, 'cantidad_pasajeros' => 2]],
            $counter->id_usuario
        );

        // 2. Cliente acepta la cotización desde el portal
        $responseAceptar = $this->actingAs($clienteUser)->post("/mi-portal/cotizaciones/{$cot->id_cotizacion}/aceptar");
        $responseAceptar->assertRedirect('/mi-portal/cotizaciones');
        $this->assertEquals('ACEPTADA', $cot->fresh()->estado);

        // 3. Cliente sube su voucher de pago
        $archivoVoucher = UploadedFile::fake()->create('voucher_yape.jpg', 300, 'image/jpeg');
        $responsePago = $this->actingAs($clienteUser)->post('/mi-portal/pagos', [
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => 4, // YAPE
            'monto_base' => round((float) $cot->total * 0.5, 2),
            'numero_operacion' => 'YAPE-998877',
            'comprobante' => $archivoVoucher,
        ]);

        $responsePago->assertRedirect('/mi-portal/cotizaciones');
        $this->assertDatabaseHas('pago', [
            'id_cotizacion' => $cot->id_cotizacion,
            'estado' => 'PENDIENTE_VALIDACION',
            'numero_operacion' => 'YAPE-998877',
        ]);
    }

    public function test_client_cannot_access_staff_modules()
    {
        $clienteUser = $this->getUsuario('CLIENTE');

        // Intento de entrar a gestión de usuarios
        $resp1 = $this->actingAs($clienteUser)->get('/usuarios');
        $resp1->assertStatus(403);

        // Intento de entrar a modificar destinos
        $resp2 = $this->actingAs($clienteUser)->get('/destinos');
        $resp2->assertStatus(403);
    }
}
