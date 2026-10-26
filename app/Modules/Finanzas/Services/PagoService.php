<?php

namespace App\Modules\Finanzas\Services;

use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PagoService
{
    public function montoValidado(int $idCotizacion): float
    {
        return (float) Pago::where('id_cotizacion', $idCotizacion)
            ->where('estado', 'VALIDADO')
            ->sum('monto_base');
    }

    public function saldoPendiente(int $idCotizacion): float
    {
        $cotizacion = Cotizacion::findOrFail($idCotizacion);
        $validado = $this->montoValidado($idCotizacion);
        $saldo = (float) $cotizacion->total - $validado;
        return max(0.0, round($saldo, 2));
    }

    public function listar(array $filtros = [])
    {
        $query = Pago::with(['metodoPago', 'cotizacion.cliente.persona', 'usuarioRegistro', 'usuarioValidacion'])
            ->orderBy('id_pago', 'desc');

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'TODOS') {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['id_cotizacion'])) {
            $query->where('id_cotizacion', $filtros['id_cotizacion']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_pago', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_pago', '<=', $filtros['fecha_hasta']);
        }

        return $query->paginate(15);
    }

    public function registrar(array $datos, ?UploadedFile $comprobante, int $idUsuario): Pago
    {
        return DB::transaction(function () use ($datos, $comprobante, $idUsuario) {
            $idCotizacion = (int) $datos['id_cotizacion'];
            $cotizacion = Cotizacion::whereKey($idCotizacion)->lockForUpdate()->firstOrFail();

            // Solo cotizaciones ACEPTADA o con reserva activa pueden recibir pagos
            $tieneReserva = Reserva::where('id_cotizacion', $idCotizacion)
                ->whereIn('estado', ['CONFIRMADA', 'COMPLETADA'])
                ->exists();

            if ($cotizacion->estado !== 'ACEPTADA' && !$tieneReserva) {
                throw new InvalidArgumentException("Solo se pueden registrar pagos a cotizaciones ACEPTADAS o con reserva confirmada.");
            }

            $montoBase = round((float) $datos['monto_base'], 2);
            if ($montoBase <= 0) {
                throw new InvalidArgumentException("El monto base debe ser mayor a 0.");
            }

            // Validar que suma de montos no rechazados no exceda el total
            $acumuladoNoRechazado = (float) Pago::where('id_cotizacion', $idCotizacion)
                ->whereIn('estado', ['PENDIENTE_VALIDACION', 'VALIDADO'])
                ->sum('monto_base');

            if (($acumuladoNoRechazado + $montoBase) > ((float) $cotizacion->total + 0.01)) {
                $maximoPermitido = max(0.0, round((float) $cotizacion->total - $acumuladoNoRechazado, 2));
                throw new InvalidArgumentException("El monto excede el saldo restante de la cotización. Máximo registrable: S/ {$maximoPermitido}");
            }

            $metodo = MetodoPago::findOrFail((int) $datos['id_metodo_pago']);
            if (!$metodo->activo) {
                throw new InvalidArgumentException("El método de pago seleccionado no está activo.");
            }

            $recargo = 0.00;
            if ($metodo->aplica_recargo && (float) $metodo->porcentaje_recargo > 0) {
                $recargo = round($montoBase * ((float) $metodo->porcentaje_recargo / 100), 2);
            }
            $montoTotal = round($montoBase + $recargo, 2);

            $rutaComprobante = null;
            if ($comprobante && $comprobante->isValid()) {
                $rutaComprobante = $comprobante->store('comprobantes', 'public');
            }

            $requiereVal = (bool) $metodo->requiere_validacion;
            $estadoInicial = $requiereVal ? 'PENDIENTE_VALIDACION' : 'VALIDADO';
            $idValidador = $requiereVal ? null : $idUsuario;
            $fechaVal = $requiereVal ? null : now();

            $pago = Pago::create([
                'id_cotizacion' => $idCotizacion,
                'id_metodo_pago' => $metodo->id_metodo_pago,
                'id_usuario_registro' => $idUsuario,
                'id_usuario_validacion' => $idValidador,
                'fecha_pago' => now(),
                'moneda' => $cotizacion->moneda ?? 'PEN',
                'monto_base' => $montoBase,
                'recargo' => $recargo,
                'monto_total_pagado' => $montoTotal,
                'estado' => $estadoInicial,
                'numero_operacion' => $datos['numero_operacion'] ?? null,
                'ruta_comprobante' => $rutaComprobante,
                'fecha_validacion' => $fechaVal,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            Auditoria::registrar(
                $idUsuario,
                'REGISTRAR_PAGO',
                'pago',
                $pago->id_pago,
                "Pago de S/ {$montoBase} (+recargo S/ {$recargo}) con {$metodo->nombre} para cotización {$cotizacion->codigo}. Estado: {$estadoInicial}"
            );

            return $pago;
        });
    }

    public function validar(int $idPago, int $idValidador, ?string $obs = null): Pago
    {
        return DB::transaction(function () use ($idPago, $idValidador, $obs) {
            $pago = Pago::whereKey($idPago)->lockForUpdate()->firstOrFail();

            if ($pago->estado !== 'PENDIENTE_VALIDACION') {
                throw new InvalidArgumentException("Solo se pueden validar pagos en estado PENDIENTE_VALIDACION.");
            }

            $pago->estado = 'VALIDADO';
            $pago->id_usuario_validacion = $idValidador;
            $pago->fecha_validacion = now();
            if (!empty($obs)) {
                $pago->observacion = ($pago->observacion ? $pago->observacion . " | " : "") . "Validación: " . $obs;
            }
            $pago->save();

            Auditoria::registrar(
                $idValidador,
                'VALIDAR_PAGO',
                'pago',
                $pago->id_pago,
                "Pago ID {$idPago} por S/ {$pago->monto_base} VALIDADO exitosamente."
            );

            return $pago;
        });
    }

    public function rechazar(int $idPago, string $motivo, int $idValidador): Pago
    {
        if (trim($motivo) === '') {
            throw new InvalidArgumentException("El motivo de rechazo es obligatorio.");
        }

        return DB::transaction(function () use ($idPago, $motivo, $idValidador) {
            $pago = Pago::whereKey($idPago)->lockForUpdate()->firstOrFail();

            if ($pago->estado !== 'PENDIENTE_VALIDACION') {
                throw new InvalidArgumentException("Solo se pueden rechazar pagos en estado PENDIENTE_VALIDACION.");
            }

            $pago->estado = 'RECHAZADO';
            $pago->id_usuario_validacion = $idValidador;
            $pago->fecha_validacion = now();
            $pago->observacion = ($pago->observacion ? $pago->observacion . " | " : "") . "RECHAZO: " . trim($motivo);
            $pago->save();

            Auditoria::registrar(
                $idValidador,
                'RECHAZAR_PAGO',
                'pago',
                $pago->id_pago,
                "Pago ID {$idPago} RECHAZADO. Motivo: {$motivo}"
            );

            return $pago;
        });
    }
}
