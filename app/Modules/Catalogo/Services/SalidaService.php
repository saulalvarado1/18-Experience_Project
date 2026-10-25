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
            ->select('v.*');

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
