<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VentasAndPagosTest extends TestCase
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

    public function test_counter_can_create_cotizacion_and_generate_code()
    {
        $counter = $this->getUsuario('COUNTER');
        $cliente = Cliente::where('activo', 1)->firstOrFail();
        $producto = ProductoTuristico::where('activo', 1)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->first();

        $cotizacionService = app(CotizacionService::class);
        $cotizacion = $cotizacionService->crear(
            [
                'id_cliente' => $cliente->id_cliente,
                'observacion' => 'Cotización de prueba test automatizado',
            ],
            [
                [
                    'id_producto' => $producto->id_producto,
                    'id_salida' => $salida ? $salida->id_salida : null,
                    'cantidad_pasajeros' => 2,
                    'precio_unitario' => (float) $producto->precio_base,
                ]
            ],
            $counter->id_usuario
        );

        $this->assertNotNull($cotizacion->id_cotizacion);
        $this->assertStringStartsWith('COT-', $cotizacion->codigo);
        $this->assertEquals('VIGENTE', $cotizacion->estado);
        $this->assertEquals(round($producto->precio_base * 2, 2), (float) $cotizacion->total);

        // PDF Generation
        $pdf = $cotizacionService->generarPdf($cotizacion->id_cotizacion);
        $this->assertNotEmpty($pdf->output());
    }

    public function test_cotizacion_state_transition_to_aceptada()
    {
        $counter = $this->getUsuario('COUNTER');
        $cliente = Cliente::where('activo', 1)->firstOrFail();
        $producto = ProductoTuristico::where('activo', 1)->firstOrFail();

        $cotizacionService = app(CotizacionService::class);
        $cot = $cotizacionService->crear(
            ['id_cliente' => $cliente->id_cliente],
            [['id_producto' => $producto->id_producto, 'cantidad_pasajeros' => 1]],
            $counter->id_usuario
        );

        $actualizada = $cotizacionService->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);
        $this->assertEquals('ACEPTADA', $actualizada->estado);
    }

    public function test_pago_flow_with_surcharge_and_contabilidad_validation()
    {
        $counter = $this->getUsuario('COUNTER');
        $contabilidad = $this->getUsuario('CONTABILIDAD');
        $cliente = Cliente::where('activo', 1)->firstOrFail();
        $producto = ProductoTuristico::where('activo', 1)->firstOrFail();

        $cotService = app(CotizacionService::class);
        $cot = $cotService->crear(
            ['id_cliente' => $cliente->id_cliente],
            [['id_producto' => $producto->id_producto, 'cantidad_pasajeros' => 2]],
            $counter->id_usuario
        );
        $cotService->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);

        // Método con validación: TRANSFERENCIA (id 2)
        $pagoService = app(PagoService::class);
        $pago = $pagoService->registrar(
            [
                'id_cotizacion' => $cot->id_cotizacion,
                'id_metodo_pago' => 2, // TRANSFERENCIA
                'monto_base' => round((float) $cot->total * 0.5, 2), // 50% anticipo
                'numero_operacion' => 'OP-TEST-12345',
            ],
            null,
            $counter->id_usuario
        );

        $this->assertEquals('PENDIENTE_VALIDACION', $pago->estado);
        $this->assertEquals(0.00, $pagoService->montoValidado($cot->id_cotizacion));

        // Contabilidad valida el pago
        $pagoValidado = $pagoService->validar($pago->id_pago, $contabilidad->id_usuario);
        $this->assertEquals('VALIDADO', $pagoValidado->estado);
        $this->assertEquals(round((float) $cot->total * 0.5, 2), $pagoService->montoValidado($cot->id_cotizacion));
    }

    public function test_reserva_requires_at_least_50_percent_validated_advance()
    {
        $counter = $this->getUsuario('COUNTER');
        $contabilidad = $this->getUsuario('CONTABILIDAD');
        $cliente = Cliente::where('activo', 1)->firstOrFail();
        $producto = ProductoTuristico::where('activo', 1)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->firstOrFail();

        $cotService = app(CotizacionService::class);
        $cot = $cotService->crear(
            ['id_cliente' => $cliente->id_cliente],
            [[
                'id_producto' => $producto->id_producto,
                'id_salida' => $salida->id_salida,
                'cantidad_pasajeros' => 2,
            ]],
            $counter->id_usuario
        );
        $cotService->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);

        $reservaService = app(ReservaService::class);

        // Intento de reservar sin pagos validados -> debe fallar (RN05)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Anticipo insuficiente');
        $reservaService->confirmarDesdeCotizacion($cot->id_cotizacion, $counter->id_usuario);
    }

    public function test_reserva_confirms_successfully_when_advance_is_validated()
    {
        $counter = $this->getUsuario('COUNTER');
        $contabilidad = $this->getUsuario('CONTABILIDAD');
        $cliente = Cliente::where('activo', 1)->firstOrFail();
        $producto = ProductoTuristico::where('activo', 1)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->firstOrFail();

        $cotService = app(CotizacionService::class);
        $cot = $cotService->crear(
            ['id_cliente' => $cliente->id_cliente],
            [[
                'id_producto' => $producto->id_producto,
                'id_salida' => $salida->id_salida,
                'cantidad_pasajeros' => 1,
            ]],
            $counter->id_usuario
        );
        $cotService->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);

        // Registrar y validar 60% de anticipo
        $pagoService = app(PagoService::class);
        $pago = $pagoService->registrar(
            [
                'id_cotizacion' => $cot->id_cotizacion,
                'id_metodo_pago' => 1, // EFECTIVO (se valida automáticamente)
                'monto_base' => round((float) $cot->total * 0.6, 2),
            ],
            null,
            $counter->id_usuario
        );

        $this->assertEquals('VALIDADO', $pago->estado);

        // Confirmar reserva
        $reservaService = app(ReservaService::class);
        $reserva = $reservaService->confirmarDesdeCotizacion($cot->id_cotizacion, $counter->id_usuario);

        $this->assertNotNull($reserva->id_reserva);
        $this->assertStringStartsWith('RES-', $reserva->codigo);
        $this->assertEquals('CONFIRMADA', $reserva->estado);
        $this->assertCount(1, $reserva->detalles);

        // Cancelar reserva y verificar liberación
        $reservaCancelada = $reservaService->cancelar($reserva->id_reserva, 'Cliente canceló por viaje', $counter->id_usuario);
        $this->assertEquals('CANCELADA', $reservaCancelada->estado);
    }

    public function test_gerente_can_access_usuarios_and_create_user()
    {
        $gerente = $this->getUsuario('GERENTE');
        $rolCounter = Rol::where('nombre', 'COUNTER')->firstOrFail();

        $response = $this->actingAs($gerente)->post('/usuarios', [
            'tipo_documento' => 'DNI',
            'numero_documento' => '45678912',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Mamani Flores',
            'id_rol' => $rolCounter->id_rol,
            'nombre_usuario' => 'carlos.counter.' . rand(100, 999),
            'password' => 'Secreto123*',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('persona', ['numero_documento' => '45678912']);
    }

    public function test_reportes_gerenciales_accessible_by_gerente_and_generates_pdf()
    {
        $gerente = $this->getUsuario('GERENTE');

        $response = $this->actingAs($gerente)->get('/reportes');
        $response->assertStatus(200);

        $responsePdf = $this->actingAs($gerente)->get('/reportes/pdf');
        $responsePdf->assertStatus(200);
        $this->assertTrue(str_contains($responsePdf->headers->get('content-type'), 'application/pdf'));
    }
}
