---
name: ventas-transaccional
description: >-
  Patrones obligatorios para el flujo comercial crítico de 18 Experience: cotización → pago →
  validación contable → reserva con pasajeros. Cubre bloqueo pesimista de cupos (lockForUpdate),
  cálculo de anticipo mínimo, recargos, generación de códigos COT/RES, carga de comprobantes y
  tests de concurrencia/overbooking. Usar al implementar o modificar CU15-CU23 (Cotizaciones,
  Pagos, Reservas) o cualquier código que cambie cupos o saldos.
---

# Flujo transaccional de ventas

Leer primero las reglas RN05, RN07, RN10, RN11, RN13 en
[reglas.md](../reglas-negocio-18exp/references/reglas.md) y las tablas `cotizacion`, `pago`,
`reserva*` en [schema.md](../reglas-negocio-18exp/references/schema.md).
La lógica original está en `18Experience_FINAL/src/experience18/dao/ReservaDAO.java`
(`insertarReserva`) y `service/{Cotizacion,Pago,Reserva}Service.java`.

## Ubicación de módulos

- `app/Modules/Ventas/` → `Cotizacion`, `DetalleCotizacion`, `Reserva`, `DetalleReserva`, `ReservaPasajero`
  con `CotizacionService` y `ReservaService`.
- `app/Modules/Finanzas/` → `Pago`, `MetodoPago` con `PagoService`.
- Ventas consulta saldos a través de `PagoService::montoValidado($idCotizacion)`, no con SQL propio.

## 1. Códigos

```php
$codigo = $prefijo . '-' . now()->format('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
```
Prefijos `COT`, `RES`. Insertar dentro de la transacción; ante colisión de UNIQUE reintentar 1 vez.

## 2. Cotización (CU15–CU17)

- Solo clientes activos y productos activos. Si el detalle tiene `id_salida`, la salida debe estar
  `PROGRAMADA`, con fecha futura y `cupos_disponibles >= cantidad_pasajeros` (consulta a la vista; aquí
  no se bloquea, la cotización **no reserva cupos**).
- `subtotal = cantidad_pasajeros * precio_unitario + monto_adicional`; `total = Σ subtotal`.
  Usar aritmética con `round(..., 2)` o `bcadd`; nunca comparar floats con `==` (usar tolerancia 0.001).
- Solo se edita en estado `VIGENTE`. Aceptar (CU17) exige `VIGENTE`, vigencia no vencida y cupos aún
  disponibles; si no hay cupos → estado `SIN_DISPONIBILIDAD`.
- Auditar `CREAR_COTIZACION`, `EDITAR_COTIZACION`, `ACEPTAR_COTIZACION` (nombres ya usados en la BD).

## 3. Pago (CU18–CU19)

- Solo sobre cotizaciones `ACEPTADA` (o con reserva `CONFIRMADA` para saldos).
- `recargo = round(monto_base * porcentaje_recargo / 100, 2)` si `metodo.aplica_recargo`;
  `monto_total_pagado = monto_base + recargo`. El saldo y el anticipo se calculan con `monto_base`.
- No permitir que `Σ monto_base no RECHAZADO + nuevo > total` de la cotización.
- Comprobante: `$request->validate(['comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'])`,
  guardar con `$file->store('comprobantes', 'local')` (disco privado) y servirlo solo por una ruta
  protegida por rol. Guardar la ruta relativa en `ruta_comprobante`.
- Validar/Rechazar: solo desde `PENDIENTE_VALIDACION`; rechazo con observación obligatoria; setear
  `id_usuario_validacion = Auth::id()` y `fecha_validacion = now()`. Auditar `REGISTRAR_PAGO`,
  `VALIDAR_PAGO`, `RECHAZAR_PAGO`.

## 4. Reserva con bloqueo pesimista (CU20) — patrón obligatorio

```php
return DB::transaction(function () use ($cotizacion, $pasajerosPorDetalle, $idUsuario) {
    // a) Re-leer y bloquear la cotización para impedir doble reserva concurrente
    $cot = Cotizacion::whereKey($cotizacion->id_cotizacion)->lockForUpdate()->firstOrFail();
    if ($cot->estado !== 'ACEPTADA' || Reserva::where('id_cotizacion', $cot->id_cotizacion)->exists()) {
        throw new \InvalidArgumentException('La cotización no está disponible para reservar.');
    }

    // b) Anticipo mínimo (RN05) con pagos VALIDADO únicamente
    $validado = $this->pagoService->montoValidado($cot->id_cotizacion);
    // porcentaje = MAX(porcentaje_reserva) de los productos del detalle
    if ($validado + 0.001 < round($cot->total * $porcentaje / 100, 2)) { throw ...; }

    // c) Por cada detalle con salida: bloquear EN ORDEN ASCENDENTE de id_salida (evita deadlocks)
    foreach ($detallesOrdenadosPorSalida as $det) {
        $salida = Salida::whereKey($det->id_salida)->lockForUpdate()->firstOrFail();
        $ocupados = /* COUNT reserva_pasajero, misma lógica que vw_disponibilidad_salida */;
        if ($salida->estado !== 'PROGRAMADA' || $ocupados + $det->cantidad_pasajeros > $salida->capacidad) {
            throw new \InvalidArgumentException('Cupos insuficientes para la salida del ...');
        }
    }

    // d) Insertar reserva, detalle_reserva, personas (reutilizar ClienteService/Persona por documento)
    //    y reserva_pasajero. N.º de pasajeros registrados == cantidad_pasajeros del detalle.
    // e) Si ocupados == capacidad tras insertar → salida.estado = 'COMPLETA'
    // f) Auditoria::registrar(..., 'GENERAR_RESERVA', 'reserva', $id, "Código: ... | Total: ...")
});
```

Nunca validar cupos fuera de la transacción ni con la vista leída antes del `lockForUpdate`.

- Cancelar (CU22): motivo obligatorio, `reserva.estado = CANCELADA`, detalles `CANCELADO`, salidas
  `COMPLETA` vuelven a `PROGRAMADA`. Auditar `CANCELAR_RESERVA`.
- Completar (CU23): solo si `vw_saldo_reserva.saldo_pendiente = 0` y las salidas ya pasaron.

## 5. Tests mínimos

1. Reserva rechazada si el pago validado < anticipo (y aceptada al alcanzarlo).
2. Pago `PENDIENTE_VALIDACION` o `RECHAZADO` no cuenta para el anticipo.
3. Overbooking: con salida de capacidad N y N ocupados, una nueva reserva se rechaza.
4. Doble reserva de la misma cotización se rechaza.
5. Counter recibe error/403 al validar pagos; Contabilidad puede.
6. Cada operación deja su fila en `auditoria`.

Prueba de concurrencia real (opcional, manual): dos procesos `php artisan tinker` que llamen
`ReservaService::generar` sobre el último cupo; solo uno debe confirmar.
Todos los tests de escritura usan `DB::beginTransaction()` / `DB::rollBack()` (ver skill `modulo-dominio-laravel`).
