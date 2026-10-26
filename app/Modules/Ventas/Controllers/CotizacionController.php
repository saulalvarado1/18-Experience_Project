<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Ventas\Services\CotizacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class CotizacionController extends Controller
{
    protected CotizacionService $cotizacionService;

    public function __construct(CotizacionService $cotizacionService)
    {
        $this->cotizacionService = $cotizacionService;
    }

    public function index(Request $request)
    {
        $filtros = [
            'estado' => $request->query('estado', 'TODOS'),
            'termino' => $request->query('termino'),
        ];

        $cotizaciones = $this->cotizacionService->listar($filtros);
        return view('cotizaciones.index', compact('cotizaciones', 'filtros'));
    }

    public function create()
    {
        $clientes = Cliente::with('persona')->where('activo', 1)->get();
        $productos = ProductoTuristico::where('activo', 1)->with(['salidas' => function ($q) {
            $q->where('estado', 'PROGRAMADA')->where('fecha', '>=', now()->toDateString())->orderBy('fecha', 'asc');
        }])->get();

        return view('cotizaciones.create', compact('clientes', 'productos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cliente' => 'required|integer|exists:cliente,id_cliente',
            'vigencia_hasta' => 'nullable|date',
            'observacion' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.id_producto' => 'required|integer|exists:producto_turistico,id_producto',
            'items.*.id_salida' => 'nullable|integer|exists:salida,id_salida',
            'items.*.cantidad_pasajeros' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'nullable|numeric|min:0',
            'items.*.monto_adicional' => 'nullable|numeric|min:0',
            'items.*.observacion' => 'nullable|string|max:255',
        ]);

        try {
            $cotizacion = $this->cotizacionService->crear(
                [
                    'id_cliente' => $validated['id_cliente'],
                    'vigencia_hasta' => $validated['vigencia_hasta'] ?? null,
                    'observacion' => $validated['observacion'] ?? null,
                ],
                $validated['items'],
                Auth::id()
            );

            return redirect()->route('cotizaciones.index')
                ->with('success', "Cotización {$cotizacion->codigo} creada exitosamente por S/ {$cotizacion->total}.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $cotizacion = $this->cotizacionService->obtenerPorId((int) $id);
        return response()->json($cotizacion);
    }

    public function cambiarEstado($id, Request $request)
    {
        $request->validate([
            'estado' => 'required|in:VIGENTE,ACEPTADA,NO_ACEPTADA,CANCELADA',
            'motivo' => 'nullable|string|max:500',
        ]);

        try {
            $cotizacion = $this->cotizacionService->cambiarEstado(
                (int) $id,
                $request->input('estado'),
                Auth::id(),
                $request->input('motivo')
            );

            return redirect()->route('cotizaciones.index')
                ->with('success', "La cotización {$cotizacion->codigo} cambió a estado {$cotizacion->estado}.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function pdf($id)
    {
        $pdf = $this->cotizacionService->generarPdf((int) $id);
        $cotizacion = $this->cotizacionService->obtenerPorId((int) $id);

        return $pdf->download("Cotizacion_{$cotizacion->codigo}.pdf");
    }
}
