# Esquema real de db_18_experience (PostgreSQL 18)

Fuente exacta y versionada: `database/schema/pgsql-schema.sql` (+ datos de catálogo en `datos-base.sql`).
Cambios de esquema: scripts idempotentes en `database/sql/AAAA_MM_DD_*.sql` y luego regenerar el dump.

Todas las PK son `integer` con secuencia (`serial`) llamadas `id_<tabla>`. Los "enum" son `varchar`
(sin CHECK); los valores válidos son los indicados abajo. Ninguna tabla tiene `created_at/updated_at`
→ en Eloquent `public $timestamps = false`. Fechas: `timestamp without time zone` en **hora de Perú**
(app y conexión en `America/Lima`; ver `ZonaHorariaTest`).

## Seguridad
- **persona**: id_persona, tipo_documento varchar(20), numero_documento varchar(30), nombres varchar(100), apellidos varchar(120), fecha_nacimiento date?, nacionalidad varchar(60)?, genero varchar(30)?, telefono varchar(30)?, correo varchar(120)?, activo boolean=true
- **rol**: id_rol, nombre varchar(50) (`GERENTE`,`COUNTER`,`CONTABILIDAD`), descripcion?, activo
- **usuario**: id_usuario, id_persona, id_rol, nombre_usuario varchar(50), password_hash varchar(255) (`PBKDF2$iter$salt$hash` heredado o bcrypt), activo, fecha_creacion timestamp, ultimo_acceso timestamp?
- **auditoria**: id_auditoria bigint, id_usuario, accion varchar(80), entidad varchar(80), id_registro bigint?, fecha_hora timestamp=now, detalle text?

## Clientes
- **cliente**: id_cliente, id_persona (UNIQUE), fecha_registro timestamp, tipo_cliente enum(`DIRECTO`,`CORPORATIVO`), activo

## Catálogo
- **destino**: id_destino, nombre, descripcion, ubicacion, altitud_msnm decimal, como_llegar, temporada_recomendada, recomendaciones, activo
- **servicio**: id_servicio, nombre, descripcion, activo
- **producto_turistico**: id_producto, codigo, nombre, tipo_producto (`TOUR_LOCAL`,`PAQUETE_NACIONAL`,`PAQUETE_INTERNACIONAL`), descripcion, itinerario, duracion_dias, precio_base decimal(10,2), moneda char(3), porcentaje_reserva decimal(5,2), minimo_pasajeros, activo
- **producto_destino**: id_producto_destino, id_producto, id_destino, orden_visita smallint?, observacion varchar(500)?
- **producto_servicio**: id_producto_servicio, id_producto, id_servicio, incluido boolean=true, precio_adicional decimal(10,2)=0, observacion?
- **salida**: id_salida, id_producto, fecha date, hora time, capacidad smallint, estado enum(`PROGRAMADA`,`COMPLETA`,`FINALIZADA`,`CANCELADA`)=`PROGRAMADA`, observacion

## Ventas
- **cotizacion**: id_cotizacion, codigo varchar(30), id_cliente, id_usuario_counter, fecha_cotizacion timestamp=now, estado enum(`VIGENTE`,`ACEPTADA`,`NO_ACEPTADA`,`CANCELADA`,`SIN_DISPONIBILIDAD`)=`VIGENTE`, moneda char(3)=`PEN`, total decimal(10,2)=0, vigencia_hasta timestamp?, observacion text?
- **detalle_cotizacion**: id_detalle_cotizacion, id_cotizacion, id_producto, id_salida?, cantidad_pasajeros smallint, precio_unitario decimal, monto_adicional decimal=0, subtotal decimal, observacion varchar(500)?
- **reserva**: id_reserva, codigo varchar(30), id_cotizacion, id_usuario_counter, fecha_reserva timestamp=now, estado enum(`CONFIRMADA`,`COMPLETADA`,`CANCELADA`)=`CONFIRMADA`, moneda, total_contratado decimal, fecha_limite_pago timestamp?, observacion text?
- **detalle_reserva**: id_detalle_reserva, id_reserva, id_producto, id_salida?, cantidad_pasajeros, precio_unitario, subtotal, punto_recojo varchar(255)?, estado enum(`ACTIVO`,`COMPLETADO`,`CANCELADO`)=`ACTIVO`, observacion?
- **reserva_pasajero**: id_reserva_pasajero, id_detalle_reserva, id_persona, restriccion_alimentaria varchar(500)?, condicion_particular varchar(500)?, observacion?

## Finanzas
- **metodo_pago**: id_metodo_pago, nombre, requiere_validacion, aplica_recargo, porcentaje_recargo decimal(5,2), activo.
  Datos: 1 EFECTIVO(0,0,0) · 2 TRANSFERENCIA(1,0,0) · 3 DEPOSITO(1,0,0) · 4 YAPE(1,0,0) · 5 PLIN(1,0,0) · 6 TARJETA(0,1,5.00) · 7 LINK_DE_PAGO(0,0,0) · 8 PASARELA_ONLINE(0,0,0) (solo lo registra la pasarela)
- **pago**: id_pago, id_cotizacion (el pago se asocia a la cotización, no a la reserva), id_metodo_pago, id_usuario_registro, id_usuario_validacion?, fecha_pago timestamp=now, moneda, monto_base decimal, recargo decimal=0, monto_total_pagado decimal, estado (`PENDIENTE_VALIDACION`,`VALIDADO`,`RECHAZADO`,`DEVUELTO`), numero_operacion varchar(100)?, ruta_comprobante varchar(500)? (archivo privado en `storage/app/private`), fecha_validacion?, observacion varchar(500)?, monto_devuelto decimal=0, monto_base_devuelto decimal=0.
  Índice único parcial `uq_pago_operacion_pasarela` (numero_operacion de PASARELA_ONLINE).
  **Saldos y anticipo siempre con `monto_base - monto_base_devuelto`.**
- **devolucion_pago**: id_devolucion, id_pago, monto, monto_base, numero_operacion (ref_… UNIQUE), motivo, id_usuario, fecha
- **ajuste_datos**: nombre (PK), aplicado_en, detalle — registro de correcciones de datos ya aplicadas (p. ej. zona horaria)

## Vistas (solo lectura)
- **vw_disponibilidad_salida**: id_salida, id_producto, codigo_producto, `producto` (nombre del tour, varchar 180), fecha, hora, capacidad, estado, cupos_ocupados, cupos_disponibles.
  Ocupados = COUNT(reserva_pasajero) de detalle_reserva no `CANCELADO` con reserva no `CANCELADA`.
- **vw_saldo_reserva**: id_reserva, codigo_reserva, id_cotizacion, total_contratado, monto_validado (SUM monto_base − monto_base_devuelto de pagos VALIDADO), saldo_pendiente = GREATEST(total − validado, 0).

Para lecturas usar las vistas; para decisiones que escriben (reservar) recalcular dentro de la
transacción con la salida bloqueada (`lockForUpdate`), nunca confiar en la vista leída antes.
