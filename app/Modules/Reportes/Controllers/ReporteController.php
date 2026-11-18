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
        $desde = $request->query('desde') ?: null;
        $hasta = $request->query('hasta') ?: null;

        try {
            $this->reporteService->validarPeriodo($desde, $hasta);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('reportes.index')->with('error', $e->getMessage());
        }

        $metricas = $this->reporteService->metricasGenerales();
        $resumen = $this->reporteService->resumen($desde, $hasta);
        $reservas = $this->reporteService->reservasPeriodo($desde, $hasta);
        $ventas = $this->reporteService->ventasPeriodo($desde, $hasta);
        $saldos = $this->reporteService->saldosPendientes($desde, $hasta);
        $ventasProducto = $this->reporteService->ventasPorProducto($desde, $hasta);
        $ocupacion = $this->reporteService->ocupacionSalidas($desde, $hasta);

        return view('reportes.index', compact('metricas', 'resumen', 'reservas', 'ventas', 'saldos', 'ventasProducto', 'ocupacion', 'desde', 'hasta'));
    }

    public function pdf(Request $request)
    {
        $desde = $request->query('desde') ?: null;
        $hasta = $request->query('hasta') ?: null;

        try {
            $this->reporteService->validarPeriodo($desde, $hasta);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('reportes.index')->with('error', $e->getMessage());
        }

        $datos = [
            'metricas' => $this->reporteService->metricasGenerales(),
            'resumen' => $this->reporteService->resumen($desde, $hasta),
            'ventasProducto' => $this->reporteService->ventasPorProducto($desde, $hasta),
            'ocupacion' => $this->reporteService->ocupacionSalidas($desde, $hasta),
            'desde' => $desde,
            'hasta' => $hasta,
        ];

        $pdf = $this->reporteService->generarPdfReporte($datos);

        return $pdf->download("Reporte_Gerencial_" . date('Ymd_His') . ".pdf");
    }
}
