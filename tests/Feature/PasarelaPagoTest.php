<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Services\CotizacionService;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasarelaPagoTest extends TestCase
{
    private const API = 'https://api.culqi.com/v2';

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->sinMinimoDePasajeros();

        config([
            'services.pasarela.driver' => 'culqi',
            'services.culqi.public_key' => 'pk_test_portal',
            'services.culqi.secret_key' => 'sk_test_secreta',
            'services.culqi.api_url' => self::API,
            'services.culqi.webhook_user' => 'culqi_hook',
            'services.culqi.webhook_password' => 'clave_hook',
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

    private function metodoOnline(): MetodoPago
    {
        return MetodoPago::where('nombre', 'PASARELA_ONLINE')->firstOrFail();
    }

    private function clienteUser(): Usuario
    {
        $usuario = $this->getUsuario('CLIENTE');
        if (!$usuario->persona->correo) {
            $usuario->persona->update(['correo' => 'turista.pasarela@test.com']);
        }
        return $usuario->fresh();
    }

    private function cotizacionAceptada(int $idCliente): Cotizacion
    {
        $counter = $this->getUsuario('COUNTER');
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();
        $service = app(CotizacionService::class);

        $cot = $service->crear(
            ['id_cliente' => $idCliente],
            [['id_producto' => $producto->id_producto, 'cantidad_pasajeros' => 2]],
            $counter->id_usuario
        );
        $service->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario, 'Test pasarela');

        return $cot->fresh();
    }

    private function cotizacionDelCliente(Usuario $clienteUser): Cotizacion
    {
        $cliente = Cliente::where('id_persona', $clienteUser->id_persona)->firstOrFail();
        return $this->cotizacionAceptada($cliente->id_cliente);
    }

    private function cargoCulqi(string $id, int $centimos, Cotizacion $cot, float $montoBase, ?int $idUsuario = null, string $outcome = 'venta_exitosa'): array
    {
        return [
            'object' => 'charge',
            'id' => $id,
            'amount' => $centimos,
            'currency_code' => 'PEN',
            'outcome' => ['type' => $outcome, 'user_message' => 'Su compra ha sido exitosa.'],
            'metadata' => array_filter([
                'id_cotizacion' => (string) $cot->id_cotizacion,
                'codigo_cotizacion' => $cot->codigo,
                'monto_base' => number_format($montoBase, 2, '.', ''),
                'id_usuario' => $idUsuario ? (string) $idUsuario : null,
            ]),
        ];
    }

    private function eventoWebhook(string $chargeId, string $tipo = 'charge.creation.succeeded'): array
    {
        return [
            'object' => 'event',
            'id' => 'evt_test_' . uniqid(),
            'type' => $tipo,
            // Culqi envía "data" como string JSON.
            'data' => json_encode(['object' => 'charge', 'id' => $chargeId]),
        ];
    }

    private function postWebhook(array $evento, string $user = 'culqi_hook', string $pass = 'clave_hook')
    {
        return $this->withHeaders(['Authorization' => 'Basic ' . base64_encode("{$user}:{$pass}")])
            ->postJson('/webhooks/pasarela', $evento);
    }

    // ------------------------------------------------------------ checkout

    public function test_cliente_paga_online_y_el_pago_nace_validado_y_auditado()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        $montoBase = round((float) $cot->total * 0.5, 2);
        $centimos = (int) round($montoBase * 100); // PASARELA_ONLINE sin recargo
        $chargeId = 'chr_test_' . uniqid();

        Http::fake([
            self::API . '/charges' => Http::response($this->cargoCulqi($chargeId, $centimos, $cot, $montoBase, $clienteUser->id_usuario), 201),
        ]);

        $resp = $this->actingAs($clienteUser)->postJson('/mi-portal/pagos/online', [
            'id_cotizacion' => $cot->id_cotizacion,
            'token' => 'tkn_test_abc123',
            'monto_base' => $montoBase,
        ]);

        $resp->assertOk()->assertJson(['ok' => true]);

        $pago = Pago::where('numero_operacion', $chargeId)->firstOrFail();
        $this->assertSame('VALIDADO', $pago->estado);
        $this->assertSame($this->metodoOnline()->id_metodo_pago, $pago->id_metodo_pago);
        $this->assertEquals($montoBase, (float) $pago->monto_base);
        $this->assertSame($clienteUser->id_usuario, $pago->id_usuario_registro);
        $this->assertDatabaseHas('auditoria', ['accion' => 'REGISTRAR_PAGO', 'id_registro' => $pago->id_pago]);

        // El monto lo calcula el servidor y el token viaja como source_id con la llave secreta.
        Http::assertSent(fn (HttpRequest $r) => $r->url() === self::API . '/charges'
            && $r['amount'] === $centimos
            && $r['source_id'] === 'tkn_test_abc123'
            && $r['metadata']['id_cotizacion'] === (string) $cot->id_cotizacion
            && $r->hasHeader('Authorization', 'Bearer sk_test_secreta'));
    }

    public function test_tarjeta_rechazada_no_registra_pago()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);

        Http::fake([
            self::API . '/charges' => Http::response([
                'object' => 'error',
                'type' => 'card_error',
                'user_message' => 'Tu tarjeta no tiene fondos suficientes.',
            ], 402),
        ]);

        $this->actingAs($clienteUser)->postJson('/mi-portal/pagos/online', [
            'id_cotizacion' => $cot->id_cotizacion,
            'token' => 'tkn_test_sinfondos',
            'monto_base' => 10,
        ])->assertStatus(422)->assertJson(['ok' => false, 'mensaje' => 'Tu tarjeta no tiene fondos suficientes.']);

        $this->assertSame(0, Pago::where('id_cotizacion', $cot->id_cotizacion)->count());
    }

    public function test_monto_mayor_al_saldo_se_rechaza_sin_llamar_a_la_pasarela()
    {
        Http::fake();
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);

        $this->actingAs($clienteUser)->postJson('/mi-portal/pagos/online', [
            'id_cotizacion' => $cot->id_cotizacion,
            'token' => 'tkn_test_abc',
            'monto_base' => (float) $cot->total + 100,
        ])->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_cliente_no_puede_pagar_cotizacion_ajena()
    {
        Http::fake();
        $clienteUser = $this->clienteUser();
        $otroCliente = Cliente::where('id_persona', '!=', $clienteUser->id_persona)->where('activo', true)->firstOrFail();
        $cotAjena = $this->cotizacionAceptada($otroCliente->id_cliente);

        $this->actingAs($clienteUser)->postJson('/mi-portal/pagos/online', [
            'id_cotizacion' => $cotAjena->id_cotizacion,
            'token' => 'tkn_test_abc',
            'monto_base' => 10,
        ])->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_pasarela_caida_responde_503_y_no_registra_pago()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        Http::fake([self::API . '/charges' => Http::response(['object' => 'error'], 500)]);

        $this->actingAs($clienteUser)->postJson('/mi-portal/pagos/online', [
            'id_cotizacion' => $cot->id_cotizacion,
            'token' => 'tkn_test_abc',
            'monto_base' => 10,
        ])->assertStatus(503);

        $this->assertSame(0, Pago::where('id_cotizacion', $cot->id_cotizacion)->count());
    }

    public function test_staff_no_accede_al_endpoint_de_pago_online()
    {
        $this->actingAs($this->getUsuario('COUNTER'))
            ->postJson('/mi-portal/pagos/online', ['id_cotizacion' => 1, 'token' => 'tkn_x', 'monto_base' => 1])
            ->assertForbidden();
    }

    // ------------------------------------------------------------- webhook

    public function test_webhook_registra_el_pago_una_sola_vez()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        $montoBase = round((float) $cot->total * 0.5, 2);
        $chargeId = 'chr_test_' . uniqid();

        Http::fake([
            self::API . '/charges/*' => Http::response($this->cargoCulqi($chargeId, (int) round($montoBase * 100), $cot, $montoBase), 200),
        ]);

        $this->postWebhook($this->eventoWebhook($chargeId))->assertOk()->assertJson(['status' => 'registrado']);
        $this->postWebhook($this->eventoWebhook($chargeId))->assertOk()->assertJson(['status' => 'duplicado']);

        $pagos = Pago::where('numero_operacion', $chargeId)->get();
        $this->assertCount(1, $pagos);
        $this->assertSame('VALIDADO', $pagos->first()->estado);
        // Sin id_usuario en metadata se registra a nombre de la cuenta del cliente.
        $this->assertSame($clienteUser->id_usuario, $pagos->first()->id_usuario_registro);
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'GET' && $r->url() === self::API . "/charges/{$chargeId}");
    }

    public function test_webhook_posterior_al_checkout_no_duplica_el_pago()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        $montoBase = 20.00;
        $chargeId = 'chr_test_' . uniqid();
        $cargo = $this->cargoCulqi($chargeId, 2000, $cot, $montoBase, $clienteUser->id_usuario);

        Http::fake([
            self::API . '/charges' => Http::response($cargo, 201),
            self::API . '/charges/*' => Http::response($cargo, 200),
        ]);

        $this->actingAs($clienteUser)->postJson('/mi-portal/pagos/online', [
            'id_cotizacion' => $cot->id_cotizacion,
            'token' => 'tkn_test_abc',
            'monto_base' => $montoBase,
        ])->assertOk();

        $this->postWebhook($this->eventoWebhook($chargeId))->assertOk()->assertJson(['status' => 'duplicado']);
        $this->assertSame(1, Pago::where('numero_operacion', $chargeId)->count());
    }

    public function test_webhook_con_credenciales_invalidas_es_rechazado()
    {
        Http::fake();

        $this->postWebhook($this->eventoWebhook('chr_test_falso'), 'culqi_hook', 'incorrecta')->assertUnauthorized();
        $this->postJson('/webhooks/pasarela', $this->eventoWebhook('chr_test_falso'))->assertUnauthorized();

        Http::assertNothingSent();
    }

    public function test_webhook_no_registra_cargos_que_la_api_no_confirma()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        $chargeId = 'chr_test_' . uniqid();

        // El evento dice "exitoso", pero la API (fuente de verdad) dice que fue denegado.
        Http::fake([
            self::API . '/charges/*' => Http::response($this->cargoCulqi($chargeId, 1000, $cot, 10, null, 'venta_denegada'), 200),
        ]);

        $this->postWebhook($this->eventoWebhook($chargeId))->assertOk()->assertJson(['status' => 'ignorado']);
        $this->assertSame(0, Pago::where('numero_operacion', $chargeId)->count());
    }

    public function test_webhook_rechaza_cargo_con_monto_que_no_coincide()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        $chargeId = 'chr_test_' . uniqid();

        // Metadata declara S/ 100.00 pero el cargo real fue de S/ 1.00.
        Http::fake([
            self::API . '/charges/*' => Http::response($this->cargoCulqi($chargeId, 100, $cot, 100.00), 200),
        ]);

        $this->postWebhook($this->eventoWebhook($chargeId))->assertOk()->assertJson(['status' => 'error']);
        $this->assertSame(0, Pago::where('numero_operacion', $chargeId)->count());
    }

    public function test_webhook_ignora_eventos_que_no_son_cargos_exitosos()
    {
        Http::fake();

        $this->postWebhook($this->eventoWebhook('chr_test_x', 'refund.creation.succeeded'))
            ->assertOk()->assertJson(['status' => 'ignorado']);

        Http::assertNothingSent();
    }

    // ----------------------------------------------------------------- vista

    public function test_portal_muestra_boton_pago_online_y_oculta_metodos_autovalidados()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);

        $resp = $this->actingAs($clienteUser)->get('/mi-portal/cotizaciones');

        $resp->assertOk()
            ->assertSee('btn-pagar', false)
            ->assertSee('id="tab-pago-online"', false)
            ->assertSee('data-id="' . $cot->id_cotizacion . '"', false)
            ->assertSee('https://js.culqi.com/checkout-js', false)
            ->assertSee('pk_test_portal', false)
            ->assertDontSee('sk_test_secreta', false);

        // En el selector de vouchers no aparecen métodos que se auto-validan.
        foreach (MetodoPago::where('requiere_validacion', false)->pluck('id_metodo_pago') as $id) {
            $resp->assertDontSee('<option value="' . $id . '">', false);
        }
    }

    public function test_portal_sin_llave_publica_no_muestra_pago_online()
    {
        config(['services.culqi.public_key' => null]);
        $clienteUser = $this->clienteUser();
        $this->cotizacionDelCliente($clienteUser);

        $this->actingAs($clienteUser)->get('/mi-portal/cotizaciones')
            ->assertOk()
            ->assertSee('btn-pagar', false)               // el voucher sigue disponible
            ->assertDontSee('id="tab-pago-online"', false)
            ->assertDontSee('js.culqi.com', false);
    }

    // ------------------------------------------- voucher manual (seguridad)

    public function test_cliente_no_puede_declarar_voucher_con_metodo_autovalidado()
    {
        $clienteUser = $this->clienteUser();
        $cot = $this->cotizacionDelCliente($clienteUser);
        $efectivo = MetodoPago::where('requiere_validacion', false)->where('activo', true)->firstOrFail();

        $this->actingAs($clienteUser)->post('/mi-portal/pagos', [
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => $efectivo->id_metodo_pago,
            'monto_base' => 10,
            'comprobante' => \Illuminate\Http\UploadedFile::fake()->create('voucher.jpg', 100, 'image/jpeg'),
        ])->assertSessionHasErrors('id_metodo_pago');

        $this->assertSame(0, Pago::where('id_cotizacion', $cot->id_cotizacion)->count());
    }
}
