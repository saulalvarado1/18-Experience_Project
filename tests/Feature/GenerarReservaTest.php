<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * CU20 – Generar reserva: pasajeros por servicio, punto de recojo y restricciones (RN12, RN13, RN07).
 */
class GenerarReservaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->sinMinimoDePasajeros();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', $rol))->firstOrFail();
    }

    /** Cotización aceptada con salida futura, para $pax pasajeros y, si $pagar, con 60 % validado. */
    private function cotizacion(int $pax = 2, bool $pagar = true): Cotizacion
    {
        $counter = $this->usuario('COUNTER');
        $cliente = Cliente::where('activo', true)->firstOrFail();
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->firstOrFail();
        $salida->update(['fecha' => now()->addDays(10)->toDateString(), 'capacidad' => 17]);

        $svc = app(CotizacionService::class);
        $cot = $svc->crear(['id_cliente' => $cliente->id_cliente], [[
            'id_producto' => $producto->id_producto,
            'id_salida' => $salida->id_salida,
            'cantidad_pasajeros' => $pax,
        ]], $counter->id_usuario);
        $svc->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);

        if ($pagar) {
            app(PagoService::class)->registrar([
                'id_cotizacion' => $cot->id_cotizacion,
                'id_metodo_pago' => MetodoPago::where('nombre', 'EFECTIVO')->value('id_metodo_pago'),
                'monto_base' => round((float) $cot->fresh()->total * 0.6, 2),
            ], null, $counter->id_usuario);
        }

        return $cot->fresh(['detalles']);
    }

    private function ocupados(int $idSalida): int
    {
        return (int) DB::table('vw_disponibilidad_salida')->where('id_salida', $idSalida)->value('cupos_ocupados');
    }

    private function idDetalle(Cotizacion $cot): int
    {
        return $cot->detalles->first()->id_detalle_cotizacion;
    }

    // ------------------------------------------------------------------ servicio

    public function test_confirma_con_todos_los_pasajeros_recojo_y_restricciones()
    {
        $cot = $this->cotizacion(3);
        $idSalida = $cot->detalles->first()->id_salida;
        $antes = $this->ocupados($idSalida);
        $servicios = $this->pasajerosPara($cot, 'Hotel Costa del Sol, Tacna');
        $servicios[$this->idDetalle($cot)]['pasajeros'][1]['condicion_particular'] = 'Asma leve';

        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);

        $detalle = $reserva->detalles()->with('pasajeros.persona')->first();
        $this->assertSame('Hotel Costa del Sol, Tacna', $detalle->punto_recojo);
        $this->assertCount(3, $detalle->pasajeros);
        $this->assertSame('Vegetariano', $detalle->pasajeros->firstWhere('restriccion_alimentaria', '!=', null)->restriccion_alimentaria);
        $this->assertSame('Asma leve', $detalle->pasajeros->firstWhere('condicion_particular', '!=', null)->condicion_particular);
        // Los cupos ocupados reflejan a los 3 pasajeros (antes se registraba solo al titular).
        $this->assertSame($antes + 3, $this->ocupados($idSalida));
        $this->assertDatabaseHas('auditoria', ['accion' => 'GENERAR_RESERVA', 'id_registro' => $reserva->id_reserva]);
    }

    public function test_rechaza_si_faltan_pasajeros_y_no_deja_nada_registrado()
    {
        $cot = $this->cotizacion(2);
        $servicios = $this->pasajerosPara($cot);
        $doc = $servicios[$this->idDetalle($cot)]['pasajeros'][0]['numero_documento'];
        array_pop($servicios[$this->idDetalle($cot)]['pasajeros']);

        try {
            app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);
            $this->fail('Debió rechazar la reserva incompleta.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('se contrataron 2 cupo(s) y se registraron 1', $e->getMessage());
        }

        $this->assertFalse(Reserva::where('id_cotizacion', $cot->id_cotizacion)->exists());
        $this->assertFalse(Persona::where('numero_documento', $doc)->exists(), 'La persona nueva no debe quedar registrada.');
    }

    public function test_rechaza_sin_punto_de_recojo()
    {
        $cot = $this->cotizacion(1);
        $servicios = $this->pasajerosPara($cot);
        $servicios[$this->idDetalle($cot)]['punto_recojo'] = '   ';

        $this->expectExceptionMessage('Ingrese el punto de recojo');
        app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);
    }

    public function test_rechaza_pasajero_repetido_en_el_mismo_servicio()
    {
        $cot = $this->cotizacion(2);
        $servicios = $this->pasajerosPara($cot);
        $k = $this->idDetalle($cot);
        $servicios[$k]['pasajeros'][1] = $servicios[$k]['pasajeros'][0];

        $this->expectExceptionMessage('está repetido en el mismo servicio');
        app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);
    }

    public function test_reutiliza_persona_registrada_y_rechaza_nombres_distintos()
    {
        $cot = $this->cotizacion(1);
        $titular = Cliente::findOrFail($cot->id_cliente)->persona;
        $k = $this->idDetalle($cot);
        $servicios = [$k => ['punto_recojo' => 'Agencia', 'pasajeros' => [[
            'tipo_documento' => $titular->tipo_documento,
            'numero_documento' => $titular->numero_documento,
            'nombres' => 'Otro Nombre',
            'apellidos' => 'Distinto',
        ]]]];

        try {
            app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);
            $this->fail('Debió rechazar un documento con nombres distintos.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('ya pertenece a otra persona', $e->getMessage());
        }

        // Con los nombres correctos reutiliza a la persona (no crea otra).
        $servicios[$k]['pasajeros'][0]['nombres'] = $titular->nombres;
        $servicios[$k]['pasajeros'][0]['apellidos'] = $titular->apellidos;
        $personasAntes = Persona::count();
        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);

        $this->assertSame($personasAntes, Persona::count());
        $this->assertSame($titular->id_persona, $reserva->detalles()->first()->pasajeros()->first()->id_persona);
    }

    public function test_documento_invalido_indica_el_pasajero()
    {
        $cot = $this->cotizacion(1);
        $servicios = $this->pasajerosPara($cot);
        $servicios[$this->idDetalle($cot)]['pasajeros'][0]['numero_documento'] = '123';

        $this->expectExceptionMessage('pasajero 1: El DNI debe contener exactamente 8 dígitos');
        app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $servicios);
    }

    // ------------------------------------------------------------------ HTTP

    public function test_formulario_no_abre_sin_anticipo_validado()
    {
        $cot = $this->cotizacion(2, pagar: false);

        $this->actingAs($this->usuario('COUNTER'))
            ->get('/reservas/nueva?cotizacion=' . $cot->id_cotizacion)
            ->assertRedirect('/reservas')
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Anticipo insuficiente'));
    }

    public function test_formulario_muestra_una_fila_por_pasajero_contratado()
    {
        $cot = $this->cotizacion(3);

        $html = $this->actingAs($this->usuario('COUNTER'))
            ->get('/reservas/nueva?cotizacion=' . $cot->id_cotizacion)
            ->assertOk()
            ->assertSee($cot->codigo)
            ->assertSee('El cliente es pasajero')
            ->getContent();

        $this->assertSame(3, substr_count($html, 'class="pasajero-fila"'));
    }

    public function test_counter_confirma_la_reserva_desde_el_formulario()
    {
        $cot = $this->cotizacion(2);

        $this->actingAs($this->usuario('COUNTER'))
            ->post('/reservas', [
                'id_cotizacion' => $cot->id_cotizacion,
                'servicios' => $this->pasajerosPara($cot, 'Terminal Terrestre'),
                'observacion' => 'Llegan en bus',
            ])
            ->assertRedirect('/reservas')
            ->assertSessionHas('success');

        $reserva = Reserva::where('id_cotizacion', $cot->id_cotizacion)->firstOrFail();
        $this->assertSame(2, $reserva->detalles()->first()->pasajeros()->count());
        $this->assertSame('Terminal Terrestre', $reserva->detalles()->first()->punto_recojo);
    }

    public function test_formulario_incompleto_vuelve_con_el_error_y_los_datos()
    {
        $cot = $this->cotizacion(2);
        $servicios = $this->pasajerosPara($cot);
        $k = $this->idDetalle($cot);
        $servicios[$k]['pasajeros'][1] = ['tipo_documento' => 'DNI', 'numero_documento' => '', 'nombres' => '', 'apellidos' => ''];

        $this->actingAs($this->usuario('COUNTER'))
            ->from('/reservas/nueva?cotizacion=' . $cot->id_cotizacion)
            ->post('/reservas', ['id_cotizacion' => $cot->id_cotizacion, 'servicios' => $servicios])
            ->assertRedirect('/reservas/nueva?cotizacion=' . $cot->id_cotizacion)
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Registre los datos de todos los pasajeros'))
            ->assertSessionHasInput("servicios.$k.pasajeros.0.numero_documento");

        $this->assertFalse(Reserva::where('id_cotizacion', $cot->id_cotizacion)->exists());
    }

    public function test_buscar_persona_por_documento()
    {
        $titular = Persona::where('tipo_documento', 'DNI')->firstOrFail();
        $counter = $this->usuario('COUNTER');

        $this->actingAs($counter)
            ->getJson("/reservas/persona?tipo_documento=DNI&numero_documento={$titular->numero_documento}")
            ->assertOk()
            ->assertJson(['encontrado' => true, 'nombres' => $titular->nombres, 'apellidos' => $titular->apellidos]);

        $this->actingAs($counter)
            ->getJson('/reservas/persona?tipo_documento=DNI&numero_documento=' . $this->dniDePrueba())
            ->assertOk()
            ->assertJson(['encontrado' => false]);
    }

    public function test_contabilidad_no_genera_reservas()
    {
        $cot = $this->cotizacion(1);
        $conta = $this->usuario('CONTABILIDAD');

        $this->actingAs($conta)->get('/reservas/nueva?cotizacion=' . $cot->id_cotizacion)->assertForbidden();
        $this->actingAs($conta)->getJson('/reservas/persona?tipo_documento=DNI&numero_documento=12345678')->assertForbidden();
        $this->actingAs($conta)->post('/reservas', ['id_cotizacion' => $cot->id_cotizacion, 'servicios' => $this->pasajerosPara($cot)])->assertForbidden();
    }
}
