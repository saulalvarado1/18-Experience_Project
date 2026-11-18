-- =====================================================================
-- CU10 – Asignar destinos y servicios al producto
-- Un destino o un servicio no puede asignarse dos veces al mismo producto (SRS CU10, flujo 3a).
-- Idempotente. Aplicar con:
--   psql -h 127.0.0.1 -U postgres -d db_18_experience -v ON_ERROR_STOP=1 -f database/sql/2026_11_12_composicion_producto.sql
-- =====================================================================

BEGIN;

CREATE UNIQUE INDEX IF NOT EXISTS uq_producto_destino ON producto_destino (id_producto, id_destino);
CREATE UNIQUE INDEX IF NOT EXISTS uq_producto_servicio ON producto_servicio (id_producto, id_servicio);

COMMIT;
