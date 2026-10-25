<?php

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\Servicio;
use App\Modules\Catalogo\Services\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductoController extends Controller
{
    protected CatalogoService $catalogoService;

    public function __construct(CatalogoService $catalogoService)
    {
        $this->catalogoService = $catalogoService;
    }

    public function index(Request $request)
    {
        $termino = $request->query('buscar');
        $tipo = $request->query('tipo', 'TODOS');
        $estado = $request->query('estado', 'TODOS');

        $productos = $this->catalogoService->listarProductos($termino, $tipo, $estado);
        $destinos = Destino::where('activo', 1)->orderBy('nombre')->get();
        $servicios = Servicio::where('activo', 1)->orderBy('nombre')->get();

        return view('tours.index', compact('productos', 'destinos', 'servicios', 'termino', 'tipo', 'estado'));
    }

    public function store(Request $request)
    {
        try {
            $this->catalogoService->crearProducto($request->all(), Auth::id());
            return redirect()->route('tours.index')->with('success', 'Tour o Paquete creado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al crear tour: ' . $e->getMessage());
        }
    }

    public function cambiarPrecio(Request $request, int $id)
    {
        // RN04: Solo el GERENTE puede modificar precios base
        if (!Auth::user()->tieneRol('GERENTE')) {
            return back()->with('error', 'Solo el GERENTE tiene autorización para modificar precios base.');
        }

        try {
            $nuevoPrecio = (float) $request->input('nuevo_precio');
            $motivo = $request->input('motivo');

            $this->catalogoService->cambiarPrecio($id, $nuevoPrecio, $motivo, Auth::id());

            return redirect()->route('tours.index')->with('success', 'Precio actualizado y registrado en auditoría.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cambiar precio: ' . $e->getMessage());
        }
    }
}
