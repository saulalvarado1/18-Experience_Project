<?php

namespace App\Modules\Finanzas\Services;

use App\Modules\Finanzas\Gateways\ChargeResult;
use App\Modules\Finanzas\Gateways\PaymentGatewayInterface;
use App\Modules\Finanzas\Models\DevolucionPago;
use App\Modules\Finanzas\Models\MetodoPago;
use App\Modules\Finanzas\Models\Pago;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Usuario;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\Reserva;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Pagos online mediante pasarela (Checkout + Webhook).
 *
 * Flujo:
 *  1. El navegador tokeniza la tarjeta/Yape con el checkout de la pasarela (los datos nunca llegan aquí).
 *  2. pagarConToken(): el servidor calcula el monto, cobra el token y registra el pago VALIDADO.
 *  3. El webhook llama a registrarDesdeWebhook(): re-consulta el cargo en la API y lo registra
 *     solo si aún no existe (idempotente por numero_operacion = ID del cargo).
 *
 * El registro final siempre pasa por PagoService::registrar() para respetar RN10, recargos,
 * el tope del total de la cotización y la auditoría (RN16).
 */
class PagoOnlineService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly PagoService $pagoService,
    ) {
    }

    public function metodoOnline(): MetodoPago
    {
        $metodo = MetodoPago::where('nombre', config('services.pasarela.metodo_pago'))->first();

        if (!$metodo || !$metodo->activo) {
            throw new InvalidArgumentException('El pago online no está disponible en este momento.');
        }

        return $metodo;
    }

    /**
     * Monto que aún se puede pagar: total menos pagos no rechazados (misma regla que PagoService).
     */
    public function maximoPagable(Cotizacion $cotizacion): float
    {
        $acumulado = (float) Pago::where('id_cotizacion', $cotizacion->id_cotizacion)
            ->whereIn('estado', ['PENDIENTE_VALIDACION', 'VALIDADO'])
            ->sum(DB::raw('monto_base - monto_base_devuelto'));

        return max(0.0, round((float) $cotizacion->total - $acumulado, 2));
    }

    /**
     * @return array{monto_base: float, recargo: float, total: float, centimos: int}
     */
    public function calcularCargo(float $montoBase, ?MetodoPago $metodo = null): array
    {
        $metodo ??= $this->metodoOnline();
        $montoBase = round($montoBase, 2);

        $recargo = 0.0;
        if ($metodo->aplica_recargo && (float) $metodo->porcentaje_recargo > 0) {
            $recargo = round($montoBase * ((float) $metodo->porcentaje_recargo / 100), 2);
        }
        $total = round($montoBase + $recargo, 2);

        return [
            'monto_base' => $montoBase,
            'recargo' => $recargo,
            'total' => $total,
            'centimos' => (int) round($total * 100),
        ];
    }

    /**
     * Cobra un token del checkout y registra el pago. El monto lo decide el servidor.
     */
    public function pagarConToken(Cotizacion $cotizacion, string $token, float $montoBase, Usuario $usuario): Pago
    {
        $metodo = $this->metodoOnline();
        $this->validarCotizacionPagable($cotizacion);

        $montoBase = round($montoBase, 2);
        if ($montoBase <= 0) {
            throw new InvalidArgumentException('El monto a pagar debe ser mayor a 0.');
        }

        $maximo = $this->maximoPagable($cotizacion);
        if ($montoBase > $maximo + 0.001) {
            throw new InvalidArgumentException("El monto excede el saldo de la cotización. Máximo a pagar: S/ " . number_format($maximo, 2));
        }

        $email = $usuario->persona?->correo ?: $cotizacion->cliente?->persona?->correo;
        if (!$email) {
            throw new InvalidArgumentException('Registra un correo electrónico en tu perfil para pagar en línea.');
        }

        $cargo = $this->calcularCargo($montoBase, $metodo);

        $resultado = $this->gateway->createCharge([
            'token' => $token,
            'monto_centimos' => $cargo['centimos'],
            'moneda' => $cotizacion->moneda ?: 'PEN',
            'email' => $email,
            'descripcion' => "18 Grados Sur - Cotización {$cotizacion->codigo}",
            'metadata' => [
                'id_cotizacion' => $cotizacion->id_cotizacion,
                'codigo_cotizacion' => $cotizacion->codigo,
                'monto_base' => number_format($cargo['monto_base'], 2, '.', ''),
                'id_usuario' => $usuario->id_usuario,
            ],
        ]);

        if (!$resultado->exitoso || !$resultado->id) {
            Log::info('Pago online rechazado por la pasarela', [
                'id_cotizacion' => $cotizacion->id_cotizacion,
                'cargo' => $resultado->id,
                'mensaje' => $resultado->mensajeUsuario,
            ]);
            throw new InvalidArgumentException($resultado->mensajeUsuario);
        }

        try {
            return $this->registrarCargoConfirmado($resultado, $usuario->id_usuario);
        } catch (Throwable $e) {
            // El dinero ya fue cobrado: no se pierde el rastro. El webhook reintentará el registro
            // y, si tampoco puede, el cargo queda en el log para devolución manual.
            Log::critical('Cargo cobrado en la pasarela pero no registrado en pago', [
                'cargo' => $resultado->id,
                'id_cotizacion' => $cotizacion->id_cotizacion,
                'error' => $e->getMessage(),
            ]);
            throw new InvalidArgumentException(
                "Tu pago fue procesado (operación {$resultado->id}) pero no pudo registrarse automáticamente. "
                . 'Nuestro equipo lo revisará; no vuelvas a pagar.'
            );
        }
    }

    /**
     * Procesa una notificación de la pasarela. Devuelve null si el evento no aplica.
     */
    public function registrarDesdeWebhook(array $payload): ?Pago
    {
        $chargeId = $this->gateway->extractSucceededChargeId($payload);
        if (!$chargeId) {
            return null;
        }

        // Nunca se confía en el contenido del evento: se consulta el cargo en la API.
        $resultado = $this->gateway->retrieveCharge($chargeId);
        if (!$resultado->exitoso) {
            Log::warning('Webhook de pasarela con cargo no exitoso', ['cargo' => $chargeId]);
            return null;
        }

        return $this->registrarCargoConfirmado($resultado);
    }

    /**
     * Registra un cargo confirmado por la pasarela. Idempotente: si el cargo ya está
     * registrado devuelve el pago existente (wasRecentlyCreated = false).
     */
    public function registrarCargoConfirmado(ChargeResult $cargo, ?int $idUsuario = null): Pago
    {
        if (!$cargo->exitoso || !$cargo->id) {
            throw new InvalidArgumentException('El cargo no fue confirmado por la pasarela.');
        }

        $idCotizacion = (int) ($cargo->metadata['id_cotizacion'] ?? 0);
        $montoBase = round((float) ($cargo->metadata['monto_base'] ?? 0), 2);
        if ($idCotizacion <= 0 || $montoBase <= 0) {
            throw new InvalidArgumentException("El cargo {$cargo->id} no pertenece a una cotización del sistema.");
        }

        $metodo = $this->metodoOnline();

        try {
            return DB::transaction(function () use ($cargo, $idCotizacion, $montoBase, $metodo, $idUsuario) {
                // Serializa los registros concurrentes de la misma cotización (checkout + webhook).
                $cotizacion = Cotizacion::whereKey($idCotizacion)->lockForUpdate()->firstOrFail();

                $existente = $this->buscarPorCargo($cargo->id, $metodo);
                if ($existente) {
                    return $existente;
                }

                $esperado = $this->calcularCargo($montoBase, $metodo);
                $moneda = $cotizacion->moneda ?: 'PEN';
                if ($esperado['centimos'] !== $cargo->montoCentimos || strtoupper($cargo->moneda) !== strtoupper($moneda)) {
                    throw new InvalidArgumentException(
                        "El monto del cargo {$cargo->id} ({$cargo->montoCentimos} {$cargo->moneda}) no coincide con el esperado ({$esperado['centimos']} {$moneda})."
                    );
                }

                return $this->pagoService->registrar([
                    'id_cotizacion' => $idCotizacion,
                    'id_metodo_pago' => $metodo->id_metodo_pago,
                    'monto_base' => $montoBase,
                    'numero_operacion' => $cargo->id,
                    'observacion' => 'Pago online confirmado por la pasarela ' . config('services.pasarela.driver') . '.',
                ], null, $this->resolverUsuarioRegistro($cargo, $cotizacion, $idUsuario));
            });
        } catch (UniqueConstraintViolationException) {
            // Otro proceso registró el mismo cargo entre la consulta y el insert.
            return $this->buscarPorCargo($cargo->id, $metodo)
                ?? throw new InvalidArgumentException("No se pudo registrar el cargo {$cargo->id}.");
        }
    }

    // ------------------------------------------------------------------ devoluciones

    /**
     * Pagos online de una cotización que aún tienen monto por devolver.
     */
    public function pagosDevolvibles(int $idCotizacion)
    {
        $idMetodo = MetodoPago::where('nombre', config('services.pasarela.metodo_pago'))->value('id_metodo_pago');

        return Pago::where('id_cotizacion', $idCotizacion)
            ->where('id_metodo_pago', $idMetodo)
            ->where('estado', 'VALIDADO')
            ->whereColumn('monto_devuelto', '<', 'monto_total_pagado')
            ->get();
    }

    /**
     * Devuelve al cliente todo o parte de un pago online a través de la pasarela.
     * Si se devuelve el total, el pago pasa a DEVUELTO; si es parcial sigue VALIDADO
     * y solo la parte no devuelta cuenta para saldo y anticipo (RN05).
     */
    public function devolver(int $idPago, float $monto, string $motivo, int $idUsuario): DevolucionPago
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new InvalidArgumentException('El motivo de la devolución es obligatorio.');
        }

        $monto = round($monto, 2);
        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto a devolver debe ser mayor a 0.');
        }

        return DB::transaction(function () use ($idPago, $monto, $motivo, $idUsuario) {
            // El bloqueo impide dos devoluciones simultáneas del mismo pago.
            $pago = Pago::with('metodoPago')->whereKey($idPago)->lockForUpdate()->firstOrFail();

            if (($pago->metodoPago->nombre ?? null) !== config('services.pasarela.metodo_pago') || !$pago->numero_operacion) {
                throw new InvalidArgumentException('Solo se pueden devolver por el sistema los pagos hechos con la pasarela online.');
            }
            if ($pago->estado !== 'VALIDADO') {
                throw new InvalidArgumentException("El pago está en estado {$pago->estado} y no admite devoluciones.");
            }

            $devolvible = $pago->montoDevolvible();
            if ($monto > $devolvible + 0.001) {
                throw new InvalidArgumentException('El monto excede lo que queda por devolver: S/ ' . number_format($devolvible, 2));
            }

            $resultado = $this->gateway->refundCharge($pago->numero_operacion, (int) round($monto * 100));
            if (!$resultado->exitoso || !$resultado->id) {
                throw new InvalidArgumentException('La pasarela rechazó la devolución: ' . $resultado->mensaje);
            }

            try {
                $esTotal = abs($monto - $devolvible) < 0.001;
                // Parte del monto_base que deja de contar; en la devolución final se ajusta el redondeo.
                $montoBase = $esTotal
                    ? round((float) $pago->monto_base - (float) $pago->monto_base_devuelto, 2)
                    : round($monto * (float) $pago->monto_base / (float) $pago->monto_total_pagado, 2);

                $pago->monto_devuelto = round((float) $pago->monto_devuelto + $monto, 2);
                $pago->monto_base_devuelto = round((float) $pago->monto_base_devuelto + $montoBase, 2);
                if ($esTotal) {
                    $pago->estado = 'DEVUELTO';
                }
                $pago->observacion = mb_substr(
                    ($pago->observacion ? $pago->observacion . ' | ' : '') . "DEVOLUCIÓN S/ {$monto} ({$resultado->id}): {$motivo}",
                    0,
                    500
                );
                $pago->save();

                $devolucion = DevolucionPago::create([
                    'id_pago' => $pago->id_pago,
                    'monto' => $monto,
                    'monto_base' => $montoBase,
                    'numero_operacion' => $resultado->id,
                    'motivo' => mb_substr($motivo, 0, 500),
                    'id_usuario' => $idUsuario,
                    'fecha' => now(),
                ]);

                Auditoria::registrar(
                    $idUsuario,
                    'DEVOLVER_PAGO',
                    'pago',
                    $pago->id_pago,
                    "Devolución de S/ {$monto} del cargo {$pago->numero_operacion} (operación {$resultado->id})"
                    . ($esTotal ? ' - TOTAL' : ' - PARCIAL') . ". Motivo: {$motivo}"
                );

                return $devolucion;
            } catch (Throwable $e) {
                // La pasarela ya devolvió el dinero: dejar rastro para conciliar a mano.
                Log::critical('Devolución hecha en la pasarela pero no registrada en el sistema', [
                    'id_pago' => $pago->id_pago,
                    'devolucion' => $resultado->id,
                    'monto' => $monto,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    private function buscarPorCargo(string $chargeId, MetodoPago $metodo): ?Pago
    {
        return Pago::where('id_metodo_pago', $metodo->id_metodo_pago)
            ->where('numero_operacion', $chargeId)
            ->first();
    }

    private function validarCotizacionPagable(Cotizacion $cotizacion): void
    {
        $tieneReserva = Reserva::where('id_cotizacion', $cotizacion->id_cotizacion)
            ->whereIn('estado', ['CONFIRMADA', 'COMPLETADA'])
            ->exists();

        if ($cotizacion->estado !== 'ACEPTADA' && !$tieneReserva) {
            throw new InvalidArgumentException('Solo se pueden pagar cotizaciones aceptadas o con reserva confirmada.');
        }
    }

    /**
     * Usuario que figura como registrador: el que inició el pago (metadata enviada por el
     * servidor con la llave secreta) o, en su defecto, la cuenta del cliente dueño de la cotización.
     */
    private function resolverUsuarioRegistro(ChargeResult $cargo, Cotizacion $cotizacion, ?int $idUsuario): int
    {
        $candidatos = array_filter([$idUsuario, (int) ($cargo->metadata['id_usuario'] ?? 0)]);
        foreach ($candidatos as $id) {
            if (Usuario::whereKey($id)->exists()) {
                return (int) $id;
            }
        }

        $idPersona = $cotizacion->cliente?->id_persona;
        $usuarioCliente = $idPersona ? Usuario::where('id_persona', $idPersona)->value('id_usuario') : null;
        if ($usuarioCliente) {
            return (int) $usuarioCliente;
        }

        return (int) $cotizacion->id_usuario_counter;
    }
}
