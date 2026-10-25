<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Rol;
use App\Modules\Seguridad\Models\Usuario;
use Illuminate\Support\Facades\DB;

class CatalogoAndSalidasTest extends TestCase
{
    protected Usuario $gerenteUser;
    protected Usuario $counterUser;

    protected function setUp(): void
    {
        parent::setUp();

        $rolGerente = Rol::where('nombre', 'GERENTE')->first();
        $rolCounter = Rol::where('nombre', 'COUNTER')->first();

        $this->gerenteUser = Usuario::where('id_rol', $rolGerente->id_rol)->first();
        $this->counterUser = Usuario::where('id_rol', $rolCounter->id_rol)->first();
    }

    public function test_counter_can_access_catalog_and_departures(): void
    {
        $this->actingAs($this->counterUser)->get('/destinos')->assertStatus(200);
        $this->actingAs($this->counterUser)->get('/tours')->assertStatus(200);
        $this->actingAs($this->counterUser)->get('/salidas')->assertStatus(200);
    }

    public function test_can_create_destination_and_rejects_duplicate(): void
    {
        DB::beginTransaction();

        try {
            $nombreDestino = 'Test Destino ' . uniqid();

            $response = $this->actingAs($this->counterUser)->post('/destinos', [
                'nombre' => $nombreDestino,
                'ubicacion' => 'Tacna',
                'altitud_msnm' => 1200,
            ]);

            $response->assertRedirect('/destinos');
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('destino', ['nombre' => $nombreDestino]);

            // Intento duplicado debe fallar
            $dupResponse = $this->actingAs($this->counterUser)->post('/destinos', [
                'nombre' => $nombreDestino,
            ]);

            $dupResponse->assertSessionHas('error');

        } finally {
            DB::rollBack();
        }
    }

    public function test_tour_local_requires_at_least_50_percent_advance(): void
    {
        DB::beginTransaction();

        try {
            $response = $this->actingAs($this->counterUser)->post('/tours', [
                'codigo' => 'TL-TEST-' . rand(100, 999),
                'nombre' => 'Tour Local Test',
                'tipo_producto' => 'TOUR_LOCAL',
                'precio_base' => 100,
                'moneda' => 'PEN',
                'porcentaje_reserva' => 30, // Inválido: menor a 50% (RN05)
            ]);

            $response->assertSessionHas('error');

        } finally {
            DB::rollBack();
        }
    }

    public function test_only_gerente_can_change_price_and_it_is_audited(): void
    {
        $producto = ProductoTuristico::first();
        $this->assertNotNull($producto);

        DB::beginTransaction();

        try {
            // 1. Counter no puede cambiar precios (RN04)
            $counterResponse = $this->actingAs($this->counterUser)->post("/tours/{$producto->id_producto}/precio", [
                'nuevo_precio' => 180.00,
                'motivo' => 'Intento Counter',
            ]);
            $counterResponse->assertSessionHas('error');

            // 2. Gerente sí puede cambiar precio y se audita (CU11, RN16)
            $precioAnterior = (float) $producto->precio_base;
            $nuevoPrecio = $precioAnterior + 25.00;

            $gerenteResponse = $this->actingAs($this->gerenteUser)->post("/tours/{$producto->id_producto}/precio", [
                'nuevo_precio' => $nuevoPrecio,
                'motivo' => 'Incremento de tarifas por combustible',
            ]);

            $gerenteResponse->assertRedirect('/tours');
            $gerenteResponse->assertSessionHas('success');

            // Verificar cambio en BD
            $this->assertEquals($nuevoPrecio, (float) $producto->fresh()->precio_base);

            // Verificar auditoría
            $this->assertDatabaseHas('auditoria', [
                'accion' => 'CAMBIO_PRECIO',
                'entidad' => 'producto_turistico',
                'id_registro' => $producto->id_producto,
            ]);

        } finally {
            DB::rollBack();
        }
    }

    public function test_can_program_departure_and_prevents_overlap(): void
    {
        $producto = ProductoTuristico::first();
        $this->assertNotNull($producto);

        DB::beginTransaction();

        try {
            $fecha = '2026-11-20';
            $hora = '09:00:00';

            $response = $this->actingAs($this->counterUser)->post('/salidas', [
                'id_producto' => $producto->id_producto,
                'fecha' => $fecha,
                'hora' => $hora,
                'capacidad' => 17,
            ]);

            $response->assertRedirect('/salidas');
            $response->assertSessionHas('success');

            // Intento duplicado para el mismo producto, fecha y hora (RN06)
            $dupResponse = $this->actingAs($this->counterUser)->post('/salidas', [
                'id_producto' => $producto->id_producto,
                'fecha' => $fecha,
                'hora' => $hora,
                'capacidad' => 17,
            ]);

            $dupResponse->assertSessionHas('error');

        } finally {
            DB::rollBack();
        }
    }
}
