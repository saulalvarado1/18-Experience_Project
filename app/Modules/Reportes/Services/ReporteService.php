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
        $totalRecaudado = (float) Pago::where('estado', 'VALIDADO')->sum('monto_base');
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

    public function ocupacionSalidas(): array
    {
        return DB::table('vw_disponibilidad_salida')
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->get()
            ->toArray();
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
            'logoBase64' => $logoBase64,
            'fechaEmision' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');
        return $pdf;
    }
}
