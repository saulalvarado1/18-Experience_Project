<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Gateways\ChargeResult;
use App\Modules\Finanzas\Models\DevolucionPago;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Finanzas\Services\PagoOnlineService;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DevolucionPagoTest extends TestCase
{
    private const API = 'https://api.culqi.com/v2';

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->sinMinimoDePasajeros();

        config([
            'services.pasarela.driver' => 'culqi',
            'services.culqi.secret_key' => 'sk_test_secreta',
            'services.culqi.api_url' => self::API,
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    // ----------------------------------------------------------------- helpers

    private function getUsuario(string $rol): Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', $rol))->firstOrFail();
    }

    private function cotizacionAceptada(bool $conSalida = false): Cotizacion
    {
        $counter = $this->getUsuario('COUNTER');
        $cliente = Cliente::where('activo', true)->firstOrFail();
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();

        $detalle = ['id_producto' => $producto->id_producto, 'cantidad_pasajeros' => 1];
        if ($conSalida) {
            $salida = $this->salidaFutura($producto->id_producto);
            $detalle['id_salida'] = $salida->id_salida;
        }

        $service = app(CotizacionService::class);
        $cot = $service->crear(['id_cliente' => $cliente->id_cliente], [$detalle], $counter->id_usuario);
        $service->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);

        return $cot->fresh();
    }

    /** Registra un pago online ya cobrado (como lo haría el checkout o el webhook). */
    private function pagoOnline(Cotizacion $cot, float $montoBase): Pago
    {
        $chargeId = 'chr_test_' . uniqid();

        return app(PagoOnlineService::class)->registrarCargoConfirmado(new ChargeResult(
            exitoso: true,
            id: $chargeId,
            montoCentimos: (int) round($montoBase * 100),
            moneda: 'PEN',
            metadata: [
                'id_cotizacion' => (string) $cot->id_cotizacion,
                'monto_base' => number_format($montoBase, 2, '.', ''),
            ],
            mensajeUsuario: 'ok',
        ));
    }

    private function fakeRefundOk(): void
    {
        Http::fake([
            self::API . '/refunds' => fn (HttpRequest $r) => Http::response([
                'object' => 'refund',
                'id' => 'ref_test_' . uniqid(),
                'charge_id' => $r['charge_id'],
                'amount' => $r['amount'],
                'reason' => $r['reason'],
            ], 201),
        ]);
    }

    // ------------------------------------------------------------------- tests

    public function test_contabilidad_devuelve_el_total_y_el_pago_queda_devuelto()
    {
        $this->fakeRefundOk();
        $contabilidad = $this->getUsuario('CONTABILIDAD');
        $cot = $this->cotizacionAceptada();
        $monto = round((float) $cot->total * 0.5, 2);
        $pago = $this->pagoOnline($cot, $monto);
        $pagoService = app(PagoService::class);
        $this->assertEquals($monto, $pagoService->montoValidado($cot->id_cotizacion));

        $this->actingAs($contabilidad)
            ->post("/pagos/{$pago->id_pago}/devolver", ['monto' => $monto, 'motivo' => 'Cliente canceló el viaje'])
            ->assertRedirect('/pagos')
            ->assertSessionHas('success');

        $pago->refresh();
        $this->assertSame('DEVUELTO', $pago->estado);
        $this->assertEquals($monto, (float) $pago->monto_devuelto);
        $this->assertEquals($monto, (float) $pago->monto_base_devuelto);
        $this->assertEquals(0.0, $pagoService->montoValidado($cot->id_cotizacion));

        $devolucion = DevolucionPago::where('id_pago', $pago->id_pago)->firstOrFail();
        $this->assertStringStartsWith('ref_test_', $devolucion->numero_operacion);
        $this->assertSame($contabilidad->id_usuario, $devolucion->id_usuario);
        $this->assertDatabaseHas('auditoria', ['accion' => 'DEVOLVER_PAGO', 'id_registro' => $pago->id_pago]);

        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::API . '/refunds'
            && $r['charge_id'] === $pago->numero_operacion
            && $r['amount'] === (int) round($monto * 100)
            && $r['reason'] === 'solicitud_comprador'
            && $r->hasHeader('Authorization', 'Bearer sk_test_secreta'));
    }

    public function test_devolucion_parcial_descuenta_solo_esa_parte_del_saldo()
    {
        $this->fakeRefundOk();
        $gerente = $this->getUsuario('GERENTE');
        $cot = $this->cotizacionAceptada();
        $pago = $this->pagoOnline($cot, 100.00);
        $online = app(PagoOnlineService::class);
        $pagoService = app(PagoService::class);

        // Devuelve 70 y retiene 30 de penalidad.
        $online->devolver($pago->id_pago, 70.00, 'Cancelación con penalidad del 30%', $gerente->id_usuario);

        $pago->refresh();
        $this->assertSame('VALIDADO', $pago->estado);
        $this->assertEquals(30.00, $pago->montoDevolvible());
        $this->assertEquals(30.00, $pagoService->montoValidado($cot->id_cotizacion));
        // El saldo libera lo devuelto: se puede volver a pagar hasta el total.
        $this->assertEquals(round((float) $cot->total - 30.00, 2), $online->maximoPagable($cot));

        // La segunda devolución por el resto cierra el pago sin errores de redondeo.
        $online->devolver($pago->id_pago, 30.00, 'Se devuelve la penalidad por excepción', $gerente->id_usuario);
        $pago->refresh();
        $this->assertSame('DEVUELTO', $pago->estado);
        $this->assertEquals((float) $pago->monto_base, (float) $pago->monto_base_devuelto);
        $this->assertSame(2, DevolucionPago::where('id_pago', $pago->id_pago)->count());
    }

    public function test_no_se_puede_devolver_mas_de_lo_pagado()
    {
        Http::fake();
        $cot = $this->cotizacionAceptada();
        $pago = $this->pagoOnline($cot, 50.00);

        $this->actingAs($this->getUsuario('GERENTE'))
            ->post("/pagos/{$pago->id_pago}/devolver", ['monto' => 50.01, 'motivo' => 'Prueba de exceso'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertSame('VALIDADO', $pago->fresh()->estado);
    }

    public function test_counter_no_puede_devolver_pagos()
    {
        Http::fake();
        $cot = $this->cotizacionAceptada();
        $pago = $this->pagoOnline($cot, 50.00);

        $this->actingAs($this->getUsuario('COUNTER'))
            ->post("/pagos/{$pago->id_pago}/devolver", ['monto' => 10, 'motivo' => 'Intento no autorizado'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_pagos_manuales_no_se_devuelven_por_la_pasarela()
    {
        Http::fake();
        $counter = $this->getUsuario('COUNTER');
        $cot = $this->cotizacionAceptada();
        $efectivo = MetodoPago::where('nombre', 'EFECTIVO')->firstOrFail();
        $pago = app(PagoService::class)->registrar([
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => $efectivo->id_metodo_pago,
            'monto_base' => 20,
        ], null, $counter->id_usuario);

        $this->actingAs($this->getUsuario('GERENTE'))
            ->post("/pagos/{$pago->id_pago}/devolver", ['monto' => 20, 'motivo' => 'No corresponde'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_si_la_pasarela_rechaza_no_se_registra_la_devolucion()
    {
        Http::fake([self::API . '/refunds' => Http::response([
            'object' => 'error',
            'type' => 'invalid_request_error',
            'merchant_message' => 'El cargo ya fue devuelto.',
        ], 400)]);
        $cot = $this->cotizacionAceptada();
        $pago = $this->pagoOnline($cot, 40.00);

        $this->actingAs($this->getUsuario('GERENTE'))
            ->post("/pagos/{$pago->id_pago}/devolver", ['monto' => 40, 'motivo' => 'Cliente canceló'])
            ->assertSessionHas('error', 'La pasarela rechazó la devolución: El cargo ya fue devuelto.');

        $pago->refresh();
        $this->assertSame('VALIDADO', $pago->estado);
        $this->assertEquals(0.0, (float) $pago->monto_devuelto);
        $this->assertSame(0, DevolucionPago::where('id_pago', $pago->id_pago)->count());
    }

    public function test_si_la_pasarela_no_responde_no_se_registra_la_devolucion()
    {
        Http::fake([self::API . '/refunds' => Http::response([], 503)]);
        $cot = $this->cotizacionAceptada();
        $pago = $this->pagoOnline($cot, 40.00);

        $this->actingAs($this->getUsuario('GERENTE'))
            ->post("/pagos/{$pago->id_pago}/devolver", ['monto' => 40, 'motivo' => 'Cliente canceló'])
            ->assertSessionHas('error');

        $this->assertSame('VALIDADO', $pago->fresh()->estado);
        $this->assertSame(0, DevolucionPago::where('id_pago', $pago->id_pago)->count());
    }

    public function test_cancelar_reserva_avisa_de_pagos_online_por_devolver_y_saldo_refleja_devolucion()
    {
        $this->fakeRefundOk();
        $counter = $this->getUsuario('COUNTER');
        $cot = $this->cotizacionAceptada(conSalida: true);
        $pago = $this->pagoOnline($cot, round((float) $cot->total, 2));
        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $counter->id_usuario, $this->pasajerosPara($cot));

        $this->actingAs($counter)
            ->post("/reservas/{$reserva->id_reserva}/cancelar", ['motivo' => 'El cliente no puede viajar'])
            ->assertRedirect('/reservas')
            ->assertSessionHas('warning', fn ($msg) => str_contains($msg, '1 pago(s) online'));

        // La cancelación no devuelve dinero por sí sola.
        $this->assertSame('VALIDADO', $pago->fresh()->estado);

        app(PagoOnlineService::class)->devolver($pago->id_pago, (float) $pago->monto_total_pagado, 'Cancelación', $this->getUsuario('GERENTE')->id_usuario);

        $saldo = DB::table('vw_saldo_reserva')->where('id_reserva', $reserva->id_reserva)->first();
        $this->assertEquals(0.0, (float) $saldo->monto_validado);
    }

    public function test_staff_no_puede_registrar_a_mano_un_pago_de_pasarela()
    {
        $cot = $this->cotizacionAceptada();
        $online = MetodoPago::where('nombre', 'PASARELA_ONLINE')->firstOrFail();

        $this->actingAs($this->getUsuario('COUNTER'))
            ->post('/pagos', [
                'id_cotizacion' => $cot->id_cotizacion,
                'id_metodo_pago' => $online->id_metodo_pago,
                'monto_base' => 10,
                'numero_operacion' => 'chr_inventado',
            ])
            ->assertSessionHasErrors('id_metodo_pago');

        $this->assertSame(0, Pago::where('id_cotizacion', $cot->id_cotizacion)->count());
    }

    public function test_bandeja_de_pagos_muestra_boton_devolver_solo_a_roles_autorizados()
    {
        $cot = $this->cotizacionAceptada();
        $pago = $this->pagoOnline($cot, 25.00);

        $this->actingAs($this->getUsuario('CONTABILIDAD'))
            ->get('/pagos?id_cotizacion=' . $cot->id_cotizacion)
            ->assertOk()
            ->assertSee('btn-devolver-pago', false)
            ->assertSee('data-id="' . $pago->id_pago . '"', false);

        $this->actingAs($this->getUsuario('COUNTER'))
            ->get('/pagos?id_cotizacion=' . $cot->id_cotizacion)
            ->assertOk()
            ->assertDontSee('class="btn btn-sm btn-outline-secondary btn-devolver-pago"', false);
    }
}
