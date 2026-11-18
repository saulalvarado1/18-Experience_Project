<?php

namespace App\Modules\PortalCliente\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Gateways\GatewayException;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Services\PagoOnlineService;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Exception;
use InvalidArgumentException;

class PortalClienteController extends Controller
{
    protected CotizacionService $cotizacionService;
    protected PagoService $pagoService;
    protected PagoOnlineService $pagoOnlineService;

    public function __construct(CotizacionService $cotizacionService, PagoService $pagoService, PagoOnlineService $pagoOnlineService)
    {
        $this->cotizacionService = $cotizacionService;
        $this->pagoService = $pagoService;
        $this->pagoOnlineService = $pagoOnlineService;
    }

    private function getClienteActual(): Cliente
    {
        $usuario = Auth::user();
        $cliente = Cliente::where('id_persona', $usuario->id_persona)->first();

        if (!$cliente) {
            // Si la persona existe pero no tiene registro en cliente, crear registro directo
            $cliente = Cliente::create([
                'id_persona' => $usuario->id_persona,
                'fecha_registro' => now(),
                'tipo_cliente' => 'DIRECTO',
                'activo' => 1,
            ]);
        }

        return $cliente;
    }

    public function dashboard()
    {
        $cliente = $this->getClienteActual();

        $cotizaciones = Cotizacion::where('id_cliente', $cliente->id_cliente)
            ->with(['detalles.producto', 'detalles.salida', 'reserva'])
            ->orderBy('id_cotizacion', 'desc')
            ->take(5)
            ->get();

        $reservas = Reserva::whereHas('cotizacion', fn($q) => $q->where('id_cliente', $cliente->id_cliente))
            ->with(['detalles.producto', 'detalles.salida'])
            ->orderBy('id_reserva', 'desc')
            ->get();

        $totalCotizaciones = Cotizacion::where('id_cliente', $cliente->id_cliente)->count();
        $totalReservas = $reservas->where('estado', '!=', 'CANCELADA')->count();
        $totalInvertido = $reservas->where('estado', '!=', 'CANCELADA')->sum('total_contratado');

        return view('portal.dashboard', compact('cliente', 'cotizaciones', 'reservas', 'totalCotizaciones', 'totalReservas', 'totalInvertido'));
    }

    public function cotizaciones()
    {
        $cliente = $this->getClienteActual();

        $cotizaciones = Cotizacion::where('id_cliente', $cliente->id_cliente)
            ->with(['detalles.producto', 'detalles.salida', 'pagos.metodoPago', 'reserva'])
            ->orderBy('id_cotizacion', 'desc')
            ->paginate(10);

        // Resumen de pagos por cotización (misma lógica que PagoService, sin consultas extra).
        $resumen = $cotizaciones->getCollection()->mapWithKeys(fn (Cotizacion $c) => [
            $c->id_cotizacion => $this->resumenPagos($c),
        ]);

        // El cliente solo puede declarar pagos que Contabilidad valida después (RN10/RN11).
        // Los métodos auto-validados (efectivo, tarjeta, pasarela) no se ofrecen para vouchers.
        $metodos = MetodoPago::where('activo', true)->where('requiere_validacion', true)->get();

        $pagoOnline = null;
        $publicKey = config('services.culqi.public_key');
        if ($publicKey) {
            try {
                $metodoOnline = $this->pagoOnlineService->metodoOnline();
                $pagoOnline = [
                    'public_key' => $publicKey,
                    'porcentaje_recargo' => $metodoOnline->aplica_recargo ? (float) $metodoOnline->porcentaje_recargo : 0.0,
                    'email' => Auth::user()->persona?->correo,
                ];
            } catch (InvalidArgumentException) {
                $pagoOnline = null; // Método online desactivado.
            }
        }

        return view('portal.cotizaciones', compact('cotizaciones', 'metodos', 'pagoOnline', 'resumen'));
    }

    /**
     * @return array{total: float, validado: float, pendiente: float, porcentaje_anticipo: float,
     *               anticipo: float, anticipo_cubierto: bool, pagable: float, pct_validado: float,
     *               pct_pendiente: float, tiene_reserva: bool, paso: int}
     */
    private function resumenPagos(Cotizacion $c): array
    {
        $total = (float) $c->total;
        $validado = round($c->pagos->where('estado', 'VALIDADO')
            ->sum(fn ($p) => (float) $p->monto_base - (float) $p->monto_base_devuelto), 2);
        $pendiente = round($c->pagos->where('estado', 'PENDIENTE_VALIDACION')
            ->sum(fn ($p) => (float) $p->monto_base - (float) $p->monto_base_devuelto), 2);

        // RN05: el anticipo es el mayor porcentaje_reserva de los productos (50 % por defecto).
        $porcentaje = (float) ($c->detalles->max(fn ($d) => $d->producto->porcentaje_reserva ?? 50.0) ?: 50.0);
        $anticipo = round($total * $porcentaje / 100, 2);
        $tieneReserva = $c->reserva && in_array($c->reserva->estado, ['CONFIRMADA', 'COMPLETADA'], true);
        $cubierto = $validado + 0.001 >= $anticipo;

        $paso = match (true) {
            $tieneReserva => 4,
            $c->estado === 'ACEPTADA' && $cubierto => 3,
            $c->estado === 'ACEPTADA' => 2,
            default => 1,
        };

        return [
            'total' => $total,
            'validado' => $validado,
            'pendiente' => $pendiente,
            'porcentaje_anticipo' => $porcentaje,
            'anticipo' => $anticipo,
            'anticipo_cubierto' => $cubierto,
            'pagable' => max(0.0, round($total - $validado - $pendiente, 2)),
            'pct_validado' => $total > 0 ? min(100, round($validado * 100 / $total, 1)) : 0,
            'pct_pendiente' => $total > 0 ? min(100, round($pendiente * 100 / $total, 1)) : 0,
            'tiene_reserva' => $tieneReserva,
            'paso' => $paso,
        ];
    }

    public function aceptarCotizacion($id)
    {
        $cliente = $this->getClienteActual();
        $cotizacion = Cotizacion::where('id_cliente', $cliente->id_cliente)->findOrFail($id);

        try {
            $this->cotizacionService->cambiarEstado(
                $cotizacion->id_cotizacion,
                'ACEPTADA',
                Auth::id(),
                'Aceptada por el cliente desde el Portal Web.'
            );

            return redirect()->route('portal.cotizaciones')
                ->with('success', "¡Cotización {$cotizacion->codigo} aceptada! Ahora puedes adjuntar tu comprobante de pago para asegurar tus cupos.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reservas()
    {
        $cliente = $this->getClienteActual();

        $reservas = Reserva::whereHas('cotizacion', fn($q) => $q->where('id_cliente', $cliente->id_cliente))
            ->with([
                'cotizacion',
                'detalles.producto',
                'detalles.salida',
                'detalles.pasajeros.persona',
            ])
            ->orderBy('id_reserva', 'desc')
            ->paginate(10);

        return view('portal.reservas', compact('reservas'));
    }

    public function voucherPdf($id)
    {
        $cliente = $this->getClienteActual();
        $reserva = Reserva::whereHas('cotizacion', fn($q) => $q->where('id_cliente', $cliente->id_cliente))
            ->with([
                'cotizacion.cliente.persona',
                'detalles.producto',
                'detalles.salida',
                'detalles.pasajeros.persona',
            ])
            ->findOrFail($id);

        $logoPath = public_path('img/logo-18-grados-sur.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('portal.voucher-pdf', [
            'reserva' => $reserva,
            'logoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("Voucher_Viaje_{$reserva->codigo}.pdf");
    }

    public function subirPago(Request $request)
    {
        $cliente = $this->getClienteActual();

        $validated = $request->validate([
            'id_cotizacion' => 'required|integer',
            // Solo métodos que requieren validación contable: un cliente no puede auto-validar su pago.
            'id_metodo_pago' => [
                'required',
                'integer',
                Rule::exists('metodo_pago', 'id_metodo_pago')
                    ->where('activo', true)
                    ->where('requiere_validacion', true),
            ],
            'monto_base' => 'required|numeric|min:0.01',
            'numero_operacion' => 'required|string|max:100',
            'comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observacion' => 'nullable|string|max:500',
        ]);

        // Asegurar que la cotización pertenece al cliente en sesión
        $cotizacion = Cotizacion::where('id_cliente', $cliente->id_cliente)
            ->where('id_cotizacion', $validated['id_cotizacion'])
            ->firstOrFail();

        try {
            $pago = $this->pagoService->registrar(
                $validated,
                $request->file('comprobante'),
                Auth::id()
            );

            return redirect()->route('portal.cotizaciones')
                ->with('success', "¡Comprobante enviado exitosamente! Nuestro equipo contable validará el abono de S/ {$pago->monto_base} para confirmar tu reserva.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Cobra un token de Culqi Checkout (AJAX). El navegador solo envía el token y el monto base;
     * recargo, total y validaciones se calculan en el servidor.
     */
    public function pagarOnline(Request $request): JsonResponse
    {
        $cliente = $this->getClienteActual();

        $validated = $request->validate([
            'id_cotizacion' => 'required|integer',
            'token' => ['required', 'string', 'max:100', 'regex:/^[a-z]{3}_[A-Za-z0-9_]+$/'],
            'monto_base' => 'required|numeric|min:0.01',
        ]);

        // La cotización debe pertenecer al cliente en sesión (404 si no).
        $cotizacion = Cotizacion::where('id_cliente', $cliente->id_cliente)
            ->where('id_cotizacion', $validated['id_cotizacion'])
            ->firstOrFail();

        try {
            $pago = $this->pagoOnlineService->pagarConToken(
                $cotizacion,
                $validated['token'],
                (float) $validated['monto_base'],
                Auth::user()
            );
        } catch (GatewayException $e) {
            Log::error('Pago online: pasarela no disponible', ['error' => $e->getMessage()]);
            return response()->json([
                'ok' => false,
                'mensaje' => 'La pasarela de pagos no está disponible. No se realizó ningún cobro; inténtalo más tarde.',
            ], 503);
        } catch (InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'mensaje' => $e->getMessage()], 422);
        }

        session()->flash('success', "¡Pago de S/ " . number_format((float) $pago->monto_total_pagado, 2)
            . " confirmado! Operación {$pago->numero_operacion}. Ya puedes coordinar la confirmación de tu reserva.");

        return response()->json([
            'ok' => true,
            'id_pago' => $pago->id_pago,
            'redirect' => route('portal.cotizaciones'),
        ]);
    }

    public function tours()
    {
        $productos = ProductoTuristico::where('activo', 1)
            ->with(['destinos', 'salidas' => fn($q) => $q->where('estado', 'PROGRAMADA')->where('fecha', '>=', now()->toDateString())->orderBy('fecha')])
            ->get();

        return view('portal.tours', compact('productos'));
    }

    public function solicitarCotizacion(Request $request)
    {
        $cliente = $this->getClienteActual();

        $validated = $request->validate([
            'id_producto' => 'required|integer|exists:producto_turistico,id_producto',
            'id_salida' => 'nullable|integer|exists:salida,id_salida',
            'cantidad_pasajeros' => 'required|integer|min:1|max:20',
            'observacion' => 'nullable|string|max:500',
        ]);

        try {
            $prod = ProductoTuristico::findOrFail($validated['id_producto']);

            $cotizacion = $this->cotizacionService->crear(
                [
                    'id_cliente' => $cliente->id_cliente,
                    'vigencia_hasta' => now()->addDays(7)->format('Y-m-d H:i:s'),
                    'observacion' => "Solicitada en línea por el cliente. " . ($validated['observacion'] ?? ''),
                ],
                [
                    [
                        'id_producto' => $prod->id_producto,
                        'id_salida' => $validated['id_salida'] ?? null,
                        'cantidad_pasajeros' => (int) $validated['cantidad_pasajeros'],
                        'precio_unitario' => (float) $prod->precio_base,
                    ]
                ],
                Auth::id()
            );

            return redirect()->route('portal.cotizaciones')
                ->with('success', "¡Cotización {$cotizacion->codigo} generada exitosamente! Puedes revisarla y descargar tu presupuesto en PDF.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
