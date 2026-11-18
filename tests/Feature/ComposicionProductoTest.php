<?php

namespace Tests\Feature;

use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Servicio;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** CU10 – Asignar destinos y servicios al producto. */
class ComposicionProductoTest extends TestCase
{
    private ProductoTuristico $producto;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        // Producto nuevo y vacío para no depender de la composición existente.
        $this->producto = ProductoTuristico::create([
            'codigo' => 'TST-' . strtoupper(substr(uniqid(), -6)), 'nombre' => 'Tour de prueba composición',
            'tipo_producto' => 'TOUR_LOCAL', 'precio_base' => 100, 'moneda' => 'PEN',
            'porcentaje_reserva' => 50, 'minimo_pasajeros' => 1, 'duracion_dias' => 1, 'activo' => true,
        ]);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function counter(): Usuario
    {
        return Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'COUNTER'))->firstOrFail();
    }

    private function url(string $resto = ''): string
    {
        return "/tours/{$this->producto->id_producto}" . $resto;
    }

    public function test_asigna_destinos_en_orden_y_los_quita()
    {
        [$d1, $d2] = Destino::where('activo', true)->take(2)->get()->all();
        $counter = $this->counter();

        $this->actingAs($counter)->post($this->url('/destinos'), ['id_destino' => $d2->id_destino, 'orden_visita' => 2])->assertSessionHas('success');
        $this->actingAs($counter)->post($this->url('/destinos'), ['id_destino' => $d1->id_destino, 'orden_visita' => 1, 'observacion' => 'Parada de 30 min'])->assertSessionHas('success');

        $destinos = $this->producto->destinos()->orderBy('producto_destino.orden_visita')->get();
        $this->assertSame([$d1->id_destino, $d2->id_destino], $destinos->pluck('id_destino')->all());
        $this->assertSame('Parada de 30 min', $destinos->first()->pivot->observacion);
        $this->assertDatabaseHas('auditoria', ['accion' => 'ASIGNAR_DESTINO', 'id_registro' => $this->producto->id_producto]);

        $this->actingAs($counter)->post($this->url("/destinos/{$d2->id_destino}/quitar"))->assertSessionHas('success');
        $this->assertSame(1, $this->producto->destinos()->count());
        $this->assertDatabaseHas('auditoria', ['accion' => 'QUITAR_DESTINO', 'id_registro' => $this->producto->id_producto]);
    }

    public function test_rechaza_destino_repetido_y_orden_invalido()
    {
        $d = Destino::where('activo', true)->firstOrFail();
        $counter = $this->counter();

        $this->actingAs($counter)->post($this->url('/destinos'), ['id_destino' => $d->id_destino, 'orden_visita' => 0])
            ->assertSessionHas('error', 'El orden de visita debe ser mayor que cero.');
        $this->actingAs($counter)->post($this->url('/destinos'), ['id_destino' => $d->id_destino, 'orden_visita' => 1])->assertSessionHas('success');
        $this->actingAs($counter)->post($this->url('/destinos'), ['id_destino' => $d->id_destino, 'orden_visita' => 2])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ya está asignado'));

        $this->assertSame(1, $this->producto->destinos()->count());
    }

    public function test_servicio_incluido_tiene_precio_cero_y_adicional_conserva_su_precio()
    {
        [$s1, $s2] = Servicio::where('activo', true)->take(2)->get()->all();
        $counter = $this->counter();

        // Aunque llegue un precio, si está incluido se guarda en cero (flujo 4a).
        $this->actingAs($counter)->post($this->url('/servicios'), ['id_servicio' => $s1->id_servicio, 'incluido' => '1', 'precio_adicional' => 50])->assertSessionHas('success');
        $this->actingAs($counter)->post($this->url('/servicios'), ['id_servicio' => $s2->id_servicio, 'precio_adicional' => 35.5])->assertSessionHas('success');

        $servicios = $this->producto->servicios()->get()->keyBy('id_servicio');
        $this->assertTrue((bool) $servicios[$s1->id_servicio]->pivot->incluido);
        $this->assertEquals(0, (float) $servicios[$s1->id_servicio]->pivot->precio_adicional);
        $this->assertFalse((bool) $servicios[$s2->id_servicio]->pivot->incluido);
        $this->assertEquals(35.5, (float) $servicios[$s2->id_servicio]->pivot->precio_adicional);

        $this->actingAs($counter)->post($this->url('/servicios'), ['id_servicio' => $s1->id_servicio, 'incluido' => '1'])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ya está asignado'));
        $this->actingAs($counter)->post($this->url("/servicios/{$s1->id_servicio}/quitar"))->assertSessionHas('success');
        $this->assertSame(1, $this->producto->servicios()->count());
    }

    public function test_pantalla_de_composicion_y_permisos()
    {
        $this->actingAs($this->counter())->get($this->url('/composicion'))
            ->assertOk()->assertSee($this->producto->nombre)->assertSee('Destinos del itinerario')->assertSee('Servicios del producto');

        $contabilidad = Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'CONTABILIDAD'))->firstOrFail();
        $this->actingAs($contabilidad)->get($this->url('/composicion'))->assertForbidden();
        $this->actingAs($contabilidad)->post($this->url('/destinos'), ['id_destino' => 1, 'orden_visita' => 1])->assertForbidden();
    }
}
