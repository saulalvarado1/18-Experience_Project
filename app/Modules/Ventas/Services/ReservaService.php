<?php

namespace App\Modules\Ventas\Services;

use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Services\ClienteService;
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
    /** Punto de recojo sugerido en el formulario de reserva. */
    public const PUNTO_RECOJO_DEFECTO = 'Agencia 18 Grados Sur, Tacna';

    protected PagoService $pagoService;
    protected ClienteService $clienteService;

    public function __construct(PagoService $pagoService, ClienteService $clienteService)
    {
        $this->pagoService = $pagoService;
        $this->clienteService = $clienteService;
    }

    /**
     * Comprobaciones previas a abrir el formulario de reserva (CU20, paso 2): estado ACEPTADA, sin reserva previa,
     * anticipo validado y cupos. Se repiten dentro de la transacción al confirmar.
     *
     * @return array{validado: float, anticipo: float, porcentaje: float, saldo: float}
     */
    public function verificarReservable(Cotizacion $cot): array
    {
        $cot->loadMissing(['detalles.producto', 'detalles.salida']);

        if ($cot->estado !== 'ACEPTADA') {
            throw new InvalidArgumentException("La cotización {$cot->codigo} debe estar en estado ACEPTADA para poder reservarse.");
        }
        if (Reserva::where('id_cotizacion', $cot->id_cotizacion)->exists()) {
            throw new InvalidArgumentException("La cotización {$cot->codigo} ya tiene una reserva asociada.");
        }

        $validado = $this->pagoService->montoValidado($cot->id_cotizacion);
        $porcentaje = (float) $cot->detalles->max(fn ($d) => $d->producto->porcentaje_reserva ?? 50.0);
        $anticipo = round((float) $cot->total * ($porcentaje / 100), 2);
        if (($validado + 0.001) < $anticipo) {
            throw new InvalidArgumentException(
                "Anticipo insuficiente. Se requiere el {$porcentaje}% (S/ {$anticipo}), pero los pagos validados suman S/ {$validado}."
            );
        }

        foreach ($cot->detalles->filter(fn ($d) => $d->id_salida) as $det) {
            if (!$det->salida || !in_array($det->salida->estado, ['PROGRAMADA', 'COMPLETA'], true)) {
                throw new InvalidArgumentException("La salida del servicio {$det->producto->nombre} ya no está activa.");
            }
            $disp = $det->salida->disponibilidad;
            if ($disp->disponibles < $det->cantidad_pasajeros) {
                throw new InvalidArgumentException(
                    "Cupos insuficientes para {$det->producto->nombre}: disponibles {$disp->disponibles}, solicitados {$det->cantidad_pasajeros}."
                );
            }
        }

        return [
            'validado' => $validado,
            'anticipo' => $anticipo,
            'porcentaje' => $porcentaje,
            'saldo' => max(0.0, round((float) $cot->total - $validado, 2)),
        ];
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

    /**
     * CU20 – Generar reserva.
     *
     * @param array<int, array{punto_recojo?: string, pasajeros?: array<int, array>}> $servicios
     *        Indexado por id_detalle_cotizacion. Cada pasajero: tipo_documento, numero_documento, nombres, apellidos,
     *        y opcionales fecha_nacimiento, nacionalidad, genero, restriccion_alimentaria, condicion_particular.
     *        Debe haber exactamente `cantidad_pasajeros` pasajeros por servicio (RN12, RN13).
     */
    public function confirmarDesdeCotizacion(int $idCotizacion, int $idUsuarioCounter, array $servicios, ?string $observacion = null): Reserva
    {
        return DB::transaction(function () use ($idCotizacion, $idUsuarioCounter, $servicios, $observacion) {
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

            // d) Pasajeros completos e identificados por servicio (RN13). Las personas nuevas se registran aquí;
            //    si algo falla después, la transacción también las revierte.
            $pasajerosResueltos = $this->resolverPasajeros($cot->detalles, 'id_detalle_cotizacion', $servicios);

            // e) Crear la Reserva
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

            // f) Crear detalles de reserva y registrar pasajeros
            foreach ($cot->detalles as $det) {
                $datosServicio = $pasajerosResueltos[$det->id_detalle_cotizacion];
                $dr = DetalleReserva::create([
                    'id_reserva' => $reserva->id_reserva,
                    'id_producto' => $det->id_producto,
                    'id_salida' => $det->id_salida,
                    'cantidad_pasajeros' => $det->cantidad_pasajeros,
                    'precio_unitario' => $det->precio_unitario,
                    'subtotal' => $det->subtotal,
                    'punto_recojo' => $datosServicio['punto_recojo'],
                    'estado' => 'ACTIVO',
                    'observacion' => $det->observacion,
                ]);

                foreach ($datosServicio['pasajeros'] as $pInfo) {
                    ReservaPasajero::create([
                        'id_detalle_reserva' => $dr->id_detalle_reserva,
                        'id_persona' => $pInfo['id_persona'],
                        'restriccion_alimentaria' => $pInfo['restriccion_alimentaria'],
                        'condicion_particular' => $pInfo['condicion_particular'],
                        'observacion' => null,
                    ]);
                }

                // g) Si la salida se llenó al 100%, actualizar estado a COMPLETA
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
                "Reserva {$reserva->codigo} confirmada para cotización {$cot->codigo}. Total: S/ {$reserva->total_contratado}. "
                . 'Pasajeros: ' . collect($pasajerosResueltos)->sum(fn ($srv) => count($srv['pasajeros'])) . '.'
            );

            return $reserva;
        });
    }

    /**
     * CU21 – Modificar reserva: observación, puntos de recojo y lista de pasajeros de una reserva CONFIRMADA.
     * Cada servicio mantiene su cantidad contratada (sin cambiar cupos ni total) y sin personas repetidas.
     *
     * @param array<int, array{punto_recojo?: string, pasajeros?: array<int, array>}> $servicios Indexado por id_detalle_reserva.
     */
    public function modificar(int $idReserva, array $servicios, ?string $observacion, int $idUsuario): Reserva
    {
        return DB::transaction(function () use ($idReserva, $servicios, $observacion, $idUsuario) {
            $reserva = Reserva::whereKey($idReserva)->lockForUpdate()->firstOrFail();
            if ($reserva->estado !== 'CONFIRMADA') {
                throw new InvalidArgumentException('Solo se pueden modificar reservas CONFIRMADAS.');
            }

            $detalles = DetalleReserva::with(['producto', 'pasajeros'])
                ->where('id_reserva', $reserva->id_reserva)
                ->where('estado', 'ACTIVO')
                ->orderBy('id_detalle_reserva')
                ->get();
            if ($detalles->isEmpty()) {
                throw new InvalidArgumentException('La reserva no tiene servicios activos para modificar.');
            }

            $resueltos = $this->resolverPasajeros($detalles, 'id_detalle_reserva', $servicios);

            $cambios = [];
            foreach ($detalles as $det) {
                $nuevo = $resueltos[$det->id_detalle_reserva];
                $antes = $det->pasajeros->pluck('id_persona')->sort()->values()->all();
                $despues = collect($nuevo['pasajeros'])->pluck('id_persona')->sort()->values()->all();

                if ($det->punto_recojo !== $nuevo['punto_recojo']) {
                    $cambios[] = "recojo de {$det->producto->nombre}: {$det->punto_recojo} → {$nuevo['punto_recojo']}";
                }
                if ($antes !== $despues) {
                    $cambios[] = "pasajeros de {$det->producto->nombre} actualizados";
                }

                $det->punto_recojo = $nuevo['punto_recojo'];
                $det->save();

                // Se reemplaza la lista completa; la cantidad no cambia, así que los cupos ocupados tampoco.
                ReservaPasajero::where('id_detalle_reserva', $det->id_detalle_reserva)->delete();
                foreach ($nuevo['pasajeros'] as $pInfo) {
                    ReservaPasajero::create([
                        'id_detalle_reserva' => $det->id_detalle_reserva,
                        'id_persona' => $pInfo['id_persona'],
                        'restriccion_alimentaria' => $pInfo['restriccion_alimentaria'],
                        'condicion_particular' => $pInfo['condicion_particular'],
                        'observacion' => null,
                    ]);
                }
            }

            $observacion = trim((string) $observacion);
            if (mb_strlen($observacion) > 500) {
                throw new InvalidArgumentException('La observación no debe superar 500 caracteres.');
            }
            if ($observacion !== trim((string) $reserva->observacion)) {
                $cambios[] = 'observación actualizada';
            }
            $reserva->observacion = $observacion !== '' ? $observacion : null;
            $reserva->save();

            Auditoria::registrar(
                $idUsuario,
                'EDITAR_RESERVA',
                'reserva',
                $reserva->id_reserva,
                "Reserva {$reserva->codigo} modificada. " . ($cambios ? implode('; ', $cambios) . '.' : 'Sin cambios en los datos.')
            );

            return $reserva;
        });
    }

    /**
     * Valida y resuelve los pasajeros de cada servicio (CU20 pasos 3-4, CU21, RN13).
     *
     * @param iterable $detalles Detalles de cotización o de reserva (con producto y cantidad_pasajeros).
     * @param string   $clave    Columna que identifica al detalle en $servicios.
     * @return array<int, array{punto_recojo: string, pasajeros: array<int, array{id_persona: int, restriccion_alimentaria: ?string, condicion_particular: ?string}>}>
     */
    private function resolverPasajeros(iterable $detalles, string $clave, array $servicios): array
    {
        $resultado = [];

        foreach ($detalles as $det) {
            $nombreServicio = $det->producto->nombre ?? "Servicio {$det->$clave}";
            $datos = $servicios[$det->$clave] ?? [];

            $puntoRecojo = trim((string) ($datos['punto_recojo'] ?? ''));
            if ($puntoRecojo === '') {
                throw new InvalidArgumentException("Ingrese el punto de recojo de {$nombreServicio}.");
            }
            if (mb_strlen($puntoRecojo) > 255) {
                throw new InvalidArgumentException("El punto de recojo de {$nombreServicio} no debe superar 255 caracteres.");
            }

            // Se ignoran las filas del formulario que quedaron completamente vacías
            $filas = array_values(array_filter($datos['pasajeros'] ?? [], fn ($p) => is_array($p)
                && trim(($p['numero_documento'] ?? '') . ($p['nombres'] ?? '') . ($p['apellidos'] ?? '')) !== ''));

            $requeridos = (int) $det->cantidad_pasajeros;
            if (count($filas) !== $requeridos) {
                throw new InvalidArgumentException(
                    "{$nombreServicio}: se contrataron {$requeridos} cupo(s) y se registraron " . count($filas)
                    . ' pasajero(s). Registre los datos de todos los pasajeros.'
                );
            }

            $pasajeros = [];
            $vistos = [];
            foreach ($filas as $i => $p) {
                $n = $i + 1;
                try {
                    $persona = $this->clienteService->obtenerOCrearPersona($p);
                } catch (InvalidArgumentException $e) {
                    throw new InvalidArgumentException("{$nombreServicio}, pasajero {$n}: " . $e->getMessage());
                }

                if (isset($vistos[$persona->id_persona])) {
                    throw new InvalidArgumentException(
                        "{$nombreServicio}: {$persona->nombres} {$persona->apellidos} está repetido en el mismo servicio."
                    );
                }
                $vistos[$persona->id_persona] = true;

                $restriccion = trim((string) ($p['restriccion_alimentaria'] ?? ''));
                $condicion = trim((string) ($p['condicion_particular'] ?? ''));
                if (mb_strlen($restriccion) > 500 || mb_strlen($condicion) > 500) {
                    throw new InvalidArgumentException("{$nombreServicio}, pasajero {$n}: las observaciones no deben superar 500 caracteres.");
                }

                $pasajeros[] = [
                    'id_persona' => $persona->id_persona,
                    'restriccion_alimentaria' => $restriccion !== '' ? $restriccion : null,
                    'condicion_particular' => $condicion !== '' ? $condicion : null,
                ];
            }

            $resultado[$det->$clave] = ['punto_recojo' => $puntoRecojo, 'pasajeros' => $pasajeros];
        }

        return $resultado;
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

            // RN15 / CU23 2a: sin saldo pendiente
            $validado = $this->pagoService->montoValidado($reserva->id_cotizacion);
            if ($validado + 0.001 < (float) $reserva->total_contratado) {
                $pendiente = round((float) $reserva->total_contratado - $validado, 2);
                throw new InvalidArgumentException("La reserva tiene saldo pendiente y no puede completarse (S/ {$pendiente}).");
            }

            // RN15 / CU23 2b: ninguna salida cancelada y todas ya realizadas (fecha y hora pasadas)
            $detalles = DetalleReserva::with(['salida', 'producto'])
                ->where('id_reserva', $reserva->id_reserva)
                ->where('estado', '!=', 'CANCELADO')
                ->get();
            foreach ($detalles as $det) {
                $salida = $det->salida;
                if (!$salida) {
                    continue; // servicio sin salida programada (fecha por coordinar)
                }
                if ($salida->estado === 'CANCELADA') {
                    throw new InvalidArgumentException("La salida de {$det->producto->nombre} está CANCELADA; la reserva no puede completarse.");
                }
                $momento = $salida->fecha->copy()->setTimeFromTimeString($salida->hora ?: '00:00:00');
                if ($momento->isFuture()) {
                    throw new InvalidArgumentException(
                        "El servicio {$det->producto->nombre} aún no se realiza (salida {$momento->format('d/m/Y H:i')})."
                    );
                }
            }

            $reserva->estado = 'COMPLETADA';
            $reserva->save();

            DetalleReserva::where('id_reserva', $reserva->id_reserva)
                ->where('estado', 'ACTIVO')
                ->update(['estado' => 'COMPLETADO']);

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
