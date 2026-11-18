<?php

namespace App\Modules\Catalogo\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\ProductoTuristico;
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

    /** CU09 flujo 1a – Editar producto. */
    public function update(Request $request, int $id)
    {
        try {
            $this->catalogoService->actualizarProducto($id, $request->all(), Auth::id());
            return redirect()->route('tours.index')->with('success', 'Tour actualizado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /** CU09 flujo 1b – Activar / desactivar producto. */
    public function toggleEstado(int $id)
    {
        $producto = $this->catalogoService->cambiarEstadoProducto($id, Auth::id());
        return redirect()->route('tours.index')
            ->with('success', "El tour {$producto->codigo} ahora está " . ($producto->activo ? 'activo.' : 'inactivo.'));
    }

    /**
     * CU10 – Composición del producto: destinos (con orden de visita) y servicios.
     */
    public function composicion(int $id)
    {
        $producto = ProductoTuristico::with([
            'destinos' => fn ($q) => $q->orderBy('producto_destino.orden_visita'),
            'servicios' => fn ($q) => $q->orderBy('servicio.nombre'),
        ])->findOrFail($id);

        $destinosDisponibles = Destino::where('activo', true)
            ->whereNotIn('id_destino', $producto->destinos->pluck('id_destino'))
            ->orderBy('nombre')->get();
        $serviciosDisponibles = Servicio::where('activo', true)
            ->whereNotIn('id_servicio', $producto->servicios->pluck('id_servicio'))
            ->orderBy('nombre')->get();
        $siguienteOrden = ((int) $producto->destinos->max('pivot.orden_visita')) + 1;

        return view('tours.composicion', compact('producto', 'destinosDisponibles', 'serviciosDisponibles', 'siguienteOrden'));
    }

    public function asignarDestino(Request $request, int $id)
    {
        $data = $request->validate([
            'id_destino' => 'required|integer',
            'orden_visita' => 'required|integer',
            'observacion' => 'nullable|string|max:500',
        ]);

        return $this->ejecutarComposicion($id, fn () => $this->catalogoService->asignarDestino(
            $id, (int) $data['id_destino'], (int) $data['orden_visita'], $data['observacion'] ?? null, Auth::id()
        ), 'Destino asignado al producto.');
    }

    public function quitarDestino(int $id, int $idDestino)
    {
        return $this->ejecutarComposicion($id, fn () => $this->catalogoService->quitarDestino($id, $idDestino, Auth::id()),
            'Destino quitado del producto.');
    }

    public function asignarServicio(Request $request, int $id)
    {
        $data = $request->validate([
            'id_servicio' => 'required|integer',
            'incluido' => 'nullable|boolean',
            'precio_adicional' => 'nullable|numeric|min:0',
            'observacion' => 'nullable|string|max:500',
        ]);

        return $this->ejecutarComposicion($id, fn () => $this->catalogoService->asignarServicio(
            $id, (int) $data['id_servicio'], $request->boolean('incluido'), (float) ($data['precio_adicional'] ?? 0),
            $data['observacion'] ?? null, Auth::id()
        ), 'Servicio asignado al producto.');
    }

    public function quitarServicio(int $id, int $idServicio)
    {
        return $this->ejecutarComposicion($id, fn () => $this->catalogoService->quitarServicio($id, $idServicio, Auth::id()),
            'Servicio quitado del producto.');
    }

    private function ejecutarComposicion(int $id, callable $accion, string $mensaje)
    {
        try {
            $accion();
            return redirect()->route('tours.composicion', $id)->with('success', $mensaje);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('tours.composicion', $id)->withInput()->with('error', $e->getMessage());
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
