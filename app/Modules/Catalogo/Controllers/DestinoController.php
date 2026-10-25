<?php

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Services\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DestinoController extends Controller
{
    protected CatalogoService $catalogoService;

    public function __construct(CatalogoService $catalogoService)
    {
        $this->catalogoService = $catalogoService;
    }

    public function index(Request $request)
    {
        $termino = $request->query('buscar');
        $estado = $request->query('estado', 'TODOS');

        $destinos = $this->catalogoService->listarDestinos($termino, $estado);

        return view('destinos.index', compact('destinos', 'termino', 'estado'));
    }

    public function store(Request $request)
    {
        try {
            $this->catalogoService->crearDestino($request->all(), Auth::id());
            return redirect()->route('destinos.index')->with('success', 'Destino turístico registrado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al registrar destino: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $this->catalogoService->actualizarDestino($id, $request->all(), Auth::id());
            return redirect()->route('destinos.index')->with('success', 'Destino actualizado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al actualizar destino: ' . $e->getMessage());
        }
    }

    public function toggleEstado(int $id)
    {
        try {
            $destino = \App\Modules\Catalogo\Models\Destino::findOrFail($id);
            $nuevoEstado = !$destino->activo;

            $this->catalogoService->cambiarEstadoDestino($id, $nuevoEstado, Auth::id());

            $msg = $nuevoEstado ? 'Destino activado correctamente.' : 'Destino desactivado correctamente.';
            return redirect()->route('destinos.index')->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
