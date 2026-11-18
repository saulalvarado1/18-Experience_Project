<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InterfazTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->sinMinimoDePasajeros();
        config(['services.culqi.public_key' => null]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function getUsuario(string $rol): Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', $rol))->firstOrFail();
    }

    private function cotizacionAceptadaDelCliente(Usuario $clienteUser, bool $conSalida = false): Cotizacion
    {
        $counter = $this->getUsuario('COUNTER');
        $cliente = Cliente::where('id_persona', $clienteUser->id_persona)->firstOrFail();
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();
        $detalle = ['id_producto' => $producto->id_producto, 'cantidad_pasajeros' => 2];
        if ($conSalida) {
            $detalle['id_salida'] = $this->salidaFutura($producto->id_producto)->id_salida;
        }

        $service = app(CotizacionService::class);
        $cot = $service->crear(['id_cliente' => $cliente->id_cliente], [$detalle], $counter->id_usuario);
        $service->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);

        return $cot->fresh();
    }

    public function test_layout_muestra_avisos_de_tipo_warning_e_info()
    {
        $this->actingAs($this->getUsuario('GERENTE'))
            ->withSession(['warning' => 'Aviso de prueba visible', 'info' => 'Dato informativo'])
            ->get('/')
            ->assertOk()
            ->assertSee('Aviso de prueba visible')
            ->assertSee('Dato informativo');
    }

    public function test_layout_muestra_errores_de_validacion()
    {
        $clienteUser = $this->getUsuario('CLIENTE');

        // Voucher sin archivo ni método: debe volver con los errores visibles.
        $this->actingAs($clienteUser)
            ->from('/mi-portal/cotizaciones')
            ->followingRedirects()
            ->post('/mi-portal/pagos', ['id_cotizacion' => 1, 'monto_base' => 10])
            ->assertOk()
            ->assertSee('Revisa los datos ingresados');
    }

    public function test_layout_incluye_menu_movil_logo_y_modal_de_confirmacion()
    {
        $this->actingAs($this->getUsuario('CLIENTE'))
            ->get('/mi-portal')
            ->assertOk()
            ->assertSee('id="btn-abrir-menu"', false)
            ->assertSee('img/logo-18-grados-sur.png', false)
            ->assertSee('id="modalConfirmar"', false);
    }

    public function test_cotizacion_aceptada_muestra_progreso_y_anticipo_pendiente()
    {
        Storage::fake('local');
        $clienteUser = $this->getUsuario('CLIENTE');
        $cot = $this->cotizacionAceptadaDelCliente($clienteUser);

        $this->actingAs($clienteUser)->get('/mi-portal/cotizaciones')
            ->assertOk()
            ->assertSee($cot->codigo)
            ->assertSee('Paga al menos')
            ->assertSee('Anticipo 50%')
            ->assertSee('btn-pagar', false);

        // Con un voucher en validación aparece en la barra y en el historial.
        $yape = MetodoPago::where('nombre', 'YAPE')->firstOrFail();
        app(PagoService::class)->registrar([
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => $yape->id_metodo_pago,
            'monto_base' => 10,
            'numero_operacion' => 'YP-555',
        ], UploadedFile::fake()->create('v.jpg', 50, 'image/jpeg'), $clienteUser->id_usuario);

        $this->actingAs($clienteUser)->get('/mi-portal/cotizaciones')
            ->assertOk()
            ->assertSee('En validación S/ 10.00')
            ->assertSee('Historial de pagos (1)');
    }

    public function test_cotizacion_con_anticipo_cubierto_y_reserva_muestra_estado_final()
    {
        $clienteUser = $this->getUsuario('CLIENTE');
        $counter = $this->getUsuario('COUNTER');
        $cot = $this->cotizacionAceptadaDelCliente($clienteUser, conSalida: true);
        $efectivo = MetodoPago::where('nombre', 'EFECTIVO')->firstOrFail();
        app(PagoService::class)->registrar([
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => $efectivo->id_metodo_pago,
            'monto_base' => round((float) $cot->total * 0.6, 2),
        ], null, $counter->id_usuario);

        $this->actingAs($clienteUser)->get('/mi-portal/cotizaciones')
            ->assertSee('¡Anticipo cubierto!');

        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $counter->id_usuario, $this->pasajerosPara($cot));

        $this->actingAs($clienteUser)->get('/mi-portal/cotizaciones')
            ->assertSee('Tu reserva está confirmada.');

        $this->actingAs($clienteUser)->get('/mi-portal/reservas')
            ->assertOk()
            ->assertSee($reserva->codigo)
            ->assertSee('Descargar boleto PDF');
    }

    public function test_login_y_registro_usan_la_marca()
    {
        $this->get('/login')->assertOk()
            ->assertSee('img/logo-18-grados-sur.png', false)
            ->assertSee('--brand-primary', false)
            ->assertSee('Crear mi cuenta de cliente');

        $this->get('/registro')->assertOk()
            ->assertSee('img/logo-18-grados-sur.png', false)
            ->assertSee('--brand-primary', false)
            ->assertSee('Será tu usuario para iniciar sesión.');
    }

    public function test_catalogo_permite_elegir_fecha_de_salida()
    {
        $resp = $this->actingAs($this->getUsuario('CLIENTE'))->get('/mi-portal/tours')->assertOk();
        $resp->assertSee('name="id_salida"', false)->assertSee('data-salidas=', false);
    }

    public function test_cliente_puede_cotizar_con_fecha_de_salida_elegida()
    {
        $clienteUser = $this->getUsuario('CLIENTE');
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->firstOrFail();
        // Se mueve a una fecha futura (la transacción del test lo revierte).
        $salida->update(['fecha' => now()->addDays(10)->toDateString()]);

        $this->actingAs($clienteUser)->get('/mi-portal/tours')
            ->assertSee('"id":' . $salida->id_salida, false);

        $this->actingAs($clienteUser)->post('/mi-portal/solicitar', [
            'id_producto' => $producto->id_producto,
            'id_salida' => $salida->id_salida,
            'cantidad_pasajeros' => 1,
        ])->assertRedirect('/mi-portal/cotizaciones');

        $cliente = Cliente::where('id_persona', $clienteUser->id_persona)->firstOrFail();
        $cot = Cotizacion::where('id_cliente', $cliente->id_cliente)->latest('id_cotizacion')->firstOrFail();
        $this->assertSame($salida->id_salida, $cot->detalles()->first()->id_salida);
    }

    public function test_todas_las_paginas_internas_cargan_con_el_nuevo_layout()
    {
        $gerente = $this->getUsuario('GERENTE');

        foreach (['/', '/clientes', '/destinos', '/tours', '/salidas', '/cotizaciones', '/cotizaciones/crear', '/reservas', '/pagos', '/reportes', '/usuarios'] as $url) {
            $this->actingAs($gerente)->get($url)->assertOk()->assertSee('id="sidebar"', false);
        }

        $cliente = $this->getUsuario('CLIENTE');
        foreach (['/mi-portal', '/mi-portal/cotizaciones', '/mi-portal/reservas', '/mi-portal/tours'] as $url) {
            $this->actingAs($cliente)->get($url)->assertOk()->assertSee('id="sidebar"', false);
        }
    }
}
