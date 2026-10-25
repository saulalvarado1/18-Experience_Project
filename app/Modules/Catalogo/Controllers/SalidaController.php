<?php

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Services\SalidaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalidaController extends Controller
{
    protected SalidaService $salidaService;

    public function __construct(SalidaService $salidaService)
    {
        $this->salidaService = $salidaService;
    }

    public function index(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $idProducto = $request->query('id_producto');
        $estado = $request->query('estado', 'TODOS');

        $salidas = $this->salidaService->listarSalidas($desde, $hasta, $idProducto, $estado);
        $productos = ProductoTuristico::where('activo', 1)->orderBy('nombre')->get();

        return view('salidas.index', compact('salidas', 'productos', 'desde', 'hasta', 'idProducto', 'estado'));
    }

    public function store(Request $request)
    {
        try {
            $this->salidaService->programarSalida($request->all(), Auth::id());
            return redirect()->route('salidas.index')->with('success', 'Salida programada correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al programar salida: ' . $e->getMessage());
        }
    }

    public function cambiarCapacidad(Request $request, int $id)
    {
        try {
            $nuevaCapacidad = (int) $request->input('nueva_capacidad');
            $motivo = $request->input('motivo');

            $this->salidaService->cambiarCapacidad($id, $nuevaCapacidad, $motivo, Auth::id());

            return redirect()->route('salidas.index')->with('success', 'Capacidad de salida actualizada y auditada.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar capacidad: ' . $e->getMessage());
        }
    }

    public function cancelar(Request $request, int $id)
    {
        try {
            $motivo = $request->input('motivo');
            $this->salidaService->cancelarSalida($id, $motivo, Auth::id());

            return redirect()->route('salidas.index')->with('success', 'Salida cancelada y cupos liberados.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cancelar salida: ' . $e->getMessage());
        }
    }
}
