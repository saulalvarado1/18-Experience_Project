<?php

namespace App\Modules\Catalogo\Services;

use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\ProductoTuristico;
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
            $t = '%' . trim($termino) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('codigo', 'like', $t)
                  ->orWhere('nombre', 'like', $t)
                  ->orWhere('descripcion', 'like', $t);
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
}
