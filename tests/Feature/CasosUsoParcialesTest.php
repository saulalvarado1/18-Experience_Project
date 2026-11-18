<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * Completitud de los CU que estaban parciales: CU04, CU09, CU12, CU15, CU18, CU23 y CU24.
 */
class CasosUsoParcialesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->sinMinimoDePasajeros();
        Storage::fake('local');
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

    private function salida(): Salida
    {
        $producto = ProductoTuristico::where('activo', true)->where('precio_base', '>', 0)->firstOrFail();
        $salida = Salida::where('id_producto', $producto->id_producto)->where('estado', 'PROGRAMADA')->firstOrFail();
        $salida->update(['fecha' => now()->addDays(15)->toDateString(), 'hora' => '08:00:00', 'capacidad' => 17]);

        return $salida->fresh();
    }

    private function cotizacion(?Salida $salida = null, bool $aceptar = true): Cotizacion
    {
        $counter = $this->usuario('COUNTER');
        $salida ??= $this->salida();
        $svc = app(CotizacionService::class);
        $cot = $svc->crear(['id_cliente' => Cliente::where('activo', true)->value('id_cliente')], [[
            'id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => 1,
        ]], $counter->id_usuario);
        if ($aceptar) {
            $svc->cambiarEstado($cot->id_cotizacion, 'ACEPTADA', $counter->id_usuario);
        }

        return $cot->fresh(['detalles']);
    }

    private function pagarEnEfectivo(Cotizacion $cot, float $porcentaje): void
    {
        app(PagoService::class)->registrar([
            'id_cotizacion' => $cot->id_cotizacion,
            'id_metodo_pago' => MetodoPago::where('nombre', 'EFECTIVO')->value('id_metodo_pago'),
            'monto_base' => round((float) $cot->total * $porcentaje, 2),
        ], null, $this->usuario('COUNTER')->id_usuario);
    }

    // ============================== CU23 ==============================

    public function test_cu23_no_completa_si_el_servicio_no_se_realizo_y_completa_despues()
    {
        $salida = $this->salida();
        $cot = $this->cotizacion($salida);
        $this->pagarEnEfectivo($cot, 1.0);
        $counter = $this->usuario('COUNTER')->id_usuario;
        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $counter, $this->pasajerosPara($cot));

        try {
            app(ReservaService::class)->completar($reserva->id_reserva, $counter);
            $this->fail('No debió completar una reserva cuyo servicio no se realizó.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('aún no se realiza', $e->getMessage());
        }

        $salida->update(['fecha' => now()->subDay()->toDateString()]);
        app(ReservaService::class)->completar($reserva->id_reserva, $counter);

        $this->assertSame('COMPLETADA', $reserva->fresh()->estado);
        $this->assertSame(['COMPLETADO'], $reserva->detalles()->pluck('estado')->unique()->values()->all());
    }

    public function test_cu23_no_completa_con_salida_cancelada_ni_con_saldo()
    {
        $salida = $this->salida();
        $cot = $this->cotizacion($salida);
        $this->pagarEnEfectivo($cot, 0.6);
        $counter = $this->usuario('COUNTER')->id_usuario;
        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $counter, $this->pasajerosPara($cot));
        $salida->update(['fecha' => now()->subDay()->toDateString()]);

        try {
            app(ReservaService::class)->completar($reserva->id_reserva, $counter);
            $this->fail('No debió completar con saldo pendiente.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('saldo pendiente', $e->getMessage());
        }

        $this->pagarEnEfectivo($cot, 0.4);
        $salida->update(['estado' => 'CANCELADA']);
        $this->expectExceptionMessage('CANCELADA');
        app(ReservaService::class)->completar($reserva->id_reserva, $counter);
    }

    // ============================== CU18 ==============================

    public function test_cu18_metodo_con_validacion_exige_operacion_y_comprobante()
    {
        $cot = $this->cotizacion();
        $transferencia = MetodoPago::where('nombre', 'TRANSFERENCIA')->firstOrFail();
        $base = ['id_cotizacion' => $cot->id_cotizacion, 'id_metodo_pago' => $transferencia->id_metodo_pago, 'monto_base' => 10];
        $counter = $this->usuario('COUNTER')->id_usuario;
        $svc = app(PagoService::class);

        foreach ([
            [$base, UploadedFile::fake()->create('v.pdf', 10, 'application/pdf'), 'número de operación'],
            [$base + ['numero_operacion' => 'OP-1'], null, 'Adjunte el comprobante'],
        ] as [$datos, $archivo, $mensaje]) {
            try {
                $svc->registrar($datos, $archivo, $counter);
                $this->fail("Debió exigir: {$mensaje}");
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString($mensaje, $e->getMessage());
            }
        }

        $pago = $svc->registrar($base + ['numero_operacion' => 'OP-1'], UploadedFile::fake()->create('v.pdf', 10, 'application/pdf'), $counter);
        $this->assertSame('PENDIENTE_VALIDACION', $pago->estado);
    }

    public function test_cu18_efectivo_no_exige_comprobante()
    {
        $cot = $this->cotizacion();
        $this->pagarEnEfectivo($cot, 0.5);
        $this->assertSame(1, $cot->pagos()->where('estado', 'VALIDADO')->count());
    }

    // ============================== CU12 ==============================

    public function test_cu12_edita_salida_y_valida_duplicidad_y_estado()
    {
        $salida = $this->salida();
        $gerente = $this->usuario('GERENTE');
        $nuevaFecha = now()->addDays(20)->toDateString();

        $this->actingAs($gerente)->post("/salidas/{$salida->id_salida}/editar", ['fecha' => $nuevaFecha, 'hora' => '09:30', 'observacion' => 'Cambio de horario'])
            ->assertSessionHas('success');
        $salida->refresh();
        $this->assertSame($nuevaFecha, $salida->fecha->toDateString());
        $this->assertSame('09:30', substr($salida->hora, 0, 5));
        $this->assertDatabaseHas('auditoria', ['accion' => 'EDITAR_SALIDA', 'id_registro' => $salida->id_salida]);

        $otra = Salida::create(['id_producto' => $salida->id_producto, 'fecha' => now()->addDays(25)->toDateString(), 'hora' => '10:00:00', 'capacidad' => 17, 'estado' => 'PROGRAMADA']);
        $this->actingAs($gerente)->post("/salidas/{$otra->id_salida}/editar", ['fecha' => $nuevaFecha, 'hora' => '09:30'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'misma fecha y hora'));

        $otra->update(['estado' => 'CANCELADA']);
        $this->actingAs($gerente)->post("/salidas/{$otra->id_salida}/editar", ['fecha' => now()->addDays(30)->toDateString(), 'hora' => '11:00'])
            ->assertSessionHas('error', 'No se puede editar una salida CANCELADA.');
    }

    // ============================== CU04 ==============================

    public function test_cu04_busca_y_edita_usuarios()
    {
        $gerente = $this->usuario('GERENTE');
        $counter = $this->usuario('COUNTER');
        $contabilidadRol = Rol::where('nombre', 'CONTABILIDAD')->value('id_rol');

        $this->actingAs($gerente)->get('/usuarios?buscar=' . strtoupper($counter->nombre_usuario))
            ->assertOk()->assertSee($counter->nombre_usuario)->assertDontSee($this->usuario('CONTABILIDAD')->nombre_usuario);

        // counter1 tiene un DNI heredado con formato antiguo: si no cambia el documento, se puede editar igual.
        $persona = $counter->persona;
        $this->actingAs($gerente)->post("/usuarios/{$counter->id_usuario}/editar", [
            'tipo_documento' => $persona->tipo_documento, 'numero_documento' => $persona->numero_documento,
            'nombres' => $persona->nombres, 'apellidos' => $persona->apellidos,
            'nombre_usuario' => $counter->nombre_usuario . '.x', 'id_rol' => $contabilidadRol,
        ])->assertSessionHas('success');

        $counter->refresh();
        $this->assertSame($contabilidadRol, $counter->id_rol);
        $this->assertDatabaseHas('auditoria', ['accion' => 'EDITAR_USUARIO', 'id_registro' => $counter->id_usuario]);
    }

    public function test_cu04_reglas_de_edicion_y_alta_de_roles()
    {
        $gerente = $this->usuario('GERENTE');
        $p = $gerente->persona;
        $rolCliente = Rol::where('nombre', 'CLIENTE')->value('id_rol');
        $rolCounter = Rol::where('nombre', 'COUNTER')->value('id_rol');
        $datos = ['tipo_documento' => $p->tipo_documento, 'numero_documento' => $p->numero_documento, 'nombres' => $p->nombres,
            'apellidos' => $p->apellidos, 'nombre_usuario' => $gerente->nombre_usuario];

        $this->actingAs($gerente)->post("/usuarios/{$gerente->id_usuario}/editar", $datos + ['id_rol' => $rolCounter])
            ->assertSessionHas('error', 'No puede quitarse a sí mismo el rol de Gerente.');
        $this->actingAs($gerente)->post("/usuarios/{$gerente->id_usuario}/editar", $datos + ['id_rol' => $rolCliente])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'rol válido'));

        $this->actingAs($gerente)->post('/usuarios', [
            'tipo_documento' => 'DNI', 'numero_documento' => $this->dniDePrueba(), 'nombres' => 'Ana', 'apellidos' => 'Torres Ruiz',
            'id_rol' => $rolCliente, 'nombre_usuario' => 'ana.' . uniqid(), 'password' => 'ClaveSegura1',
        ])->assertSessionHasErrors('id_rol');
    }

    // ============================== CU09 ==============================

    public function test_cu09_edita_activa_y_busca_tours()
    {
        $counter = $this->usuario('COUNTER');
        $tour = ProductoTuristico::where('tipo_producto', 'TOUR_LOCAL')->firstOrFail();
        $datos = $tour->only(['codigo', 'tipo_producto', 'moneda', 'minimo_pasajeros', 'duracion_dias', 'descripcion']);

        $this->actingAs($counter)->post("/tours/{$tour->id_producto}/editar", $datos + ['nombre' => 'Nombre editado', 'porcentaje_reserva' => 40, 'itinerario' => 'x'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'mínimo del 50%'));
        $this->actingAs($counter)->post("/tours/{$tour->id_producto}/editar", $datos + ['nombre' => 'Nombre editado', 'porcentaje_reserva' => 60, 'itinerario' => 'Visita al MIRADOR especial'])
            ->assertSessionHas('success');
        $this->assertSame('Nombre editado', $tour->fresh()->nombre);
        $this->assertEquals((float) $tour->precio_base, (float) $tour->fresh()->precio_base, 'El precio no se edita aquí (CU11).');

        // Búsqueda sin distinguir mayúsculas y en el itinerario
        $this->actingAs($counter)->get('/tours?buscar=mirador especial')->assertOk()->assertSee('Nombre editado');

        $this->actingAs($counter)->post("/tours/{$tour->id_producto}/toggle")->assertSessionHas('success');
        $this->assertFalse((bool) $tour->fresh()->activo);
        $this->assertDatabaseHas('auditoria', ['accion' => 'DESACTIVAR_PRODUCTO', 'id_registro' => $tour->id_producto]);
    }

    // ============================== CU15 ==============================

    public function test_cu15_edita_cotizacion_vigente_y_recalcula_total()
    {
        $salida = $this->salida();
        $cot = $this->cotizacion($salida, aceptar: false);
        $counter = $this->usuario('COUNTER');

        $this->actingAs($counter)->get("/cotizaciones/{$cot->id_cotizacion}/editar")->assertOk()->assertSee("Editar cotización {$cot->codigo}");

        $this->actingAs($counter)->post("/cotizaciones/{$cot->id_cotizacion}/editar", [
            'observacion' => 'Ahora viajan tres',
            'items' => [['id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => 3]],
        ])->assertRedirect('/cotizaciones')->assertSessionHas('success');

        $cot->refresh();
        $precio = (float) ProductoTuristico::find($salida->id_producto)->precio_base;
        $this->assertEquals(round($precio * 3, 2), (float) $cot->total);
        $this->assertSame(1, $cot->detalles()->count());
        $this->assertSame(3, $cot->detalles()->first()->cantidad_pasajeros);
        $this->assertDatabaseHas('auditoria', ['accion' => 'EDITAR_COTIZACION', 'id_registro' => $cot->id_cotizacion]);
    }

    public function test_cu15_no_edita_aceptadas_ni_repite_servicios()
    {
        $salida = $this->salida();
        $counter = $this->usuario('COUNTER');
        $vigente = $this->cotizacion($salida, aceptar: false);
        $item = ['id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => 1];

        $this->actingAs($counter)->post("/cotizaciones/{$vigente->id_cotizacion}/editar", ['items' => [$item, $item]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'repetido'));

        $aceptada = $this->cotizacion($salida);
        $this->actingAs($counter)->get("/cotizaciones/{$aceptada->id_cotizacion}/editar")
            ->assertRedirect('/cotizaciones')->assertSessionHas('error', 'Solo se pueden editar cotizaciones VIGENTES.');
    }

    public function test_cu15_respeta_minimo_de_pasajeros()
    {
        $salida = $this->salida();
        ProductoTuristico::whereKey($salida->id_producto)->update(['minimo_pasajeros' => 3]);
        $svc = app(CotizacionService::class);
        $datos = ['id_cliente' => Cliente::where('activo', true)->value('id_cliente')];
        $counter = $this->usuario('COUNTER')->id_usuario;
        $item = fn ($n) => [['id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => $n]];

        try {
            $svc->crear($datos, $item(2), $counter);
            $this->fail('Debió exigir el mínimo de pasajeros.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('requiere un mínimo de 3 pasajeros', $e->getMessage());
        }

        $this->assertSame(3, $svc->crear($datos, $item(3), $counter)->detalles()->first()->cantidad_pasajeros);

        // También desde el portal del cliente
        $this->actingAs($this->usuario('CLIENTE'))->post('/mi-portal/solicitar', [
            'id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => 1,
        ])->assertSessionHas('error', fn ($m) => str_contains($m, 'mínimo de 3 pasajeros'));
    }

    public function test_cu15_solo_cotiza_salidas_futuras_y_con_cupos()
    {
        $salida = $this->salida();
        $salida->update(['capacidad' => 2]);
        $svc = app(CotizacionService::class);
        $datos = ['id_cliente' => Cliente::where('activo', true)->value('id_cliente')];
        $counter = $this->usuario('COUNTER')->id_usuario;

        try {
            $svc->crear($datos, [['id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => 3]], $counter);
            $this->fail('Debió rechazar por cupos insuficientes.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('cupo(s) disponible(s)', $e->getMessage());
        }

        $salida->update(['fecha' => now()->subDay()->toDateString(), 'capacidad' => 17]);
        $this->expectExceptionMessage('ya pasó');
        $svc->crear($datos, [['id_producto' => $salida->id_producto, 'id_salida' => $salida->id_salida, 'cantidad_pasajeros' => 1]], $counter);
    }

    // ============================== CU24 ==============================

    public function test_cu24_pestanas_y_saldos_pendientes()
    {
        $cot = $this->cotizacion();
        $this->pagarEnEfectivo($cot, 0.6);
        $reserva = app(ReservaService::class)->confirmarDesdeCotizacion($cot->id_cotizacion, $this->usuario('COUNTER')->id_usuario, $this->pasajerosPara($cot));

        $resp = $this->actingAs($this->usuario('CONTABILIDAD'))->get('/reportes?desde=' . now()->toDateString() . '&hasta=' . now()->toDateString())
            ->assertOk();
        foreach (['Resumen', 'Reservas', 'Ventas', 'Saldos pendientes', 'Disponibilidad', 'graficoMetodos'] as $texto) {
            $resp->assertSee($texto, false);
        }
        $resp->assertSee($reserva->codigo);

        $saldos = app(\App\Modules\Reportes\Services\ReporteService::class)->saldosPendientes(now()->toDateString(), now()->toDateString());
        $this->assertContains($reserva->codigo, array_map(fn ($r) => $r->codigo, $saldos));
    }

    public function test_cu24_periodo_invalido_y_pdf_con_periodo()
    {
        $contabilidad = $this->usuario('CONTABILIDAD');

        $this->actingAs($contabilidad)->get('/reportes?desde=2026-12-31&hasta=2026-01-01')
            ->assertRedirect('/reportes')->assertSessionHas('error', 'La fecha Desde no puede ser posterior a la fecha Hasta.');

        $pdf = $this->actingAs($contabilidad)->get('/reportes/pdf?desde=2026-01-01&hasta=2026-12-31');
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }
}
