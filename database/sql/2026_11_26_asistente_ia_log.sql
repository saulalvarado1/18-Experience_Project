-- ============================================================================
-- Tabla para registrar consultas de Asistente IA y métricas de demanda turística
-- ============================================================================

CREATE TABLE IF NOT EXISTS asistente_consulta (
    id_consulta SERIAL PRIMARY KEY,
    id_usuario INTEGER NULL,
    pregunta TEXT NOT NULL,
    categoria_detectada VARCHAR(50) NOT NULL DEFAULT 'GENERAL',
    motor_usado VARCHAR(50) NOT NULL DEFAULT 'HEURISTICO',
    creado_en TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_asistente_consulta_usuario FOREIGN KEY (id_usuario) 
        REFERENCES usuario (id_usuario) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_asistente_consulta_categoria ON asistente_consulta (categoria_detectada);
CREATE INDEX IF NOT EXISTS idx_asistente_consulta_fecha ON asistente_consulta (creado_en);
