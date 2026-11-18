-- =====================================================================
-- Devoluciones de pagos online (total o parcial) vía pasarela.
-- Idempotente: se puede ejecutar más de una vez.
--
-- Aplicar con:
--   psql -h 127.0.0.1 -U postgres -d db_18_experience -v ON_ERROR_STOP=1 -f database/sql/2026_11_12_devoluciones_pago.sql
-- =====================================================================

BEGIN;

-- 1. Acumulados en el propio pago para que los saldos se calculen sin joins.
--    monto_devuelto:      lo devuelto al cliente (en términos de monto_total_pagado, incluye recargo).
--    monto_base_devuelto: la parte de monto_base que deja de contar para el saldo y el anticipo (RN05).
ALTER TABLE pago ADD COLUMN IF NOT EXISTS monto_devuelto      numeric(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE pago ADD COLUMN IF NOT EXISTS monto_base_devuelto numeric(10,2) NOT NULL DEFAULT 0.00;

-- 2. Historial de devoluciones (una fila por devolución en la pasarela).
CREATE TABLE IF NOT EXISTS devolucion_pago (
    id_devolucion    serial PRIMARY KEY,
    id_pago          integer       NOT NULL REFERENCES pago (id_pago),
    monto            numeric(10,2) NOT NULL CHECK (monto > 0),
    monto_base       numeric(10,2) NOT NULL CHECK (monto_base >= 0),
    numero_operacion varchar(100)  NOT NULL UNIQUE,   -- ID de la devolución en la pasarela (ref_...)
    motivo           varchar(500)  NOT NULL,
    id_usuario       integer       NOT NULL REFERENCES usuario (id_usuario),
    fecha            timestamp     NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_devolucion_pago_pago ON devolucion_pago (id_pago);

-- 3. El saldo de la reserva descuenta lo devuelto.
CREATE OR REPLACE VIEW vw_saldo_reserva AS
 SELECT r.id_reserva,
    r.codigo AS codigo_reserva,
    r.id_cotizacion,
    r.total_contratado,
    (COALESCE(sum(
        CASE
            WHEN ((p.estado)::text = 'VALIDADO'::text) THEN p.monto_base - p.monto_base_devuelto
            ELSE (0)::numeric
        END), (0)::numeric))::numeric(10,2) AS monto_validado,
    (GREATEST((0)::numeric, (r.total_contratado - COALESCE(sum(
        CASE
            WHEN ((p.estado)::text = 'VALIDADO'::text) THEN p.monto_base - p.monto_base_devuelto
            ELSE (0)::numeric
        END), (0)::numeric))))::numeric(10,2) AS saldo_pendiente
   FROM (reserva r
     LEFT JOIN pago p ON ((p.id_cotizacion = r.id_cotizacion)))
  GROUP BY r.id_reserva, r.codigo, r.id_cotizacion, r.total_contratado;

COMMIT;
