# Reglas, roles, estados y casos de uso

> La numeración RN proviene del SRS v3.2. Las marcadas con ✅ ya están implementadas y cubiertas
> por tests en Laravel. Ante duda sobre la redacción exacta, verificar en el PDF del SRS.

## Matriz de roles

| Módulo / acción | GERENTE | COUNTER | CONTABILIDAD | Grupo de rutas |
| :--- | :---: | :---: | :---: | :--- |
| Inicio (dashboard) | ✔ | ✔ | ✔ | `auth` |
| Clientes, Destinos, Tours, Salidas | ✔ | ✔ | ✖ | `rol:GERENTE,COUNTER` |
| Cambiar precio base de tour | ✔ | ✖ | ✖ | grupo GERENTE,COUNTER + chequeo en controlador |
| Cotizaciones, Reservas | ✔ | ✔ | ✖ | `rol:GERENTE,COUNTER` |
| Ver bandeja / registrar pagos | ✔ | ✔ | ✔ | `rol:GERENTE,COUNTER,CONTABILIDAD` |
| Validar / rechazar pagos | ✔ | ✖ | ✔ | `rol:GERENTE,CONTABILIDAD` |
| Reportes | ✔ | ✖ | ✔ | `rol:GERENTE,CONTABILIDAD` |
| Usuarios y contraseñas | ✔ | ✖ | ✖ | `rol:GERENTE` |

Contabilidad **no** registra clientes ni reservas; Counter **no** valida pagos (segregación de funciones).

## Reglas de negocio

| RN | Regla | Dónde se aplica | Estado |
| :--- | :--- | :--- | :---: |
| RN02 | Persona única por (tipo_documento, numero_documento). Si ya existe con mismos nombres se reutiliza; si los nombres difieren se rechaza. Una persona solo puede ser cliente una vez. DNI = 8 dígitos (no `0000000X` ni repetidos), Pasaporte 5–20 alfanum., CE 6–15. | `ClienteService`, `ValidadorPersonaService` | ✅ |
| RN04 | Solo GERENTE modifica el precio base; motivo obligatorio. | `ProductoController::cambiarPrecio` | ✅ |
| RN05 | Anticipo mínimo para reservar: `producto_turistico.porcentaje_reserva` (≥ 50 % en `TOUR_LOCAL`). Una cotización solo pasa a reserva si `SUM(pago.monto_base WHERE estado='VALIDADO') >= total * porcentaje/100`. | Alta de tour ✅ / generar reserva ⏳ | parcial |
| RN06 | No se duplica salida activa del mismo producto en la misma fecha y hora. | `SalidaService::programarSalida` | ✅ |
| RN07 | Nunca se vende más cupos que la capacidad. Generar reserva bloquea la salida con `lockForUpdate`. La capacidad no puede bajar de los cupos ocupados. | `SalidaService::cambiarCapacidad` ✅ / reserva ⏳ | parcial |
| RN08 | Solo se cancela una salida sin pasajeros reservados. | `SalidaService::cancelarSalida` | ✅ |
| RN10 | Todo pago se registra como `PENDIENTE_VALIDACION` (salvo métodos con `requiere_validacion = 0`, a definir con el SRS) y no cuenta para saldo hasta ser `VALIDADO`. | Pagos ⏳ | pendiente |
| RN11 | Validar/rechazar lo hace GERENTE o CONTABILIDAD; el rechazo exige observación. Registra `id_usuario_validacion` y `fecha_validacion`. | Pagos ⏳ | pendiente |
| RN13 | Por cada pasajero se guarda persona (documento, nombres), punto de recojo, restricción alimentaria y condición particular (Ley 29733: datos sensibles, no exponer en listados públicos). | Reservas ⏳ | pendiente |
| RN16 | Toda operación crítica inserta en `auditoria` (cambio de precio/capacidad, cancelaciones, CRUD de clientes/destinos, cotizar, pagar, validar, reservar). | `Auditoria::registrar()` | ✅ continuo |
| — | Comprobante de pago ≤ 5 MB (jpg, png, pdf). | Pagos ⏳ | pendiente |
| — | Recargo de tarjeta: `recargo = monto_base * metodo_pago.porcentaje_recargo / 100`; `monto_total_pagado = monto_base + recargo`. | Pagos ⏳ | pendiente |

## Máquinas de estado

```
cotizacion.estado:  VIGENTE ─► ACEPTADA ─► (reserva generada)
                       ├─► NO_ACEPTADA
                       ├─► CANCELADA
                       └─► SIN_DISPONIBILIDAD   (si la salida ya no tiene cupos)
pago.estado:        PENDIENTE_VALIDACION ─► VALIDADO | RECHAZADO
reserva.estado:     CONFIRMADA ─► COMPLETADA (viaje realizado y saldo 0) | CANCELADA (libera cupos)
detalle_reserva:    ACTIVO ─► COMPLETADO | CANCELADO
salida.estado:      PROGRAMADA ⇄ COMPLETA (ocupados == capacidad) ─► FINALIZADA | CANCELADA
```

Los cupos ocupados se cuentan como pasajeros (`reserva_pasajero`) de `detalle_reserva` no
`CANCELADO` cuya `reserva` no esté `CANCELADA` — misma lógica que `vw_disponibilidad_salida`.

## Códigos correlativos

Formato heredado del prototipo: `PREFIJO-YYYYMMDD-HHMMSS-XXXX` (XXXX = 4 hex en mayúsculas).
Ej.: `COT-20260927-195040-5D4B`, `RES-20260927-232127-C143`. Columna `codigo` varchar(30), única.

## Casos de uso

| CU | Nombre | Estado |
| :--- | :--- | :---: |
| CU01–CU03 | Login con CAPTCHA, panel de inicio, logout | ✅ |
| CU04–CU05 | Gestionar usuarios, cambiar contraseña (GERENTE) | ⏳ |
| CU06–CU07 | Gestionar clientes, historial | ✅ |
| CU08 | Gestionar destinos | ✅ |
| CU09 | Gestionar tours y paquetes | ✅ |
| CU10 | Asignar destinos y servicios a un producto (`producto_destino`, `producto_servicio`) | ⏳ UI pendiente |
| CU11 | Cambiar precio (GERENTE, auditado) | ✅ |
| CU12–CU14 | Programar salida, cambiar capacidad, cancelar salida | ✅ |
| CU15–CU17 | Registrar/editar cotización, PDF, aceptar | ⏳ siguiente |
| CU18–CU19 | Registrar pago, validar/rechazar | ⏳ |
| CU20–CU23 | Generar, modificar, cancelar, completar reserva | ⏳ |
| CU24 | Reportes y PDF | ⏳ |

Al completar un CU, actualizar esta tabla en el mismo commit.
