<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Ventas\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Exception;

class PagoController extends Controller
{
    protected PagoService $pagoService;

    public function __construct(PagoService $pagoService)
    {
        $this->pagoService = $pagoService;
    }

    public function index(Request $request)
    {
        $filtros = [
            'estado' => $request->query('estado', 'TODOS'),
            'id_cotizacion' => $request->query('id_cotizacion'),
            'fecha_desde' => $request->query('fecha_desde'),
            'fecha_hasta' => $request->query('fecha_hasta'),
        ];

        $pagos = $this->pagoService->listar($filtros);
        $metodos = MetodoPago::where('activo', 1)->get();
        $cotizaciones = Cotizacion::whereIn('estado', ['ACEPTADA', 'VIGENTE'])
            ->with('cliente.persona')
            ->orderBy('id_cotizacion', 'desc')
            ->take(50)
            ->get();

        return view('pagos.index', compact('pagos', 'metodos', 'cotizaciones', 'filtros'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_cotizacion' => 'required|integer|exists:cotizacion,id_cotizacion',
            'id_metodo_pago' => 'required|integer|exists:metodo_pago,id_metodo_pago',
            'monto_base' => 'required|numeric|min:0.01',
            'numero_operacion' => 'nullable|string|max:100',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'observacion' => 'nullable|string|max:500',
        ]);

        try {
            $comprobanteFile = $request->file('comprobante');
            $pago = $this->pagoService->registrar($validated, $comprobanteFile, Auth::id());

            return redirect()->route('pagos.index')
                ->with('success', "Pago registrado exitosamente (Monto base: S/ {$pago->monto_base}, Total: S/ {$pago->monto_total_pagado}).");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function validar($id, Request $request)
    {
        $rol = Auth::user()->rol->nombre ?? '';
        if (!in_array($rol, ['GERENTE', 'CONTABILIDAD'], true)) {
            abort(403, 'Solo Contabilidad y Gerencia pueden validar pagos.');
        }

        try {
            $this->pagoService->validar((int) $id, Auth::id(), $request->input('observacion'));
            return redirect()->route('pagos.index')->with('success', "Pago ID #{$id} validado correctamente.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rechazar($id, Request $request)
    {
        $rol = Auth::user()->rol->nombre ?? '';
        if (!in_array($rol, ['GERENTE', 'CONTABILIDAD'], true)) {
            abort(403, 'Solo Contabilidad y Gerencia pueden rechazar pagos.');
        }

        $request->validate([
            'motivo' => 'required|string|min:3|max:500',
        ]);

        try {
            $this->pagoService->rechazar((int) $id, $request->input('motivo'), Auth::id());
            return redirect()->route('pagos.index')->with('warning', "Pago ID #{$id} ha sido rechazado.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function comprobante($id)
    {
        $pago = Pago::findOrFail($id);
        if (!$pago->ruta_comprobante || !Storage::disk('public')->exists($pago->ruta_comprobante)) {
            abort(404, 'Comprobante no encontrado.');
        }

        return Storage::disk('public')->response($pago->ruta_comprobante);
    }
}
