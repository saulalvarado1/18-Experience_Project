<?php

namespace Tests;

use App\Modules\Ventas\Models\Cotizacion;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Datos de pasajeros válidos para todos los servicios de una cotización (CU20 / RN13),
     * con el formato que espera ReservaService::confirmarDesdeCotizacion().
     * Genera DNIs nuevos; deben usarse dentro de una transacción que el test revierta.
     */
    protected function pasajerosPara(Cotizacion $cot, string $puntoRecojo = 'Plaza de Armas de Tacna'): array
    {
        $nombres = ['Lucía', 'Mateo', 'Valeria', 'Diego', 'Camila', 'Sebastián', 'Ximena', 'Gonzalo'];
        $servicios = [];

        foreach ($cot->detalles()->get() as $det) {
            $pasajeros = [];
            for ($i = 0; $i < $det->cantidad_pasajeros; $i++) {
                $pasajeros[] = [
                    'tipo_documento' => 'DNI',
                    'numero_documento' => $this->dniDePrueba(),
                    'nombres' => $nombres[$i % count($nombres)],
                    'apellidos' => 'Prueba Reserva',
                    'restriccion_alimentaria' => $i === 0 ? 'Vegetariano' : null,
                    'condicion_particular' => null,
                ];
            }
            $servicios[$det->id_detalle_cotizacion] = ['punto_recojo' => $puntoRecojo, 'pasajeros' => $pasajeros];
        }

        return $servicios;
    }

    /**
     * Baja a 1 el mínimo de pasajeros de todos los tours, dentro de la transacción del test.
     * Para tests que cotizan con cualquier cantidad y no prueban esa regla (la prueba
     * CasosUsoParcialesTest::test_cu15_respeta_minimo_de_pasajeros).
     */
    protected function sinMinimoDePasajeros(): void
    {
        \Illuminate\Support\Facades\DB::table('producto_turistico')->update(['minimo_pasajeros' => 1]);
    }

    /**
     * Primera salida PROGRAMADA del producto, movida a una fecha futura (solo se cotizan salidas futuras).
     * El cambio lo revierte la transacción del test.
     */
    protected function salidaFutura(int $idProducto, int $dias = 10): \App\Modules\Catalogo\Models\Salida
    {
        $salida = \App\Modules\Catalogo\Models\Salida::where('id_producto', $idProducto)->where('estado', 'PROGRAMADA')->firstOrFail();
        $salida->update(['fecha' => now()->addDays($dias)->toDateString()]);

        return $salida->fresh();
    }

    /** DNI de 8 dígitos que no existe en la base (no repetido ni con forma 0000000X). */
    protected function dniDePrueba(): string
    {
        do {
            $dni = (string) random_int(60000000, 69999999);
        } while (
            \Illuminate\Support\Facades\DB::table('persona')->where('tipo_documento', 'DNI')->where('numero_documento', $dni)->exists()
        );

        return $dni;
    }
}
