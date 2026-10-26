<?php

namespace App\Modules\Reportes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reportes\Services\ReporteService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    protected ReporteService $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    public function index(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $metricas = $this->reporteService->metricasGenerales();
        $ventasProducto = $this->reporteService->ventasPorProducto($desde, $hasta);
        $ocupacion = $this->reporteService->ocupacionSalidas();

        return view('reportes.index', compact('metricas', 'ventasProducto', 'ocupacion', 'desde', 'hasta'));
    }

    public function pdf(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $datos = [
            'metricas' => $this->reporteService->metricasGenerales(),
            'ventasProducto' => $this->reporteService->ventasPorProducto($desde, $hasta),
            'ocupacion' => $this->reporteService->ocupacionSalidas(),
        ];

        $pdf = $this->reporteService->generarPdfReporte($datos);

        return $pdf->download("Reporte_Gerencial_" . date('Ymd_His') . ".pdf");
    }
}
