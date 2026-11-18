<?php

namespace App\Modules\Ventas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Clientes\Services\ClienteService;
use App\Modules\Finanzas\Services\PagoOnlineService;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\ReservaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use InvalidArgumentException;

class ReservaController extends Controller
{
    protected ReservaService $reservaService;
    protected PagoOnlineService $pagoOnlineService;
    protected ClienteService $clienteService;

    public function __construct(ReservaService $reservaService, PagoOnlineService $pagoOnlineService, ClienteService $clienteService)
    {
        $this->reservaService = $reservaService;
        $this->pagoOnlineService = $pagoOnlineService;
        $this->clienteService = $clienteService;
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

    /**
     * CU20 paso 2: valida que la cotización sea reservable y abre el formulario de pasajeros.
     */
    public function create(Request $request)
    {
        $cotizacion = Cotizacion::with(['cliente.persona', 'detalles.producto', 'detalles.salida'])
            ->findOrFail((int) $request->query('cotizacion'));

        try {
            $resumen = $this->reservaService->verificarReservable($cotizacion);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('reservas.index')->with('error', $e->getMessage());
        }

        $puntoRecojoDefecto = ReservaService::PUNTO_RECOJO_DEFECTO;

        return view('reservas.create', compact('cotizacion', 'resumen', 'puntoRecojoDefecto'));
    }

    /**
     * CU20 pasos 5-6: confirma la reserva con los pasajeros de cada servicio.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cotizacion' => 'required|integer|exists:cotizacion,id_cotizacion',
            'observacion' => 'nullable|string|max:500',
            'servicios' => 'required|array',
            'servicios.*.punto_recojo' => 'required|string|max:255',
            'servicios.*.pasajeros' => 'required|array',
            'servicios.*.pasajeros.*.tipo_documento' => 'nullable|in:DNI,PASAPORTE,CARNET_EXTRANJERIA',
            'servicios.*.pasajeros.*.numero_documento' => 'nullable|string|max:30',
            'servicios.*.pasajeros.*.nombres' => 'nullable|string|max:100',
            'servicios.*.pasajeros.*.apellidos' => 'nullable|string|max:120',
            'servicios.*.pasajeros.*.fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'servicios.*.pasajeros.*.nacionalidad' => 'nullable|string|max:60',
            'servicios.*.pasajeros.*.restriccion_alimentaria' => 'nullable|string|max:500',
            'servicios.*.pasajeros.*.condicion_particular' => 'nullable|string|max:500',
        ], [
            'servicios.*.punto_recojo.required' => 'Ingrese el punto de recojo de cada servicio.',
        ]);

        try {
            $reserva = $this->reservaService->confirmarDesdeCotizacion(
                (int) $validated['id_cotizacion'],
                Auth::id(),
                $validated['servicios'],
                $validated['observacion'] ?? null
            );

            return redirect()->route('reservas.index')
                ->with('success', "¡Reserva {$reserva->codigo} confirmada con sus pasajeros!");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * CU21 paso 2: formulario de modificación con los servicios activos y sus pasajeros actuales.
     */
    public function edit(int $id)
    {
        $reserva = Reserva::with([
            'cotizacion.cliente.persona',
            'detalles' => fn ($q) => $q->where('estado', 'ACTIVO')->orderBy('id_detalle_reserva'),
            'detalles.producto',
            'detalles.salida',
            'detalles.pasajeros.persona',
        ])->findOrFail($id);

        if ($reserva->estado !== 'CONFIRMADA') {
            return redirect()->route('reservas.index')->with('error', 'Solo se pueden modificar reservas CONFIRMADAS.');
        }

        return view('reservas.edit', compact('reserva'));
    }

    /**
     * CU21 pasos 3-4: guarda recojo, pasajeros y observación (registra EDITAR_RESERVA).
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'observacion' => 'nullable|string|max:500',
            'servicios' => 'required|array',
            'servicios.*.punto_recojo' => 'required|string|max:255',
            'servicios.*.pasajeros' => 'required|array',
            'servicios.*.pasajeros.*.tipo_documento' => 'nullable|in:DNI,PASAPORTE,CARNET_EXTRANJERIA',
            'servicios.*.pasajeros.*.numero_documento' => 'nullable|string|max:30',
            'servicios.*.pasajeros.*.nombres' => 'nullable|string|max:100',
            'servicios.*.pasajeros.*.apellidos' => 'nullable|string|max:120',
            'servicios.*.pasajeros.*.fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'servicios.*.pasajeros.*.restriccion_alimentaria' => 'nullable|string|max:500',
            'servicios.*.pasajeros.*.condicion_particular' => 'nullable|string|max:500',
        ], [
            'servicios.*.punto_recojo.required' => 'Ingrese el punto de recojo de cada servicio.',
        ]);

        try {
            $reserva = $this->reservaService->modificar($id, $validated['servicios'], $validated['observacion'] ?? null, Auth::id());

            return redirect()->route('reservas.index')->with('success', "Reserva {$reserva->codigo} actualizada.");
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * CU20 "BUSCAR PERSONA": devuelve la persona registrada con ese documento (o encontrado=false).
     */
    public function buscarPersona(Request $request): JsonResponse
    {
        $request->validate([
            'tipo_documento' => 'required|in:DNI,PASAPORTE,CARNET_EXTRANJERIA',
            'numero_documento' => 'required|string|max:30',
        ]);

        $persona = $this->clienteService->buscarPersonaPorDocumento(
            $request->query('tipo_documento'),
            $request->query('numero_documento')
        );

        if (!$persona) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado' => true,
            'nombres' => $persona->nombres,
            'apellidos' => $persona->apellidos,
            'fecha_nacimiento' => $persona->fecha_nacimiento?->format('Y-m-d'),
            'nacionalidad' => $persona->nacionalidad,
        ]);
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

            $mensaje = "Reserva {$reserva->codigo} cancelada y cupos liberados.";

            // La devolución de dinero no es automática: la decide Contabilidad/Gerencia (puede haber penalidad).
            $pagosOnline = $this->pagoOnlineService->pagosDevolvibles($reserva->id_cotizacion);
            if ($pagosOnline->isNotEmpty()) {
                $total = $pagosOnline->sum(fn ($p) => $p->montoDevolvible());
                $mensaje .= " Tiene {$pagosOnline->count()} pago(s) online por S/ " . number_format($total, 2)
                    . ' que Contabilidad o Gerencia pueden devolver desde el módulo Pagos.';
            }

            return redirect()->route('reservas.index')->with('warning', $mensaje);
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
