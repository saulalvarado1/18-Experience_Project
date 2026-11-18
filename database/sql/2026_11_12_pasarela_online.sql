-- =====================================================================
-- Pasarela de pagos online (Culqi)
-- Idempotente: se puede ejecutar más de una vez sin efectos duplicados.
--
-- Aplicar con:
--   psql -h 127.0.0.1 -U postgres -d db_18_experience -v ON_ERROR_STOP=1 -f database/sql/2026_11_12_pasarela_online.sql
-- =====================================================================

BEGIN;

-- 1. Método de pago de la pasarela.
--    requiere_validacion = FALSE: el cargo ya fue confirmado por la pasarela (RN10).
--    El recargo es configurable aquí; el código aplica lo que diga esta fila.
INSERT INTO metodo_pago (nombre, requiere_validacion, aplica_recargo, porcentaje_recargo, activo)
VALUES ('PASARELA_ONLINE', FALSE, FALSE, 0.00, TRUE)
ON CONFLICT (nombre) DO NOTHING;

-- 2. Idempotencia: un mismo cargo de la pasarela (chr_...) no puede registrarse dos veces.
--    Índice parcial solo para el método online, porque los pagos manuales históricos
--    sí tienen números de operación repetidos.
DO $$
DECLARE
    v_id integer;
BEGIN
    SELECT id_metodo_pago INTO v_id FROM metodo_pago WHERE nombre = 'PASARELA_ONLINE';

    IF NOT EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'uq_pago_operacion_pasarela') THEN
        EXECUTE format(
            'CREATE UNIQUE INDEX uq_pago_operacion_pasarela ON pago (numero_operacion) '
            'WHERE id_metodo_pago = %s AND numero_operacion IS NOT NULL',
            v_id
        );
    END IF;
END $$;

COMMIT;
