<?php

namespace App\Modules\Clientes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Services\ClienteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteController extends Controller
{
    protected ClienteService $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function index(Request $request)
    {
        $termino = $request->query('buscar');
        $estado = $request->query('estado', 'TODOS');

        $clientes = $this->clienteService->listar($termino, $estado);

        return view('clientes.index', compact('clientes', 'termino', 'estado'));
    }

    public function store(Request $request)
    {
        try {
            $datosPersona = $request->only([
                'tipo_documento',
                'numero_documento',
                'nombres',
                'apellidos',
                'fecha_nacimiento',
                'nacionalidad',
                'genero',
                'telefono',
                'correo',
            ]);

            $tipoCliente = $request->input('tipo_cliente', 'DIRECTO');

            $this->clienteService->registrar($datosPersona, $tipoCliente, Auth::id());

            return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Ocurrió un error al registrar el cliente: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $datosPersona = $request->only([
                'tipo_documento',
                'numero_documento',
                'nombres',
                'apellidos',
                'fecha_nacimiento',
                'nacionalidad',
                'genero',
                'telefono',
                'correo',
            ]);

            $tipoCliente = $request->input('tipo_cliente', 'DIRECTO');

            $this->clienteService->actualizar($id, $datosPersona, $tipoCliente, Auth::id());

            return redirect()->route('clientes.index')->with('success', 'Cliente actualizado correctamente.');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Ocurrió un error al actualizar el cliente: ' . $e->getMessage());
        }
    }

    public function toggleEstado(int $id)
    {
        try {
            $cliente = \App\Modules\Clientes\Models\Cliente::findOrFail($id);
            $nuevoEstado = !$cliente->activo;

            $this->clienteService->cambiarEstado($id, $nuevoEstado, Auth::id());

            $mensaje = $nuevoEstado ? 'Cliente activado correctamente.' : 'Cliente desactivado correctamente.';
            return redirect()->route('clientes.index')->with('success', $mensaje);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function historial(int $id)
    {
        try {
            $data = $this->clienteService->obtenerHistorial($id);

            return response()->json([
                'success' => true,
                'cliente' => [
                    'nombre' => $data['cliente']->persona->nombre_completo,
                    'documento' => "{$data['cliente']->persona->tipo_documento} {$data['cliente']->persona->numero_documento}",
                    'telefono' => $data['cliente']->persona->telefono ?? 'No registrado',
                ],
                'reservas' => $data['reservas'],
                'cotizaciones' => $data['cotizaciones'],
                'pagos' => $data['pagos'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }
}
