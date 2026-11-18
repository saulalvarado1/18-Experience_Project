-- =====================================================================
-- Corrección de zona horaria (UTC -> America/Lima)
--
-- Hasta el 12/11/2026 Laravel corría en UTC y guardaba now() con 5 horas de más,
-- mientras PostgreSQL (America/Bogota, UTC-5) guardaba en hora local. Desde ahora
-- ambos usan America/Lima (config/app.php y config/database.php).
--
-- Este script resta 5 horas solo a lo que escribió Laravel en UTC:
--   * Todos los registros con fecha >= 2026-10-24 12:43 UTC (primer commit Laravel:
--     24/10/2026 07:43 hora local). No hay datos entre el 29/09 (fin del prototipo Java,
--     hora local) y el 24/10, así que el corte es inequívoco.
--   * vigencia_hasta / fecha_limite_pago se corrigen según la fecha de creación de su fila.
--
-- Se aplica UNA sola vez: queda registrado en la tabla ajuste_datos y, si ya existe,
-- el script no hace nada. Respaldo previo: storage/app/private/backups/antes_zona_horaria_2026-11-12.sql
--
-- Aplicar con:
--   psql -h 127.0.0.1 -U postgres -d db_18_experience -v ON_ERROR_STOP=1 -f database/sql/2026_11_12_zona_horaria.sql
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS ajuste_datos (
    nombre      varchar(100) PRIMARY KEY,
    aplicado_en timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalle     text
);

DO $$
DECLARE
    corte constant timestamp := '2026-10-24 12:43:00';  -- inicio de la época Laravel, en UTC
    d     constant interval  := interval '5 hours';
    n     integer;
    resumen text := '';
BEGIN
    IF EXISTS (SELECT 1 FROM ajuste_datos WHERE nombre = 'zona_horaria_utc_a_lima') THEN
        RAISE NOTICE 'La corrección de zona horaria ya fue aplicada; no se hace nada.';
        RETURN;
    END IF;

    -- Columnas dependientes primero (usan la fecha de creación aún sin corregir).
    UPDATE cotizacion SET vigencia_hasta = vigencia_hasta - d
     WHERE fecha_cotizacion >= corte AND vigencia_hasta IS NOT NULL;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'cotizacion.vigencia_hasta=' || n || '; ';

    UPDATE reserva SET fecha_limite_pago = fecha_limite_pago - d
     WHERE fecha_reserva >= corte AND fecha_limite_pago IS NOT NULL;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'reserva.fecha_limite_pago=' || n || '; ';

    UPDATE cotizacion SET fecha_cotizacion = fecha_cotizacion - d WHERE fecha_cotizacion >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'cotizacion.fecha_cotizacion=' || n || '; ';

    UPDATE reserva SET fecha_reserva = fecha_reserva - d WHERE fecha_reserva >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'reserva.fecha_reserva=' || n || '; ';

    UPDATE pago SET fecha_pago = fecha_pago - d WHERE fecha_pago >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'pago.fecha_pago=' || n || '; ';

    UPDATE pago SET fecha_validacion = fecha_validacion - d WHERE fecha_validacion >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'pago.fecha_validacion=' || n || '; ';

    UPDATE devolucion_pago SET fecha = fecha - d WHERE fecha >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'devolucion_pago.fecha=' || n || '; ';

    UPDATE cliente SET fecha_registro = fecha_registro - d WHERE fecha_registro >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'cliente.fecha_registro=' || n || '; ';

    UPDATE usuario SET fecha_creacion = fecha_creacion - d WHERE fecha_creacion >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'usuario.fecha_creacion=' || n || '; ';

    UPDATE usuario SET ultimo_acceso = ultimo_acceso - d WHERE ultimo_acceso >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'usuario.ultimo_acceso=' || n || '; ';

    UPDATE auditoria SET fecha_hora = fecha_hora - d WHERE fecha_hora >= corte;
    GET DIAGNOSTICS n = ROW_COUNT; resumen := resumen || 'auditoria.fecha_hora=' || n || '; ';

    INSERT INTO ajuste_datos (nombre, detalle) VALUES ('zona_horaria_utc_a_lima', resumen);
    RAISE NOTICE 'Filas corregidas: %', resumen;
END $$;

COMMIT;
