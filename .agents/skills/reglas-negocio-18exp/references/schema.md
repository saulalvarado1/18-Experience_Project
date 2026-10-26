# Esquema real de db_18_experience (MariaDB 10.4, utf8mb4)

Todas las PK son `int unsigned AUTO_INCREMENT` llamadas `id_<tabla>`. Ninguna tabla tiene
`created_at/updated_at` → en Eloquent `public $timestamps = false`.

## Seguridad
- **persona**: id_persona, tipo_documento varchar(20), numero_documento varchar(30), nombres varchar(100), apellidos varchar(120), fecha_nacimiento date?, nacionalidad varchar(60)?, genero varchar(30)?, telefono varchar(30)?, correo varchar(120)?, activo tinyint=1
- **rol**: id_rol, nombre varchar(50) (`GERENTE`,`COUNTER`,`CONTABILIDAD`), descripcion?, activo
- **usuario**: id_usuario, id_persona, id_rol, nombre_usuario varchar(50), password_hash varchar(255) (`PBKDF2$iter$salt$hash` heredado o bcrypt), activo, fecha_creacion datetime, ultimo_acceso datetime?
- **auditoria**: id_auditoria bigint, id_usuario, accion varchar(80), entidad varchar(80), id_registro bigint?, fecha_hora datetime=now, detalle text?

## Clientes
- **cliente**: id_cliente, id_persona (UNIQUE), fecha_registro datetime, tipo_cliente enum(`DIRECTO`,`CORPORATIVO`), activo

## Catálogo
- **destino**: id_destino, nombre, descripcion, ubicacion, altitud_msnm decimal, como_llegar, temporada_recomendada, recomendaciones, activo
- **servicio**: id_servicio, nombre, descripcion, activo
- **producto_turistico**: id_producto, codigo, nombre, tipo_producto (`TOUR_LOCAL`,`PAQUETE_NACIONAL`,`PAQUETE_INTERNACIONAL`), descripcion, itinerario, duracion_dias, precio_base decimal(10,2), moneda char(3), porcentaje_reserva decimal(5,2), minimo_pasajeros, activo
- **producto_destino**: id_producto_destino, id_producto, id_destino, orden_visita smallint?, observacion varchar(500)?
- **producto_servicio**: id_producto_servicio, id_producto, id_servicio, incluido tinyint=1, precio_adicional decimal(10,2)=0, observacion?
- **salida**: id_salida, id_producto, fecha date, hora time, capacidad smallint, estado enum(`PROGRAMADA`,`COMPLETA`,`FINALIZADA`,`CANCELADA`)=`PROGRAMADA`, observacion

## Ventas
- **cotizacion**: id_cotizacion, codigo varchar(30), id_cliente, id_usuario_counter, fecha_cotizacion datetime=now, estado enum(`VIGENTE`,`ACEPTADA`,`NO_ACEPTADA`,`CANCELADA`,`SIN_DISPONIBILIDAD`)=`VIGENTE`, moneda char(3)=`PEN`, total decimal(10,2)=0, vigencia_hasta datetime?, observacion text?
- **detalle_cotizacion**: id_detalle_cotizacion, id_cotizacion, id_producto, id_salida?, cantidad_pasajeros smallint, precio_unitario decimal, monto_adicional decimal=0, subtotal decimal, observacion varchar(500)?
- **reserva**: id_reserva, codigo varchar(30), id_cotizacion, id_usuario_counter, fecha_reserva datetime=now, estado enum(`CONFIRMADA`,`COMPLETADA`,`CANCELADA`)=`CONFIRMADA`, moneda, total_contratado decimal, fecha_limite_pago datetime?, observacion text?
- **detalle_reserva**: id_detalle_reserva, id_reserva, id_producto, id_salida?, cantidad_pasajeros, precio_unitario, subtotal, punto_recojo varchar(255)?, estado enum(`ACTIVO`,`COMPLETADO`,`CANCELADO`)=`ACTIVO`, observacion?
- **reserva_pasajero**: id_reserva_pasajero, id_detalle_reserva, id_persona, restriccion_alimentaria varchar(500)?, condicion_particular varchar(500)?, observacion?

## Finanzas
- **metodo_pago**: id_metodo_pago, nombre, requiere_validacion, aplica_recargo, porcentaje_recargo decimal(5,2), activo.
  Datos: 1 EFECTIVO(0,0,0) · 2 TRANSFERENCIA(1,0,0) · 3 DEPOSITO(1,0,0) · 4 YAPE(1,0,0) · 5 PLIN(1,0,0) · 6 TARJETA(0,1,5.00) · 7 LINK_DE_PAGO(0,0,0)
- **pago**: id_pago, id_cotizacion (el pago se asocia a la cotización, no a la reserva), id_metodo_pago, id_usuario_registro, id_usuario_validacion?, fecha_pago datetime=now, moneda, monto_base decimal, recargo decimal=0, monto_total_pagado decimal, estado enum(`PENDIENTE_VALIDACION`,`VALIDADO`,`RECHAZADO`), numero_operacion varchar(100)?, ruta_comprobante varchar(500)?, fecha_validacion datetime?, observacion varchar(500)?

## Vistas (solo lectura)
- **vw_disponibilidad_salida**: id_salida, id_producto, codigo_producto, `producto` (nombre del tour, varchar 180), fecha, hora, capacidad, estado, cupos_ocupados, cupos_disponibles.
  Ocupados = COUNT(reserva_pasajero) de detalle_reserva no `CANCELADO` con reserva no `CANCELADA`.
- **vw_saldo_reserva**: id_reserva, codigo_reserva, id_cotizacion, total_contratado, monto_validado (SUM monto_base VALIDADO), saldo_pendiente = GREATEST(total − validado, 0).

Para lecturas usar las vistas; para decisiones que escriben (reservar) recalcular dentro de la
transacción con la salida bloqueada (`lockForUpdate`), nunca confiar en la vista leída antes.
