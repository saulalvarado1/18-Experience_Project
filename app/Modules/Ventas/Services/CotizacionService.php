<?php

namespace App\Modules\Ventas\Services;

use App\Modules\Catalogo\Models\ProductoTuristico;
use App\Modules\Catalogo\Models\Salida;
use App\Modules\Clientes\Models\Cliente;
use App\Modules\Seguridad\Models\Auditoria;
use App\Modules\Ventas\Models\Cotizacion;
use App\Modules\Ventas\Models\DetalleCotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CotizacionService
{
    public function listar(array $filtros = [])
    {
        $query = Cotizacion::with(['cliente.persona', 'usuarioCounter.rol', 'detalles.producto', 'detalles.salida', 'pagos'])
            ->orderBy('id_cotizacion', 'desc');

        if (!empty($filtros['estado']) && $filtros['estado'] !== 'TODOS') {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['termino'])) {
            $t = '%' . trim($filtros['termino']) . '%';
            $query->where(function ($q) use ($t) {
                $q->where('codigo', 'like', $t)
                  ->orWhereHas('cliente.persona', function ($qp) use ($t) {
                      $qp->where('numero_documento', 'like', $t)
                         ->orWhere('nombres', 'like', $t)
                         ->orWhere('apellidos', 'like', $t);
                  });
            });
        }

        return $query->paginate(15);
    }

    public function obtenerPorId(int $idCotizacion): Cotizacion
    {
        return Cotizacion::with([
            'cliente.persona',
            'usuarioCounter',
            'detalles.producto',
            'detalles.salida',
            'pagos.metodoPago',
            'reserva'
        ])->findOrFail($idCotizacion);
    }

    public function generarCodigo(): string
    {
        $prefijo = 'COT-' . date('Y');
        $ultimo = Cotizacion::where('codigo', 'like', "{$prefijo}-%")
            ->orderBy('id_cotizacion', 'desc')
            ->value('codigo');

        $numero = 1;
        if ($ultimo && preg_match('/COT-\d{4}-(\d+)/', $ultimo, $matches)) {
            $numero = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%04d', $prefijo, $numero);
    }

    public function crear(array $datos, array $items, int $idUsuarioCounter): Cotizacion
    {
        return DB::transaction(function () use ($datos, $items, $idUsuarioCounter) {
            $cliente = Cliente::with('persona')->findOrFail((int) $datos['id_cliente']);
            if (!$cliente->activo) {
                throw new InvalidArgumentException("El cliente seleccionado se encuentra inactivo.");
            }

            if (empty($items)) {
                throw new InvalidArgumentException("Debe agregar al menos un tour o paquete a la cotización.");
            }

            $codigo = $this->generarCodigo();
            $vigencia = !empty($datos['vigencia_hasta'])
                ? $datos['vigencia_hasta']
                : now()->addDays(7)->format('Y-m-d H:i:s');

            $cotizacion = Cotizacion::create([
                'codigo' => $codigo,
                'id_cliente' => $cliente->id_cliente,
                'id_usuario_counter' => $idUsuarioCounter,
                'fecha_cotizacion' => now(),
                'estado' => 'VIGENTE',
                'moneda' => $datos['moneda'] ?? 'PEN',
                'total' => 0.00,
                'vigencia_hasta' => $vigencia,
                'observacion' => $datos['observacion'] ?? null,
            ]);

            $cotizacion->total = $this->registrarDetalles($cotizacion, $items);
            $cotizacion->save();

            Auditoria::registrar(
                $idUsuarioCounter,
                'CREAR_COTIZACION',
                'cotizacion',
                $cotizacion->id_cotizacion,
                "Cotización {$cotizacion->codigo} generada para cliente {$cliente->persona->nombreCompleto()}. Total: S/ {$cotizacion->total}"
            );

            return $cotizacion;
        });
    }

    /**
     * CU15 – Editar cotización: solo VIGENTE. Reemplaza los servicios (mismas validaciones del registro),
     * recalcula el total y registra EDITAR_COTIZACION. El cliente y el código no cambian.
     */
    public function editar(int $idCotizacion, array $datos, array $items, int $idUsuario): Cotizacion
    {
        return DB::transaction(function () use ($idCotizacion, $datos, $items, $idUsuario) {
            $cotizacion = Cotizacion::whereKey($idCotizacion)->lockForUpdate()->firstOrFail();

            if ($cotizacion->estado !== 'VIGENTE') {
                throw new InvalidArgumentException('Solo se pueden editar cotizaciones VIGENTES.');
            }
            if (empty($items)) {
                throw new InvalidArgumentException('Debe agregar al menos un tour o paquete a la cotización.');
            }

            $totalAnterior = (float) $cotizacion->total;
            DetalleCotizacion::where('id_cotizacion', $cotizacion->id_cotizacion)->delete();
            $cotizacion->total = $this->registrarDetalles($cotizacion, $items);

            if (!empty($datos['vigencia_hasta'])) {
                $cotizacion->vigencia_hasta = $datos['vigencia_hasta'];
            }
            $cotizacion->observacion = $datos['observacion'] ?? null;
            $cotizacion->save();

            Auditoria::registrar(
                $idUsuario,
                'EDITAR_COTIZACION',
                'cotizacion',
                $cotizacion->id_cotizacion,
                "Cotización {$cotizacion->codigo} editada. Total: S/ {$totalAnterior} → S/ {$cotizacion->total}. Servicios: " . count($items) . '.'
            );

            return $cotizacion;
        });
    }

    /**
     * Valida y registra los servicios de una cotización (CU15 pasos 3-5). Devuelve el total.
     * Reglas: producto activo, cantidad ≥ 1, salida PROGRAMADA del mismo producto, misma moneda
     * en todos los servicios y sin repetir producto + salida.
     */
    private function registrarDetalles(Cotizacion $cotizacion, array $items): float
    {
        $total = 0.00;
        $moneda = null;
        $vistos = [];

        foreach ($items as $item) {
            $producto = ProductoTuristico::findOrFail((int) $item['id_producto']);
            if (!$producto->activo) {
                throw new InvalidArgumentException("El producto '{$producto->nombre}' está inactivo.");
            }

            $cantidad = (int) ($item['cantidad_pasajeros'] ?? 1);
            if ($cantidad < 1) {
                throw new InvalidArgumentException("La cantidad de pasajeros debe ser al menos 1.");
            }
            // CU15 paso 5 / RN09: respetar el mínimo de pasajeros del producto
            $minimo = max(1, (int) $producto->minimo_pasajeros);
            if ($cantidad < $minimo) {
                throw new InvalidArgumentException("El tour '{$producto->nombre}' requiere un mínimo de {$minimo} pasajeros.");
            }

            $moneda ??= $producto->moneda;
            if ($producto->moneda !== $moneda) {
                throw new InvalidArgumentException('Todos los servicios de la cotización deben estar en la misma moneda.');
            }

            $idSalida = !empty($item['id_salida']) ? (int) $item['id_salida'] : null;
            $clave = $producto->id_producto . '-' . ($idSalida ?? 'sin-salida');
            if (isset($vistos[$clave])) {
                throw new InvalidArgumentException("El servicio '{$producto->nombre}' está repetido con la misma salida.");
            }
            $vistos[$clave] = true;

            if ($idSalida) {
                $salida = Salida::findOrFail($idSalida);
                if ($salida->id_producto != $producto->id_producto) {
                    throw new InvalidArgumentException("La salida seleccionada no corresponde al producto.");
                }
                if ($salida->estado !== 'PROGRAMADA') {
                    throw new InvalidArgumentException("La salida no está disponible para cotizar (Estado: {$salida->estado}).");
                }
                // CU15 paso 3-4: solo salidas futuras y con cupos (la cotización no reserva cupos)
                if ($salida->fecha->lt(now()->startOfDay())) {
                    throw new InvalidArgumentException("La salida del {$salida->fecha->format('d/m/Y')} ya pasó; elija otra fecha.");
                }
                $disponibles = (int) $salida->disponibilidad->disponibles;
                if ($disponibles < $cantidad) {
                    throw new InvalidArgumentException("La salida del {$salida->fecha->format('d/m/Y')} solo tiene {$disponibles} cupo(s) disponible(s).");
                }
            }

            $precioUnitario = isset($item['precio_unitario']) && $item['precio_unitario'] > 0
                ? round((float) $item['precio_unitario'], 2)
                : (float) $producto->precio_base;
            $adicional = isset($item['monto_adicional']) ? round((float) $item['monto_adicional'], 2) : 0.00;
            $subtotal = round(($cantidad * $precioUnitario) + $adicional, 2);

            DetalleCotizacion::create([
                'id_cotizacion' => $cotizacion->id_cotizacion,
                'id_producto' => $producto->id_producto,
                'id_salida' => $idSalida,
                'cantidad_pasajeros' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'monto_adicional' => $adicional,
                'subtotal' => $subtotal,
                'observacion' => $item['observacion'] ?? null,
            ]);

            $total += $subtotal;
        }

        if ($moneda && $cotizacion->moneda !== $moneda) {
            $cotizacion->moneda = $moneda;
        }

        return round($total, 2);
    }

    public function cambiarEstado(int $idCotizacion, string $nuevoEstado, int $idUsuario, ?string $motivo = null): Cotizacion
    {
        $estadosPermitidos = ['VIGENTE', 'ACEPTADA', 'NO_ACEPTADA', 'CANCELADA', 'SIN_DISPONIBILIDAD'];
        if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
            throw new InvalidArgumentException("Estado de cotización inválido: {$nuevoEstado}");
        }

        return DB::transaction(function () use ($idCotizacion, $nuevoEstado, $idUsuario, $motivo) {
            $cotizacion = Cotizacion::whereKey($idCotizacion)->lockForUpdate()->firstOrFail();

            if ($nuevoEstado === 'ACEPTADA') {
                if ($cotizacion->estado !== 'VIGENTE') {
                    throw new InvalidArgumentException("Solo se pueden aceptar cotizaciones en estado VIGENTE.");
                }
                if ($cotizacion->vigencia_hasta && $cotizacion->vigencia_hasta < now()) {
                    throw new InvalidArgumentException("La cotización ha expirado su fecha de vigencia.");
                }

                // Verificar cupos en salidas asociadas
                foreach ($cotizacion->detalles as $det) {
                    if ($det->id_salida) {
                        $disponibilidad = DB::table('vw_disponibilidad_salida')
                            ->where('id_salida', $det->id_salida)
                            ->first();

                        if ($disponibilidad && $disponibilidad->cupos_disponibles < $det->cantidad_pasajeros) {
                            $cotizacion->estado = 'SIN_DISPONIBILIDAD';
                            $cotizacion->observacion = ($cotizacion->observacion ? $cotizacion->observacion . " | " : "")
                                . "Sin cupos suficientes al intentar aceptar (" . now()->format('d/m/Y H:i') . ")";
                            $cotizacion->save();

                            Auditoria::registrar(
                                $idUsuario,
                                'COTIZACION_SIN_DISPONIBILIDAD',
                                'cotizacion',
                                $cotizacion->id_cotizacion,
                                "No hay cupos suficientes para salida {$det->id_salida}."
                            );

                            return $cotizacion;
                        }
                    }
                }
            }

            $estadoAnterior = $cotizacion->estado;
            $cotizacion->estado = $nuevoEstado;
            if ($motivo) {
                $cotizacion->observacion = ($cotizacion->observacion ? $cotizacion->observacion . " | " : "") . $motivo;
            }
            $cotizacion->save();

            $accionAudit = ($nuevoEstado === 'ACEPTADA') ? 'ACEPTAR_COTIZACION' : 'CAMBIAR_ESTADO_COTIZACION';
            Auditoria::registrar(
                $idUsuario,
                $accionAudit,
                'cotizacion',
                $cotizacion->id_cotizacion,
                "Cotización {$cotizacion->codigo} cambió de {$estadoAnterior} a {$nuevoEstado}."
            );

            return $cotizacion;
        });
    }

    public function generarPdf(int $idCotizacion)
    {
        $cotizacion = $this->obtenerPorId($idCotizacion);

        $logoPath = public_path('img/logo-18-grados-sur.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('cotizaciones.pdf', [
            'cotizacion' => $cotizacion,
            'logoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf;
    }
}
