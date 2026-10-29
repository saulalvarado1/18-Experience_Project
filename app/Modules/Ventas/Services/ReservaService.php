<?php

namespace App\Modules\Ventas\Services;

use App\Modules\Catalogo\Models\Salida;
use App\Modules\Finanzas\Services\PagoService;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Seguridad\Models\Persona;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\DetalleReserva;
use App\Modules\Ventas\Models\Reserva;
use App\Modules\Ventas\Models\ReservaPasajero;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReservaService
{
    protected PagoService $pagoService;

    public function __construct(PagoService $pagoService)
    {
        $this->pagoService = $pagoService;
    }

    public function listar(array $filtros = [])
    {
        $query = Reserva::with([
            'cotizacion.cliente.persona',
            'usuarioCounter.rol',
            'detalles.producto',
            'detalles.salida',
            'detalles.pasajeros.persona',
        ])->orderBy('id_reserva', 'desc');

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'TODOS') {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['termino'])) {
            $t = '%' . trim($filtros['termino']) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('codigo', 'like', $t)
                  ->orWhereHas('cotizacion.cliente.persona', function ($qp) use ($t) {
                      $qp->where('numero_documento', 'like', $t)
                         ->orWhere('nombres', 'like', $t)
                         ->orWhere('apellidos', 'like', $t);
                  });
            });
        }

        return $query->paginate(15);
    }

    public function generarCodigo(): string
    {
        $prefijo = 'RES-' . date('Y');
        $ultimo = Reserva::where('codigo', 'like', "{$prefijo}-%")
            ->orderBy('id_reserva', 'desc')
            ->value('codigo');

        $numero = 1;
        if ($ultimo && preg_match('/RES-\d{4}-(\d+)/', $ultimo, $matches)) {
            $numero = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%04d', $prefijo, $numero);
    }

    public function obtenerPorId(int $idReserva): Reserva
    {
        return Reserva::with([
            'cotizacion.cliente.persona',
            'usuarioCounter',
            'detalles.producto',
            'detalles.salida',
            'detalles.pasajeros.persona',
        ])->findOrFail($idReserva);
    }

    public function confirmarDesdeCotizacion(int $idCotizacion, int $idUsuarioCounter, array $pasajerosPorDetalle = [], ?string $observacion = null): Reserva
    {
        return DB::transaction(function () use ($idCotizacion, $idUsuarioCounter, $pasajerosPorDetalle, $observacion) {
            // a) Bloquear la cotización
            $cot = Cotizacion::with(['cliente.persona', 'detalles.producto'])
                ->whereKey($idCotizacion)
                ->lockForUpdate()
                ->firstOrFail();

            if ($cot->estado !== 'ACEPTADA') {
                throw new InvalidArgumentException("La cotización {$cot->codigo} debe estar en estado ACEPTADA para poder reservarse.");
            }

            if (Reserva::where('id_cotizacion', $cot->id_cotizacion)->exists()) {
                throw new InvalidArgumentException("La cotización {$cot->codigo} ya tiene una reserva asociada.");
            }

            // b) Validar anticipo mínimo (RN05) únicamente con pagos VALIDADO
            $validado = $this->pagoService->montoValidado($cot->id_cotizacion);
            $porcentajeRequerido = (float) $cot->detalles->max(fn($d) => $d->producto->porcentaje_reserva ?? 50.0);
            $anticipoRequerido = round((float) $cot->total * ($porcentajeRequerido / 100), 2);

            if (($validado + 0.001) < $anticipoRequerido) {
                throw new InvalidArgumentException(
                    "Anticipo insuficiente. Se requiere el {$porcentajeRequerido}% (S/ {$anticipoRequerido}), pero los pagos validados suman S/ {$validado}."
                );
            }

            // c) Bloquear salidas en orden ascendente de id_salida para evitar deadlocks
            $detallesConSalida = $cot->detalles->filter(fn($d) => !empty($d->id_salida))->sortBy('id_salida');

            foreach ($detallesConSalida as $det) {
                $salida = Salida::whereKey($det->id_salida)->lockForUpdate()->firstOrFail();

                if (!in_array($salida->estado, ['PROGRAMADA', 'COMPLETA'])) {
                    throw new InvalidArgumentException("La salida {$salida->id_salida} ya no se encuentra activa (Estado: {$salida->estado}).");
                }

                // Calcular ocupados reales actuales
                $ocupados = (int) DB::table('reserva_pasajero as rp')
                    ->join('detalle_reserva as dr', 'dr.id_detalle_reserva', '=', 'rp.id_detalle_reserva')
                    ->join('reserva as r', 'r.id_reserva', '=', 'dr.id_reserva')
                    ->where('dr.id_salida', $salida->id_salida)
                    ->where('dr.estado', '!=', 'CANCELADO')
                    ->where('r.estado', '!=', 'CANCELADA')
                    ->count();

                if (($ocupados + $det->cantidad_pasajeros) > $salida->capacidad) {
                    $disponibles = max(0, $salida->capacidad - $ocupados);
                    throw new InvalidArgumentException(
                        "Cupos insuficientes para la salida {$salida->id_salida}. Disponibles: {$disponibles}, solicitados: {$det->cantidad_pasajeros}."
                    );
                }
            }

            // d) Crear la Reserva
            $codigoReserva = $this->generarCodigo();
            $fechaLimitePago = now()->addDays(3);

            $reserva = Reserva::create([
                'codigo' => $codigoReserva,
                'id_cotizacion' => $cot->id_cotizacion,
                'id_usuario_counter' => $idUsuarioCounter,
                'fecha_reserva' => now(),
                'estado' => 'CONFIRMADA',
                'moneda' => $cot->moneda,
                'total_contratado' => $cot->total,
                'fecha_limite_pago' => $fechaLimitePago,
                'observacion' => $observacion,
            ]);

            // e) Crear detalles de reserva y registrar pasajeros
            foreach ($cot->detalles as $det) {
                $dr = DetalleReserva::create([
                    'id_reserva' => $reserva->id_reserva,
                    'id_producto' => $det->id_producto,
                    'id_salida' => $det->id_salida,
                    'cantidad_pasajeros' => $det->cantidad_pasajeros,
                    'precio_unitario' => $det->precio_unitario,
                    'subtotal' => $det->subtotal,
                    'punto_recojo' => 'Agencia 18 Grados Sur, Tacna',
                    'estado' => 'ACTIVO',
                    'observacion' => $det->observacion,
                ]);

                // Registrar pasajeros para este detalle
                $listaPasajeros = $pasajerosPorDetalle[$det->id_detalle_cotizacion] ?? [];

                // Si no se proveyeron pasajeros individuales, registramos al titular de la cotización
                if (empty($listaPasajeros)) {
                    $listaPasajeros = [[
                        'id_persona' => $cot->cliente->id_persona,
                        'restriccion_alimentaria' => null,
                        'condicion_particular' => null,
                    ]];
                }

                foreach ($listaPasajeros as $pInfo) {
                    $idPersona = $pInfo['id_persona'] ?? $cot->cliente->id_persona;
                    ReservaPasajero::create([
                        'id_detalle_reserva' => $dr->id_detalle_reserva,
                        'id_persona' => $idPersona,
                        'restriccion_alimentaria' => $pInfo['restriccion_alimentaria'] ?? null,
                        'condicion_particular' => $pInfo['condicion_particular'] ?? null,
                        'observacion' => $pInfo['observacion'] ?? null,
                    ]);
                }

                // f) Si la salida se llenó al 100%, actualizar estado a COMPLETA
                if ($det->id_salida) {
                    $salida = Salida::whereKey($det->id_salida)->lockForUpdate()->first();
                    $nuevoTotalOcupados = (int) DB::table('reserva_pasajero as rp')
                        ->join('detalle_reserva as dr', 'dr.id_detalle_reserva', '=', 'rp.id_detalle_reserva')
                        ->join('reserva as r', 'r.id_reserva', '=', 'dr.id_reserva')
                        ->where('dr.id_salida', $salida->id_salida)
                        ->where('dr.estado', '!=', 'CANCELADO')
                        ->where('r.estado', '!=', 'CANCELADA')
                        ->count();

                    if ($nuevoTotalOcupados >= $salida->capacidad && $salida->estado === 'PROGRAMADA') {
                        $salida->estado = 'COMPLETA';
                        $salida->save();
                    }
                }
            }

            // Actualizar estado de la cotización a 'RESERVADA' si se desea o mantener trazabilidad
            // Según RN: cotización queda asociada
            Auditoria::registrar(
                $idUsuarioCounter,
                'GENERAR_RESERVA',
                'reserva',
                $reserva->id_reserva,
                "Reserva {$reserva->codigo} confirmada para cotización {$cot->codigo}. Total: S/ {$reserva->total_contratado}."
            );

            return $reserva;
        });
    }

    public function cancelar(int $idReserva, string $motivo, int $idUsuario): Reserva
    {
        if (trim($motivo) === '') {
            throw new InvalidArgumentException("El motivo de cancelación de reserva es obligatorio.");
        }

        return DB::transaction(function () use ($idReserva, $motivo, $idUsuario) {
            $reserva = Reserva::with('detalles')->whereKey($idReserva)->lockForUpdate()->firstOrFail();

            if ($reserva->estado === 'CANCELADA') {
                throw new InvalidArgumentException("La reserva ya se encuentra cancelada.");
            }
            if ($reserva->estado === 'COMPLETADA') {
                throw new InvalidArgumentException("No se puede cancelar una reserva completada.");
            }

            $reserva->estado = 'CANCELADA';
            $reserva->observacion = ($reserva->observacion ? $reserva->observacion . " | " : "") . "CANCELADA: " . trim($motivo);
            $reserva->save();

            foreach ($reserva->detalles as $det) {
                $det->estado = 'CANCELADO';
                $det->save();

                // Si la salida estaba COMPLETA, vuelve a PROGRAMADA
                if ($det->id_salida) {
                    $salida = Salida::whereKey($det->id_salida)->lockForUpdate()->first();
                    if ($salida && $salida->estado === 'COMPLETA') {
                        $salida->estado = 'PROGRAMADA';
                        $salida->save();
                    }
                }
            }

            Auditoria::registrar(
                $idUsuario,
                'CANCELAR_RESERVA',
                'reserva',
                $reserva->id_reserva,
                "Reserva {$reserva->codigo} cancelada. Motivo: {$motivo}"
            );

            return $reserva;
        });
    }

    public function completar(int $idReserva, int $idUsuario): Reserva
    {
        return DB::transaction(function () use ($idReserva, $idUsuario) {
            $reserva = Reserva::whereKey($idReserva)->lockForUpdate()->firstOrFail();

            if ($reserva->estado !== 'CONFIRMADA') {
                throw new InvalidArgumentException("Solo se pueden completar reservas en estado CONFIRMADA.");
            }

            // Validar saldo pendiente = 0
            $validado = $this->pagoService->montoValidado($reserva->id_cotizacion);
            if ($validado < (float) $reserva->total_contratado) {
                $pendiente = round((float) $reserva->total_contratado - $validado, 2);
                throw new InvalidArgumentException("No se puede completar la reserva: existe un saldo pendiente de S/ {$pendiente}.");
            }

            $reserva->estado = 'COMPLETADA';
            $reserva->save();

            Auditoria::registrar(
                $idUsuario,
                'COMPLETAR_RESERVA',
                'reserva',
                $reserva->id_reserva,
                "Reserva {$reserva->codigo} completada exitosamente."
            );

            return $reserva;
        });
    }
}
