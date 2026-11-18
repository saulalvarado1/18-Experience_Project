<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** CU21 – Modificar reserva (recojo, pasajeros y observación de una reserva CONFIRMADA). */
class ModificarReservaTest extends TestCase
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

    private function reservaConfirmada(int $pax = 2): Reserva
    {
        $counter = $this->usuario('COUNTER');
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->firstOrFail();
        $salida->update(['fecha' => now()->addDays(10)->toDateString(), 'capacidad' => 17]);

        $svc = app(CotizacionService::class);
        $cot = $svc->crear(['id_cliente' => Cliente::where('activo', true)->value('id_cliente')], [[
            'id_producto' => $producto->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => $pax,
        ]], $counter->id_usuario);
        $svc->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);
        app(PagoService::class)->registrar([
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => MetodoPago::where('nombre', 'EFECTIVO')->value('id_metodo_pago'),
            'monto_base' => round((float) $cot->fresh()->total * 0.6, 2),
        ], null, $counter->id_usuario);

        return app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $counter->id_usuario, $this->pasajerosPara($cot->fresh()));
    }

    /** Formulario de modificación con los datos actuales de la reserva. */
    private function datosActuales(Reserva $reserva): array
    {
        $servicios = [];
        foreach ($reserva->detalles()->with('pasajeros.persona')->get() as $det) {
            $servicios[$det->id_detalle_reserva] = [
                'punto_recojo' => $det->punto_recojo,
                'pasajeros' => $det->pasajeros->map(fn ($p) => [
                    'tipo_documento' => $p->persona->tipo_documento,
                    'numero_documento' => $p->persona->numero_documento,
                    'nombres' => $p->persona->nombres,
                    'apellidos' => $p->persona->apellidos,
                    'restriccion_alimentaria' => $p->restriccion_alimentaria,
                    'condicion_particular' => $p->condicion_particular,
                ])->all(),
            ];
        }

        return $servicios;
    }

    public function test_cambia_recojo_pasajero_y_observacion_sin_cambiar_total_ni_cupos()
    {
        $reserva = $this->reservaConfirmada(2);
        $det = $reserva->detalles()->first();
        $ocupadosAntes = (int) DB::table('vw_disponibilidad_salida')->where('id_salida', $det->id_salida)->value('cupos_ocupados');
        $totalAntes = (float) $reserva->total_contratado;

        $servicios = $this->datosActuales($reserva);
        $servicios[$det->id_detalle_reserva]['punto_recojo'] = 'Hotel Gran Tacna';
        $servicios[$det->id_detalle_reserva]['pasajeros'][1] = [
            'tipo_documento' => 'DNI', 'numero_documento' => $dni = $this->dniDePrueba(),
            'nombres' => 'Renato', 'apellidos' => 'Cambio Pasajero', 'restriccion_alimentaria' => 'Sin gluten',
        ];

        $this->actingAs($this->usuario('COUNTER'))
            ->post("/reservas/{$reserva->id_reserva}/modificar", ['servicios' => $servicios, 'observacion' => 'Cambio de acompañante'])
            ->assertRedirect('/reservas')->assertSessionHas('success');

        $reserva->refresh();
        $det->refresh();
        $this->assertSame('Hotel Gran Tacna', $det->punto_recojo);
        $this->assertSame('Cambio de acompañante', $reserva->observacion);
        $this->assertSame(2, $det->pasajeros()->count());
        $this->assertTrue($det->pasajeros()->whereHas('persona', fn ($q) => $q->where('numero_documento', $dni))->exists());
        $this->assertEquals($totalAntes, (float) $reserva->total_contratado);
        $this->assertSame($ocupadosAntes, (int) DB::table('vw_disponibilidad_salida')->where('id_salida', $det->id_salida)->value('cupos_ocupados'));
        $this->assertDatabaseHas('auditoria', ['accion' => 'EDITAR_RESERVA', 'id_registro' => $reserva->id_reserva]);
    }

    public function test_mantiene_la_cantidad_contratada_y_no_admite_repetidos()
    {
        $reserva = $this->reservaConfirmada(2);
        $k = $reserva->detalles()->value('id_detalle_reserva');
        $counter = $this->usuario('COUNTER')->id_usuario;
        $svc = app(ReservaService::class);

        $menos = $this->datosActuales($reserva);
        array_pop($menos[$k]['pasajeros']);
        try {
            $svc->modificar($reserva->id_reserva, $menos, null, $counter);
            $this->fail('Debió exigir la cantidad contratada.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('se contrataron 2 cupo(s)', $e->getMessage());
        }

        $repetido = $this->datosActuales($reserva);
        $repetido[$k]['pasajeros'][1] = $repetido[$k]['pasajeros'][0];
        $this->expectExceptionMessage('está repetido en el mismo servicio');
        $svc->modificar($reserva->id_reserva, $repetido, null, $counter);
    }

    public function test_solo_se_modifican_reservas_confirmadas()
    {
        $reserva = $this->reservaConfirmada(1);
        $counter = $this->usuario('COUNTER');
        app(ReservaService::class)->cancelar($reserva->id_reserva, 'Cliente desistió', $counter->id_usuario);

        $this->actingAs($counter)->get("/reservas/{$reserva->id_reserva}/editar")
            ->assertRedirect('/reservas')->assertSessionHas('error', 'Solo se pueden modificar reservas CONFIRMADAS.');

        $this->expectExceptionMessage('Solo se pueden modificar reservas CONFIRMADAS.');
        app(ReservaService::class)->modificar($reserva->id_reserva, [], null, $counter->id_usuario);
    }

    public function test_formulario_muestra_los_pasajeros_actuales()
    {
        $reserva = $this->reservaConfirmada(2);
        $doc = $reserva->detalles()->first()->pasajeros()->first()->persona->numero_documento;

        $this->actingAs($this->usuario('COUNTER'))->get("/reservas/{$reserva->id_reserva}/editar")
            ->assertOk()
            ->assertSee("Modificar reserva {$reserva->codigo}")
            ->assertSee('value="' . $doc . '"', false);

        $this->actingAs($this->usuario('CONTABILIDAD'))->get("/reservas/{$reserva->id_reserva}/editar")->assertForbidden();
    }
}
