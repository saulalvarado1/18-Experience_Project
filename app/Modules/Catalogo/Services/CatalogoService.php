<?php

namespace App\Modules\Catalogo\Services;

use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Servicio;
use App\Modules\Seguridad\Models\Auditoria;
use Illuminate\Support\Facades\DB;

class CatalogoService
{
    // --- DESTINOS (CU08) ---

    public function listarDestinos(?string $termino = null, ?string $estado = 'TODOS')
    {
        $query = Destino::query();

        if (!empty($termino)) {
            $t = '%' . trim($termino) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('nombre', 'like', $t)
                  ->orWhere('ubicacion', 'like', $t)
                  ->orWhere('descripcion', 'like', $t);
            });
        }

        if ($estado === 'ACTIVOS') {
            $query->where('activo', 1);
        } elseif ($estado === 'INACTIVOS') {
            $query->where('activo', 0);
        }

        return $query->orderBy('nombre')->paginate(15);
    }

    public function crearDestino(array $datos, int $idUsuario): Destino
    {
        $nombre = trim($datos['nombre'] ?? '');
        if (empty($nombre)) {
            throw new \InvalidArgumentException('El nombre del destino es obligatorio.');
        }

        if (Destino::where('nombre', $nombre)->exists()) {
            throw new \InvalidArgumentException('Ya existe un destino registrado con ese nombre.');
        }

        return DB::transaction(function () use ($datos, $nombre, $idUsuario) {
            $destino = Destino::create([
                'nombre' => $nombre,
                'descripcion' => $datos['descripcion'] ?? null,
                'ubicacion' => $datos['ubicacion'] ?? null,
                'altitud_msnm' => !empty($datos['altitud_msnm']) ? $datos['altitud_msnm'] : null,
                'como_llegar' => $datos['como_llegar'] ?? null,
                'temporada_recomendada' => $datos['temporada_recomendada'] ?? null,
                'recomendaciones' => $datos['recomendaciones'] ?? null,
                'activo' => true,
            ]);

            Auditoria::registrar($idUsuario, 'CREAR_DESTINO', 'destino', $destino->id_destino, "Destino creado: {$nombre}");

            return $destino;
        });
    }

    public function actualizarDestino(int $id, array $datos, int $idUsuario): Destino
    {
        $destino = Destino::findOrFail($id);
        $nombre = trim($datos['nombre'] ?? '');

        if (empty($nombre)) {
            throw new \InvalidArgumentException('El nombre del destino es obligatorio.');
        }

        if (Destino::where('nombre', $nombre)->where('id_destino', '<>', $id)->exists()) {
            throw new \InvalidArgumentException('Ya existe otro destino con ese nombre.');
        }

        return DB::transaction(function () use ($destino, $datos, $nombre, $idUsuario) {
            $destino->update([
                'nombre' => $nombre,
                'descripcion' => $datos['descripcion'] ?? null,
                'ubicacion' => $datos['ubicacion'] ?? null,
                'altitud_msnm' => !empty($datos['altitud_msnm']) ? $datos['altitud_msnm'] : null,
                'como_llegar' => $datos['como_llegar'] ?? null,
                'temporada_recomendada' => $datos['temporada_recomendada'] ?? null,
                'recomendaciones' => $datos['recomendaciones'] ?? null,
            ]);

            Auditoria::registrar($idUsuario, 'EDITAR_DESTINO', 'destino', $destino->id_destino, "Destino actualizado: {$nombre}");

            return $destino;
        });
    }

    public function cambiarEstadoDestino(int $id, bool $activo, int $idUsuario): Destino
    {
        $destino = Destino::findOrFail($id);
        $destino->activo = $activo;
        $destino->save();

        Auditoria::registrar(
            $idUsuario,
            $activo ? 'ACTIVAR_DESTINO' : 'DESACTIVAR_DESTINO',
            'destino',
            $destino->id_destino,
            $activo ? 'Destino activado.' : 'Destino desactivado.'
        );

        return $destino;
    }

    // --- TOURS Y PAQUETES (CU09 - CU11) ---

    public function listarProductos(?string $termino = null, ?string $tipo = 'TODOS', ?string $estado = 'TODOS')
    {
        $query = ProductoTuristico::with(['destinos', 'servicios']);

        if (!empty($termino)) {
            // CU09 1c: código, nombre, descripción o itinerario, sin distinguir mayúsculas
            $t = '%' . mb_strtolower(trim($termino)) . '%';
            $query->where(function ($q) use ($t) {
                $q->whereRaw('LOWER(codigo) LIKE ?', [$t])
                  ->orWhereRaw('LOWER(nombre) LIKE ?', [$t])
                  ->orWhereRaw('LOWER(COALESCE(descripcion, \'\')) LIKE ?', [$t])
                  ->orWhereRaw('LOWER(COALESCE(itinerario, \'\')) LIKE ?', [$t]);
            });
        }

        if (!empty($tipo) && $tipo !== 'TODOS') {
            $query->where('tipo_producto', $tipo);
        }

        if ($estado === 'ACTIVOS') {
            $query->where('activo', 1);
        } elseif ($estado === 'INACTIVOS') {
            $query->where('activo', 0);
        }

        return $query->orderBy('nombre')->paginate(15);
    }

    public function crearProducto(array $datos, int $idUsuario): ProductoTuristico
    {
        $codigo = strtoupper(trim($datos['codigo'] ?? ''));
        $nombre = trim($datos['nombre'] ?? '');
        $tipo = $datos['tipo_producto'] ?? 'TOUR_LOCAL';
        $precio = (float) ($datos['precio_base'] ?? 0);
        $porcentajeReserva = (float) ($datos['porcentaje_reserva'] ?? 50);

        if (empty($codigo) || empty($nombre)) {
            throw new \InvalidArgumentException('El código y el nombre del tour son obligatorios.');
        }

        if (ProductoTuristico::where('codigo', $codigo)->exists()) {
            throw new \InvalidArgumentException('El código de tour ya se encuentra registrado.');
        }

        if ($precio < 0) {
            throw new \InvalidArgumentException('El precio base debe ser mayor o igual a cero.');
        }

        // Regla RN05: Tours locales requieren mínimo 50% de anticipo
        if ($tipo === 'TOUR_LOCAL' && $porcentajeReserva < 50) {
            throw new \InvalidArgumentException('Los tours locales exigen un porcentaje de anticipo mínimo del 50%.');
        }

        return DB::transaction(function () use ($datos, $codigo, $nombre, $tipo, $precio, $porcentajeReserva, $idUsuario) {
            $producto = ProductoTuristico::create([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo_producto' => $tipo,
                'descripcion' => $datos['descripcion'] ?? null,
                'itinerario' => $datos['itinerario'] ?? null,
                'duracion_dias' => !empty($datos['duracion_dias']) ? (int) $datos['duracion_dias'] : 1,
                'precio_base' => $precio,
                'moneda' => $datos['moneda'] ?? 'PEN',
                'porcentaje_reserva' => $porcentajeReserva,
                'minimo_pasajeros' => !empty($datos['minimo_pasajeros']) ? (int) $datos['minimo_pasajeros'] : 1,
                'activo' => true,
            ]);

            Auditoria::registrar($idUsuario, 'CREAR_PRODUCTO', 'producto_turistico', $producto->id_producto, "Producto creado: [{$codigo}] {$nombre}");

            return $producto;
        });
    }

    /**
     * CU09 flujo 1a – Editar producto con las mismas validaciones del registro; el precio no se edita aquí (CU11).
     */
    public function actualizarProducto(int $id, array $datos, int $idUsuario): ProductoTuristico
    {
        $codigo = strtoupper(trim($datos['codigo'] ?? ''));
        $nombre = trim($datos['nombre'] ?? '');
        $tipo = $datos['tipo_producto'] ?? '';
        $moneda = $datos['moneda'] ?? 'PEN';
        $porcentaje = (float) ($datos['porcentaje_reserva'] ?? 0);
        $minimo = (int) ($datos['minimo_pasajeros'] ?? 0);
        $duracion = (int) ($datos['duracion_dias'] ?? 0);

        if ($codigo === '' || $nombre === '') {
            throw new \InvalidArgumentException('El código y el nombre del tour son obligatorios.');
        }
        if (!in_array($tipo, ['TOUR_LOCAL', 'PAQUETE_NACIONAL', 'PAQUETE_INTERNACIONAL'], true)) {
            throw new \InvalidArgumentException('Seleccione un tipo de producto válido.');
        }
        if (!in_array($moneda, ['PEN', 'USD'], true)) {
            throw new \InvalidArgumentException('La moneda debe ser PEN o USD.');
        }
        if ($porcentaje <= 0 || $porcentaje > 100) {
            throw new \InvalidArgumentException('El porcentaje de reserva debe estar entre 1 y 100.');
        }
        if ($tipo === 'TOUR_LOCAL' && $porcentaje < 50) {
            throw new \InvalidArgumentException('Los tours locales exigen un porcentaje de anticipo mínimo del 50%.');
        }
        if ($minimo < 1 || $duracion < 1) {
            throw new \InvalidArgumentException('El mínimo de pasajeros y la duración deben ser al menos 1.');
        }
        if (ProductoTuristico::where('codigo', $codigo)->where('id_producto', '!=', $id)->exists()) {
            throw new \InvalidArgumentException('El código de tour ya se encuentra registrado.');
        }

        return DB::transaction(function () use ($id, $datos, $codigo, $nombre, $tipo, $moneda, $porcentaje, $minimo, $duracion, $idUsuario) {
            $producto = ProductoTuristico::whereKey($id)->lockForUpdate()->firstOrFail();
            $producto->update([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo_producto' => $tipo,
                'moneda' => $moneda,
                'porcentaje_reserva' => $porcentaje,
                'minimo_pasajeros' => $minimo,
                'duracion_dias' => $duracion,
                'descripcion' => trim((string) ($datos['descripcion'] ?? '')) ?: null,
                'itinerario' => trim((string) ($datos['itinerario'] ?? '')) ?: null,
            ]);

            Auditoria::registrar($idUsuario, 'EDITAR_PRODUCTO', 'producto_turistico', $producto->id_producto, "Producto editado: [{$codigo}] {$nombre}");

            return $producto;
        });
    }

    /**
     * CU09 flujo 1b – Activar o desactivar un producto (los inactivos no se ofrecen ni se cotizan).
     */
    public function cambiarEstadoProducto(int $id, int $idUsuario): ProductoTuristico
    {
        return DB::transaction(function () use ($id, $idUsuario) {
            $producto = ProductoTuristico::whereKey($id)->lockForUpdate()->firstOrFail();
            $producto->activo = !$producto->activo;
            $producto->save();

            Auditoria::registrar($idUsuario, $producto->activo ? 'ACTIVAR_PRODUCTO' : 'DESACTIVAR_PRODUCTO', 'producto_turistico',
                $producto->id_producto, "Producto [{$producto->codigo}] " . ($producto->activo ? 'activado' : 'desactivado') . '.');

            return $producto;
        });
    }

    public function cambiarPrecio(int $id, float $nuevoPrecio, string $motivo, int $idUsuario): ProductoTuristico
    {
        if ($nuevoPrecio < 0) {
            throw new \InvalidArgumentException('El nuevo precio debe ser mayor o igual a cero.');
        }

        if (empty(trim($motivo))) {
            throw new \InvalidArgumentException('Debe ingresar el motivo de la actualización de precio.');
        }

        $producto = ProductoTuristico::findOrFail($id);
        $precioAnterior = (float) $producto->precio_base;

        if (abs($precioAnterior - $nuevoPrecio) < 0.001) {
            throw new \InvalidArgumentException('El nuevo precio no puede ser igual al precio actual.');
        }

        return DB::transaction(function () use ($producto, $precioAnterior, $nuevoPrecio, $motivo, $idUsuario) {
            $producto->precio_base = $nuevoPrecio;
            $producto->save();

            Auditoria::registrar(
                $idUsuario,
                'CAMBIO_PRECIO',
                'producto_turistico',
                $producto->id_producto,
                "Precio anterior: {$precioAnterior} | Nuevo precio: {$nuevoPrecio} | Motivo: {$motivo}"
            );

            return $producto;
        });
    }

    // --- COMPOSICIÓN DEL PRODUCTO (CU10) ---

    /**
     * Asigna un destino al producto con su orden de visita (CU10 pasos 1-3, flujo 3a).
     */
    public function asignarDestino(int $idProducto, int $idDestino, int $orden, ?string $observacion, int $idUsuario): void
    {
        if ($orden <= 0) {
            throw new \InvalidArgumentException('El orden de visita debe ser mayor que cero.');
        }

        DB::transaction(function () use ($idProducto, $idDestino, $orden, $observacion, $idUsuario) {
            $producto = ProductoTuristico::whereKey($idProducto)->lockForUpdate()->firstOrFail();
            $destino = Destino::where('activo', true)->find($idDestino);
            if (!$destino) {
                throw new \InvalidArgumentException('Seleccione un destino activo.');
            }
            if ($producto->destinos()->where('destino.id_destino', $idDestino)->exists()) {
                throw new \InvalidArgumentException("El destino {$destino->nombre} ya está asignado a este producto.");
            }

            $producto->destinos()->attach($idDestino, [
                'orden_visita' => $orden,
                'observacion' => $this->textoOpcional($observacion),
            ]);

            Auditoria::registrar($idUsuario, 'ASIGNAR_DESTINO', 'producto_turistico', $producto->id_producto,
                "Destino {$destino->nombre} asignado a [{$producto->codigo}] en el orden {$orden}.");
        });
    }

    public function quitarDestino(int $idProducto, int $idDestino, int $idUsuario): void
    {
        DB::transaction(function () use ($idProducto, $idDestino, $idUsuario) {
            $producto = ProductoTuristico::whereKey($idProducto)->lockForUpdate()->firstOrFail();
            $destino = $producto->destinos()->where('destino.id_destino', $idDestino)->first();
            if (!$destino) {
                throw new \InvalidArgumentException('El destino no está asignado a este producto.');
            }

            $producto->destinos()->detach($idDestino);

            Auditoria::registrar($idUsuario, 'QUITAR_DESTINO', 'producto_turistico', $producto->id_producto,
                "Destino {$destino->nombre} quitado de [{$producto->codigo}].");
        });
    }

    /**
     * Asigna un servicio incluido o adicional (CU10 paso 4, flujos 3a y 4a: incluido → precio adicional 0).
     */
    public function asignarServicio(int $idProducto, int $idServicio, bool $incluido, float $precioAdicional, ?string $observacion, int $idUsuario): void
    {
        $precioAdicional = $incluido ? 0.0 : round($precioAdicional, 2);
        if ($precioAdicional < 0) {
            throw new \InvalidArgumentException('El precio adicional no puede ser negativo.');
        }

        DB::transaction(function () use ($idProducto, $idServicio, $incluido, $precioAdicional, $observacion, $idUsuario) {
            $producto = ProductoTuristico::whereKey($idProducto)->lockForUpdate()->firstOrFail();
            $servicio = Servicio::where('activo', true)->find($idServicio);
            if (!$servicio) {
                throw new \InvalidArgumentException('Seleccione un servicio activo.');
            }
            if ($producto->servicios()->where('servicio.id_servicio', $idServicio)->exists()) {
                throw new \InvalidArgumentException("El servicio {$servicio->nombre} ya está asignado a este producto.");
            }

            $producto->servicios()->attach($idServicio, [
                'incluido' => $incluido,
                'precio_adicional' => $precioAdicional,
                'observacion' => $this->textoOpcional($observacion),
            ]);

            $detalle = $incluido ? 'incluido' : 'adicional por S/ ' . number_format($precioAdicional, 2);
            Auditoria::registrar($idUsuario, 'ASIGNAR_SERVICIO', 'producto_turistico', $producto->id_producto,
                "Servicio {$servicio->nombre} ({$detalle}) asignado a [{$producto->codigo}].");
        });
    }

    public function quitarServicio(int $idProducto, int $idServicio, int $idUsuario): void
    {
        DB::transaction(function () use ($idProducto, $idServicio, $idUsuario) {
            $producto = ProductoTuristico::whereKey($idProducto)->lockForUpdate()->firstOrFail();
            $servicio = $producto->servicios()->where('servicio.id_servicio', $idServicio)->first();
            if (!$servicio) {
                throw new \InvalidArgumentException('El servicio no está asignado a este producto.');
            }

            $producto->servicios()->detach($idServicio);

            Auditoria::registrar($idUsuario, 'QUITAR_SERVICIO', 'producto_turistico', $producto->id_producto,
                "Servicio {$servicio->nombre} quitado de [{$producto->codigo}].");
        });
    }

    private function textoOpcional(?string $texto): ?string
    {
        $texto = trim((string) $texto);
        if (mb_strlen($texto) > 500) {
            throw new \InvalidArgumentException('La observación no debe superar 500 caracteres.');
        }

        return $texto !== '' ? $texto : null;
    }
}
