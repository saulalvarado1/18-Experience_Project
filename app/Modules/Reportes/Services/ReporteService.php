<?php

namespace App\Modules\Reportes\Services;

use App\Modules\Catalogo\Models\Salida;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public function metricasGenerales(): array
    {
        $totalCotizaciones = Cotizacion::count();
        $totalReservas = Reserva::where('estado', '!=', 'CANCELADA')->count();
        $totalVentas = (float) Reserva::where('estado', '!=', 'CANCELADA')->sum('total_contratado');
        $totalRecaudado = (float) Pago::where('estado', 'VALIDADO')->sum(DB::raw('monto_base - monto_base_devuelto'));
        $pagosPendientes = Pago::where('estado', 'PENDIENTE_VALIDACION')->count();

        return [
            'total_cotizaciones' => $totalCotizaciones,
            'total_reservas' => $totalReservas,
            'total_ventas' => $totalVentas,
            'total_recaudado' => $totalRecaudado,
            'pagos_pendientes_validacion' => $pagosPendientes,
        ];
    }

    public function ventasPorProducto(?string $desde = null, ?string $hasta = null): array
    {
        $query = DB::table('detalle_reserva as dr')
            ->join('reserva as r', 'r.id_reserva', '=', 'dr.id_reserva')
            ->join('producto_turistico as pt', 'pt.id_producto', '=', 'dr.id_producto')
            ->where('r.estado', '!=', 'CANCELADA')
            ->where('dr.estado', '!=', 'CANCELADO');

        if ($desde) {
            $query->whereDate('r.fecha_reserva', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('r.fecha_reserva', '<=', $hasta);
        }

        return $query->select(
            'pt.id_producto',
            'pt.codigo',
            'pt.nombre',
            DB::raw('COUNT(dr.id_detalle_reserva) as total_reservas'),
            DB::raw('SUM(dr.cantidad_pasajeros) as total_pasajeros'),
            DB::raw('SUM(dr.subtotal) as total_ingresos')
        )
        ->groupBy('pt.id_producto', 'pt.codigo', 'pt.nombre')
        ->orderByDesc('total_ingresos')
        ->get()
        ->toArray();
    }

    /**
     * Pestaña DISPONIBILIDAD: ocupación de salidas, filtrada por la fecha de la salida si hay período.
     */
    public function ocupacionSalidas(?string $desde = null, ?string $hasta = null): array
    {
        $query = DB::table('vw_disponibilidad_salida');
        if ($desde) {
            $query->whereDate('fecha', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('fecha', '<=', $hasta);
        }

        return $query->orderBy('fecha', 'asc')->orderBy('hora', 'asc')->get()->toArray();
    }

    // ---------------- CU24: período y pestañas (RN17) ----------------

    /**
     * CU24 flujo 1a: fechas válidas y "Desde" no posterior a "Hasta".
     */
    public function validarPeriodo(?string $desde, ?string $hasta): void
    {
        foreach (['Desde' => $desde, 'Hasta' => $hasta] as $campo => $valor) {
            if ($valor && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                throw new \InvalidArgumentException("La fecha {$campo} no tiene un formato válido.");
            }
        }
        if ($desde && $hasta && $desde > $hasta) {
            throw new \InvalidArgumentException('La fecha Desde no puede ser posterior a la fecha Hasta.');
        }
    }

    /** Pestaña RESERVAS: reservas registradas en el período, con su saldo pendiente. */
    public function reservasPeriodo(?string $desde = null, ?string $hasta = null): array
    {
        $query = DB::table('reserva as r')
            ->join('cotizacion as c', 'c.id_cotizacion', '=', 'r.id_cotizacion')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->join('persona as p', 'p.id_persona', '=', 'cl.id_persona')
            ->leftJoin('vw_saldo_reserva as s', 's.id_reserva', '=', 'r.id_reserva')
            ->select(
                'r.id_reserva', 'r.codigo', 'r.fecha_reserva', 'r.estado', 'r.total_contratado',
                DB::raw("p.nombres || ' ' || p.apellidos as cliente"),
                DB::raw('COALESCE(s.monto_validado, 0) as pagado'),
                DB::raw("CASE WHEN r.estado = 'CANCELADA' THEN 0 ELSE COALESCE(s.saldo_pendiente, r.total_contratado) END as saldo"),
                DB::raw("(SELECT string_agg(pt.nombre, ', ') FROM detalle_reserva dr JOIN producto_turistico pt ON pt.id_producto = dr.id_producto WHERE dr.id_reserva = r.id_reserva) as servicios")
            );
        $this->filtrarFecha($query, 'r.fecha_reserva', $desde, $hasta);

        return $query->orderByDesc('r.fecha_reserva')->get()->toArray();
    }

    /** Pestaña VENTAS: pagos validados (netos de devoluciones) en el período. */
    public function ventasPeriodo(?string $desde = null, ?string $hasta = null): array
    {
        $query = DB::table('pago as pg')
            ->join('cotizacion as c', 'c.id_cotizacion', '=', 'pg.id_cotizacion')
            ->join('cliente as cl', 'cl.id_cliente', '=', 'c.id_cliente')
            ->join('persona as p', 'p.id_persona', '=', 'cl.id_persona')
            ->join('metodo_pago as mp', 'mp.id_metodo_pago', '=', 'pg.id_metodo_pago')
            ->where('pg.estado', 'VALIDADO')
            ->select(
                'pg.id_pago', 'pg.fecha_pago', 'pg.numero_operacion', 'c.codigo as cotizacion', 'mp.nombre as metodo',
                DB::raw("p.nombres || ' ' || p.apellidos as cliente"),
                DB::raw('(pg.monto_base - pg.monto_base_devuelto) as monto')
            );
        $this->filtrarFecha($query, 'pg.fecha_pago', $desde, $hasta);

        return $query->orderByDesc('pg.fecha_pago')->get()->toArray();
    }

    /** Pestaña SALDOS PENDIENTES: reservas confirmadas que aún deben dinero (período por fecha de reserva). */
    public function saldosPendientes(?string $desde = null, ?string $hasta = null): array
    {
        return array_values(array_filter(
            $this->reservasPeriodo($desde, $hasta),
            fn ($r) => $r->estado === 'CONFIRMADA' && (float) $r->saldo > 0.009
        ));
    }

    /** Pestaña RESUMEN: clientes nuevos, total vendido, productos más reservados y ventas por método. */
    public function resumen(?string $desde = null, ?string $hasta = null): array
    {
        $clientesNuevos = DB::table('cliente');
        $this->filtrarFecha($clientesNuevos, 'fecha_registro', $desde, $hasta);

        $ventas = $this->ventasPeriodo($desde, $hasta);
        $porMetodo = [];
        foreach ($ventas as $v) {
            $porMetodo[$v->metodo] = round(($porMetodo[$v->metodo] ?? 0) + (float) $v->monto, 2);
        }
        arsort($porMetodo);

        return [
            'clientes_nuevos' => $clientesNuevos->count(),
            'total_vendido' => round(array_sum(array_map(fn ($v) => (float) $v->monto, $ventas)), 2),
            'reservas_vigentes' => count(array_filter($this->reservasPeriodo($desde, $hasta), fn ($r) => $r->estado === 'CONFIRMADA')),
            'productos_top' => array_slice($this->ventasPorProducto($desde, $hasta), 0, 5),
            'ventas_por_metodo' => $porMetodo,
        ];
    }

    private function filtrarFecha($query, string $columna, ?string $desde, ?string $hasta): void
    {
        if ($desde) {
            $query->whereDate($columna, '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate($columna, '<=', $hasta);
        }
    }

    public function generarPdfReporte(array $datos)
    {
        $logoPath = public_path('img/logo-18-grados-sur.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('reportes.pdf', [
            'metricas' => $datos['metricas'],
            'ventasProducto' => $datos['ventasProducto'],
            'ocupacion' => $datos['ocupacion'],
            'resumen' => $datos['resumen'] ?? null,
            'desde' => $datos['desde'] ?? null,
            'hasta' => $datos['hasta'] ?? null,
            'logoBase64' => $logoBase64,
            'fechaEmision' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');
        return $pdf;
    }
}
