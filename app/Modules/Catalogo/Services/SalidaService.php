<?php

namespace App\Modules\Catalogo\Services;

use App\Modules\Catalogo\Models\Salida;
use App\Modules\Seguridad\Models\Auditoria;
use Illuminate\Support\Facades\DB;

class SalidaService
{
    public function listarSalidas(?string $desde = null, ?string $hasta = null, ?int $idProducto = null, ?string $estado = 'TODOS')
    {
        $query = DB::table('vw_disponibilidad_salida as v')
            ->join('salida as s', 's.id_salida', '=', 'v.id_salida')
            ->select('v.*', 's.observacion');

        if (!empty($desde)) {
            $query->whereDate('v.fecha', '>=', $desde);
        }

        if (!empty($hasta)) {
            $query->whereDate('v.fecha', '<=', $hasta);
        }

        if (!empty($idProducto) && $idProducto > 0) {
            $query->where('v.id_producto', $idProducto);
        }

        if (!empty($estado) && $estado !== 'TODOS') {
            $query->where('v.estado', $estado);
        }

        return $query->orderBy('v.fecha', 'desc')
                     ->orderBy('v.hora', 'asc')
                     ->paginate(15);
    }

    /**
     * CU12 flujo 1a – Editar salida: fecha, hora y observación. No se permite si está CANCELADA o FINALIZADA
     * y se vuelve a validar la duplicidad (RN06). La capacidad se cambia con CU13.
     */
    public function editarSalida(int $idSalida, array $datos, int $idUsuario): Salida
    {
        $fecha = trim((string) ($datos['fecha'] ?? ''));
        $hora = trim((string) ($datos['hora'] ?? ''));
        $observacion = trim((string) ($datos['observacion'] ?? ''));

        if ($fecha === '' || $hora === '') {
            throw new \InvalidArgumentException('Ingrese la fecha y la hora de la salida.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
            throw new \InvalidArgumentException('Formato de fecha u hora inválido.');
        }
        if (mb_strlen($observacion) > 500) {
            throw new \InvalidArgumentException('La observación no debe superar 500 caracteres.');
        }

        return DB::transaction(function () use ($idSalida, $fecha, $hora, $observacion, $idUsuario) {
            $salida = Salida::whereKey($idSalida)->lockForUpdate()->firstOrFail();

            if (in_array($salida->estado, ['CANCELADA', 'FINALIZADA'], true)) {
                throw new \InvalidArgumentException("No se puede editar una salida {$salida->estado}.");
            }

            $horaNormalizada = strlen($hora) === 5 ? "{$hora}:00" : $hora;
            $duplicada = Salida::where('id_producto', $salida->id_producto)
                ->where('id_salida', '!=', $salida->id_salida)
                ->where('fecha', $fecha)
                ->where('hora', $horaNormalizada)
                ->where('estado', '<>', 'CANCELADA')
                ->exists();
            if ($duplicada) {
                throw new \InvalidArgumentException('Ya existe otra salida de este tour en la misma fecha y hora.');
            }

            $antes = $salida->fecha->format('Y-m-d') . ' ' . substr((string) $salida->hora, 0, 5);
            $salida->fecha = $fecha;
            $salida->hora = $horaNormalizada;
            $salida->observacion = $observacion !== '' ? $observacion : null;
            $salida->save();

            $ocupados = (int) $salida->disponibilidad->ocupados;
            Auditoria::registrar(
                $idUsuario,
                'EDITAR_SALIDA',
                'salida',
                $salida->id_salida,
                "Salida editada: {$antes} → {$fecha} " . substr($horaNormalizada, 0, 5)
                . ($ocupados > 0 ? " (afecta a {$ocupados} pasajero(s) reservados)" : '')
            );

            return $salida;
        });
    }

    public function programarSalida(array $datos, int $idUsuario): Salida
    {
        $idProducto = (int) ($datos['id_producto'] ?? 0);
        $fecha = trim($datos['fecha'] ?? '');
        $hora = trim($datos['hora'] ?? '');
        $capacidad = (int) ($datos['capacidad'] ?? 17);

        if ($idProducto <= 0 || empty($fecha) || empty($hora)) {
            throw new \InvalidArgumentException('Debe seleccionar el tour, la fecha y la hora de salida.');
        }

        if ($capacidad <= 0) {
            throw new \InvalidArgumentException('La capacidad de pasajeros debe ser mayor a cero.');
        }

        // RN06: No duplicar salida para el mismo producto, fecha y hora
        $duplicada = Salida::where('id_producto', $idProducto)
            ->where('fecha', $fecha)
            ->where('hora', $hora)
            ->where('estado', '<>', 'CANCELADA')
            ->exists();

        if ($duplicada) {
            throw new \InvalidArgumentException('Ya existe una salida programada para este tour en la misma fecha y hora.');
        }

        return DB::transaction(function () use ($idProducto, $fecha, $hora, $capacidad, $datos, $idUsuario) {
            $salida = Salida::create([
                'id_producto' => $idProducto,
                'fecha' => $fecha,
                'hora' => $hora,
                'capacidad' => $capacidad,
                'estado' => 'PROGRAMADA',
                'observacion' => $datos['observacion'] ?? null,
            ]);

            Auditoria::registrar(
                $idUsuario,
                'PROGRAMAR_SALIDA',
                'salida',
                $salida->id_salida,
                "Salida programada para {$fecha} {$hora} (Capacidad: {$capacidad})"
            );

            return $salida;
        });
    }

    public function cambiarCapacidad(int $idSalida, int $nuevaCapacidad, string $motivo, int $idUsuario): Salida
    {
        if ($nuevaCapacidad <= 0) {
            throw new \InvalidArgumentException('La capacidad debe ser un número positivo.');
        }

        if (empty(trim($motivo))) {
            throw new \InvalidArgumentException('Debe ingresar el motivo del cambio de capacidad.');
        }

        return DB::transaction(function () use ($idSalida, $nuevaCapacidad, $motivo, $idUsuario) {
            // Bloqueo pesimista de salida
            $salida = Salida::where('id_salida', $idSalida)->lockForUpdate()->firstOrFail();

            if (in_array($salida->estado, ['CANCELADA', 'FINALIZADA'])) {
                throw new \InvalidArgumentException('No se puede modificar la capacidad de una salida cancelada o finalizada.');
            }

            $capacidadAnterior = $salida->capacidad;
            $ocupados = $salida->disponibilidad->ocupados;

            if ($nuevaCapacidad < $ocupados) {
                throw new \InvalidArgumentException("La nueva capacidad ({$nuevaCapacidad}) no puede ser menor a los {$ocupados} cupos actualmente ocupados.");
            }

            $salida->capacidad = $nuevaCapacidad;
            $salida->estado = ($nuevaCapacidad == $ocupados) ? 'COMPLETA' : 'PROGRAMADA';
            $salida->save();

            Auditoria::registrar(
                $idUsuario,
                'CAMBIO_CAPACIDAD',
                'salida',
                $salida->id_salida,
                "Capacidad anterior: {$capacidadAnterior} | Nueva capacidad: {$nuevaCapacidad} | Motivo: {$motivo}"
            );

            return $salida;
        });
    }

    public function cancelarSalida(int $idSalida, string $motivo, int $idUsuario): Salida
    {
        if (empty(trim($motivo))) {
            throw new \InvalidArgumentException('Debe ingresar el motivo de cancelación de la salida.');
        }

        return DB::transaction(function () use ($idSalida, $motivo, $idUsuario) {
            $salida = Salida::where('id_salida', $idSalida)->lockForUpdate()->firstOrFail();

            if ($salida->estado === 'CANCELADA') {
                throw new \InvalidArgumentException('La salida ya se encuentra cancelada.');
            }

            // RN08: Solo cancelar si no tiene cupos ocupados
            $ocupados = $salida->disponibilidad->ocupados;
            if ($ocupados > 0) {
                throw new \InvalidArgumentException("No se puede cancelar una salida que tiene {$ocupados} pasajeros con reserva.");
            }

            $estadoAnterior = $salida->estado;
            $salida->estado = 'CANCELADA';
            $salida->observacion = trim(($salida->observacion ? $salida->observacion . ' | ' : '') . "CANCELADA: {$motivo}");
            $salida->save();

            Auditoria::registrar(
                $idUsuario,
                'CANCELAR_SALIDA',
                'salida',
                $salida->id_salida,
                "Estado anterior: {$estadoAnterior} | Nuevo estado: CANCELADA | Motivo: {$motivo}"
            );

            return $salida;
        });
    }
}
