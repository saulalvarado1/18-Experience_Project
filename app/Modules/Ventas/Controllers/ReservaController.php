<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReservaController extends Controller
{
    protected ReservaService $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }

    public function index(Request $request)
    {
        $filtros = [
            'estado' => $request->query('estado', 'TODOS'),
            'termino' => $request->query('termino'),
        ];

        $reservas = $this->reservaService->listar($filtros);

        // Cotizaciones aceptadas listas para reservar
        $cotizacionesListas = Cotizacion::where('estado', 'ACEPTADA')
            ->whereDoesntHave('reserva')
            ->with(['cliente.persona', 'detalles.producto', 'detalles.salida'])
            ->get();

        return view('reservas.index', compact('reservas', 'cotizacionesListas', 'filtros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cotizacion' => 'required|integer|exists:cotizacion,id_cotizacion',
            'observacion' => 'nullable|string|max:500',
        ]);

        try {
            $reserva = $this->reservaService->confirmarDesdeCotizacion(
                (int) $validated['id_cotizacion'],
                Auth::id(),
                [],
                $validated['observacion'] ?? null
            );

            return redirect()->route('reservas.index')
                ->with('success', "¡Reserva {$reserva->codigo} confirmada exitosamente!");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $reserva = $this->reservaService->obtenerPorId((int) $id);
        return response()->json($reserva);
    }

    public function cancelar($id, Request $request)
    {
        $request->validate([
            'motivo' => 'required|string|min:3|max:500',
        ]);

        try {
            $reserva = $this->reservaService->cancelar(
                (int) $id,
                $request->input('motivo'),
                Auth::id()
            );

            return redirect()->route('reservas.index')
                ->with('warning', "Reserva {$reserva->codigo} cancelada y cupos liberados.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function completar($id)
    {
        try {
            $reserva = $this->reservaService->completar((int) $id, Auth::id());

            return redirect()->route('reservas.index')
                ->with('success', "Reserva {$reserva->codigo} completada exitosamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
