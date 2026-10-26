<?php

namespace App\Modules\PortalCliente\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Services\CotizacionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class PortalClienteController extends Controller
{
    protected CotizacionService $cotizacionService;
    protected PagoService $pagoService;

    public function __construct(CotizacionService $cotizacionService, PagoService $pagoService)
    {
        $this->cotizacionService = $cotizacionService;
        $this->pagoService = $pagoService;
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
            ->with(['detalles.producto', 'detalles.salida'])
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
            ->with(['detalles.producto', 'detalles.salida', 'pagos.metodoPago'])
            ->orderBy('id_cotizacion', 'desc')
            ->paginate(10);

        $metodos = MetodoPago::where('activo', 1)->get();

        return view('portal.cotizaciones', compact('cotizaciones', 'metodos'));
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
            'id_metodo_pago' => 'required|integer|exists:metodo_pago,id_metodo_pago',
            'monto_base' => 'required|numeric|min:0.01',
            'numero_operacion' => 'nullable|string|max:100',
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
