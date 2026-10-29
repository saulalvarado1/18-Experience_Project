<?php

/**
 * Inicializador idempotente de Base de Datos PostgreSQL (db_18_experience)
 * Crea la base de datos, tablas del dominio, vistas analíticas y datos semilla.
 */

$host = '127.0.0.1';
$port = 5432;
$user = 'postgres';
$pass = 'saul123';
$dbName = 'db_18_experience';

try {
    // 1. Conectar a la BD administrativa 'postgres'
    $pdoAdmin = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdoAdmin->exec("ALTER USER postgres WITH PASSWORD '{$pass}';");

    $stmt = $pdoAdmin->query("SELECT 1 FROM pg_database WHERE datname = '{$dbName}'");
    if (!$stmt->fetchColumn()) {
        $pdoAdmin->exec("CREATE DATABASE {$dbName} ENCODING 'UTF8'");
        echo "[OK] Base de datos '{$dbName}' creada en PostgreSQL.\n";
    }

    // 2. Conectar a 'db_18_experience'
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbName}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Operadores de compatibilidad boolean = integer para consultas Eloquent heredadas
    $pdo->exec(<<<'SQL'
CREATE OR REPLACE FUNCTION public.bool_eq_int(boolean, integer) RETURNS boolean AS $$
    SELECT ($1 IS TRUE AND $2 <> 0) OR ($1 IS FALSE AND $2 = 0);
$$ LANGUAGE sql IMMUTABLE STRICT;

CREATE OR REPLACE FUNCTION public.int_eq_bool(integer, boolean) RETURNS boolean AS $$
    SELECT ($2 IS TRUE AND $1 <> 0) OR ($2 IS FALSE AND $1 = 0);
$$ LANGUAGE sql IMMUTABLE STRICT;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_operator WHERE oprname = '=' AND oprleft = 'bool'::regtype AND oprright = 'int4'::regtype
    ) THEN
        CREATE OPERATOR public.= (
            LEFTARG = boolean,
            RIGHTARG = integer,
            PROCEDURE = public.bool_eq_int,
            COMMUTATOR = =
        );
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_operator WHERE oprname = '=' AND oprleft = 'int4'::regtype AND oprright = 'bool'::regtype
    ) THEN
        CREATE OPERATOR public.= (
            LEFTARG = integer,
            RIGHTARG = boolean,
            PROCEDURE = public.int_eq_bool,
            COMMUTATOR = =
        );
    END IF;
END
$$;
SQL);

    // 3. Crear tablas del dominio y tablas auxiliares de Laravel
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS cache (
    key VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS cache_locks (
    key VARCHAR(255) PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS jobs (
    id BIGSERIAL PRIMARY KEY,
    queue VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    attempts SMALLINT NOT NULL,
    reserved_at INTEGER NULL,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS persona (
    id_persona SERIAL PRIMARY KEY,
    tipo_documento VARCHAR(20) NOT NULL,
    numero_documento VARCHAR(30) NOT NULL UNIQUE,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(120) NOT NULL,
    fecha_nacimiento DATE NULL,
    nacionalidad VARCHAR(60) NULL,
    genero VARCHAR(30) NULL,
    telefono VARCHAR(30) NULL,
    correo VARCHAR(120) NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS rol (
    id_rol SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS usuario (
    id_usuario SERIAL PRIMARY KEY,
    id_persona INTEGER NOT NULL REFERENCES persona(id_persona),
    id_rol INTEGER NOT NULL REFERENCES rol(id_rol),
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria BIGSERIAL PRIMARY KEY,
    id_usuario INTEGER NULL REFERENCES usuario(id_usuario),
    accion VARCHAR(80) NOT NULL,
    entidad VARCHAR(80) NOT NULL,
    id_registro BIGINT NULL,
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalle TEXT NULL
);

CREATE TABLE IF NOT EXISTS cliente (
    id_cliente SERIAL PRIMARY KEY,
    id_persona INTEGER NOT NULL UNIQUE REFERENCES persona(id_persona),
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tipo_cliente VARCHAR(30) NOT NULL DEFAULT 'DIRECTO',
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS destino (
    id_destino SERIAL PRIMARY KEY,
    nombre VARCHAR(180) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    ubicacion VARCHAR(255) NULL,
    altitud_msnm NUMERIC(8,2) NULL,
    como_llegar TEXT NULL,
    temporada_recomendada VARCHAR(180) NULL,
    recomendaciones TEXT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS servicio (
    id_servicio SERIAL PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS producto_turistico (
    id_producto SERIAL PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    nombre VARCHAR(180) NOT NULL,
    tipo_producto VARCHAR(40) NOT NULL DEFAULT 'TOUR_LOCAL',
    descripcion TEXT NULL,
    itinerario TEXT NULL,
    duracion_dias SMALLINT NOT NULL DEFAULT 1,
    precio_base NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    moneda CHAR(3) NOT NULL DEFAULT 'PEN',
    porcentaje_reserva NUMERIC(5,2) NOT NULL DEFAULT 50.00,
    minimo_pasajeros SMALLINT NOT NULL DEFAULT 1,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS producto_destino (
    id_producto_destino SERIAL PRIMARY KEY,
    id_producto INTEGER NOT NULL REFERENCES producto_turistico(id_producto) ON DELETE CASCADE,
    id_destino INTEGER NOT NULL REFERENCES destino(id_destino) ON DELETE CASCADE,
    orden_visita SMALLINT NULL DEFAULT 1,
    observacion VARCHAR(500) NULL
);

CREATE TABLE IF NOT EXISTS producto_servicio (
    id_producto_servicio SERIAL PRIMARY KEY,
    id_producto INTEGER NOT NULL REFERENCES producto_turistico(id_producto) ON DELETE CASCADE,
    id_servicio INTEGER NOT NULL REFERENCES servicio(id_servicio) ON DELETE CASCADE,
    incluido BOOLEAN NOT NULL DEFAULT TRUE,
    precio_adicional NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    observacion VARCHAR(500) NULL
);

CREATE TABLE IF NOT EXISTS salida (
    id_salida SERIAL PRIMARY KEY,
    id_producto INTEGER NOT NULL REFERENCES producto_turistico(id_producto),
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    capacidad SMALLINT NOT NULL DEFAULT 15,
    estado VARCHAR(30) NOT NULL DEFAULT 'PROGRAMADA',
    observacion TEXT NULL
);

CREATE TABLE IF NOT EXISTS cotizacion (
    id_cotizacion SERIAL PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    id_cliente INTEGER NOT NULL REFERENCES cliente(id_cliente),
    id_usuario_counter INTEGER NOT NULL REFERENCES usuario(id_usuario),
    fecha_cotizacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL DEFAULT 'VIGENTE',
    moneda CHAR(3) NOT NULL DEFAULT 'PEN',
    total NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    vigencia_hasta TIMESTAMP NULL,
    observacion TEXT NULL
);

CREATE TABLE IF NOT EXISTS detalle_cotizacion (
    id_detalle_cotizacion SERIAL PRIMARY KEY,
    id_cotizacion INTEGER NOT NULL REFERENCES cotizacion(id_cotizacion) ON DELETE CASCADE,
    id_producto INTEGER NOT NULL REFERENCES producto_turistico(id_producto),
    id_salida INTEGER NULL REFERENCES salida(id_salida),
    cantidad_pasajeros SMALLINT NOT NULL DEFAULT 1,
    precio_unitario NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    monto_adicional NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    subtotal NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    observacion VARCHAR(500) NULL
);

CREATE TABLE IF NOT EXISTS reserva (
    id_reserva SERIAL PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    id_cotizacion INTEGER NOT NULL REFERENCES cotizacion(id_cotizacion),
    id_usuario_counter INTEGER NOT NULL REFERENCES usuario(id_usuario),
    fecha_reserva TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL DEFAULT 'CONFIRMADA',
    moneda CHAR(3) NOT NULL DEFAULT 'PEN',
    total_contratado NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    fecha_limite_pago TIMESTAMP NULL,
    observacion TEXT NULL
);

CREATE TABLE IF NOT EXISTS detalle_reserva (
    id_detalle_reserva SERIAL PRIMARY KEY,
    id_reserva INTEGER NOT NULL REFERENCES reserva(id_reserva) ON DELETE CASCADE,
    id_producto INTEGER NOT NULL REFERENCES producto_turistico(id_producto),
    id_salida INTEGER NULL REFERENCES salida(id_salida),
    cantidad_pasajeros SMALLINT NOT NULL DEFAULT 1,
    precio_unitario NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    subtotal NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    punto_recojo VARCHAR(255) NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'ACTIVO',
    observacion VARCHAR(500) NULL
);

CREATE TABLE IF NOT EXISTS reserva_pasajero (
    id_reserva_pasajero SERIAL PRIMARY KEY,
    id_detalle_reserva INTEGER NOT NULL REFERENCES detalle_reserva(id_detalle_reserva) ON DELETE CASCADE,
    id_persona INTEGER NOT NULL REFERENCES persona(id_persona),
    restriccion_alimentaria VARCHAR(500) NULL,
    condicion_particular VARCHAR(500) NULL,
    observacion VARCHAR(500) NULL
);

CREATE TABLE IF NOT EXISTS metodo_pago (
    id_metodo_pago SERIAL PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE,
    requiere_validacion BOOLEAN NOT NULL DEFAULT FALSE,
    aplica_recargo BOOLEAN NOT NULL DEFAULT FALSE,
    porcentaje_recargo NUMERIC(5,2) NOT NULL DEFAULT 0.00,
    activo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS pago (
    id_pago SERIAL PRIMARY KEY,
    id_cotizacion INTEGER NOT NULL REFERENCES cotizacion(id_cotizacion),
    id_metodo_pago INTEGER NOT NULL REFERENCES metodo_pago(id_metodo_pago),
    id_usuario_registro INTEGER NOT NULL REFERENCES usuario(id_usuario),
    id_usuario_validacion INTEGER NULL REFERENCES usuario(id_usuario),
    fecha_pago TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    moneda CHAR(3) NOT NULL DEFAULT 'PEN',
    monto_base NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    recargo NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    monto_total_pagado NUMERIC(10,2) NOT NULL DEFAULT 0.00,
    estado VARCHAR(30) NOT NULL DEFAULT 'PENDIENTE_VALIDACION',
    numero_operacion VARCHAR(100) NULL,
    ruta_comprobante VARCHAR(500) NULL,
    fecha_validacion TIMESTAMP NULL,
    observacion VARCHAR(500) NULL
);

CREATE OR REPLACE VIEW vw_disponibilidad_salida AS
SELECT
    s.id_salida,
    s.id_producto,
    pt.codigo AS codigo_producto,
    pt.nombre AS producto,
    s.fecha,
    s.hora,
    s.capacidad,
    s.estado,
    COALESCE(ocup.cupos_ocupados, 0)::INTEGER AS cupos_ocupados,
    GREATEST(s.capacidad - COALESCE(ocup.cupos_ocupados, 0), 0)::INTEGER AS cupos_disponibles
FROM salida s
JOIN producto_turistico pt ON pt.id_producto = s.id_producto
LEFT JOIN (
    SELECT
        dr.id_salida,
        COUNT(rp.id_reserva_pasajero) AS cupos_ocupados
    FROM detalle_reserva dr
    JOIN reserva r ON r.id_reserva = dr.id_reserva
    JOIN reserva_pasajero rp ON rp.id_detalle_reserva = dr.id_detalle_reserva
    WHERE dr.estado <> 'CANCELADO'
      AND r.estado <> 'CANCELADA'
    GROUP BY dr.id_salida
) ocup ON ocup.id_salida = s.id_salida;

CREATE OR REPLACE VIEW vw_saldo_reserva AS
SELECT
    r.id_reserva,
    r.codigo AS codigo_reserva,
    r.id_cotizacion,
    r.total_contratado,
    COALESCE(val.monto_validado, 0.00)::NUMERIC(10,2) AS monto_validado,
    GREATEST(r.total_contratado - COALESCE(val.monto_validado, 0.00), 0.00)::NUMERIC(10,2) AS saldo_pendiente
FROM reserva r
LEFT JOIN (
    SELECT
        p.id_cotizacion,
        SUM(p.monto_base) AS monto_validado
    FROM pago p
    WHERE p.estado = 'VALIDADO'
    GROUP BY p.id_cotizacion
) val ON val.id_cotizacion = r.id_cotizacion;
SQL);

    // 4. Sembrar datos base (Roles, Métodos de Pago, Usuarios base) si no existen
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    $roles = [
        ['nombre' => 'GERENTE', 'descripcion' => 'Gerente General y Administrador del Sistema', 'activo' => true],
        ['nombre' => 'COUNTER', 'descripcion' => 'Asesor Comercial / Counter de Ventas', 'activo' => true],
        ['nombre' => 'CONTABILIDAD', 'descripcion' => 'Encargado de Finanzas y Validación de Pagos', 'activo' => true],
        ['nombre' => 'CLIENTE', 'descripcion' => 'Turista registrado en Portal de Autoconsulta', 'activo' => true],
    ];
    foreach ($roles as $r) {
        Illuminate\Support\Facades\DB::table('rol')->updateOrInsert(['nombre' => $r['nombre']], $r);
    }

    $metodosPago = [
        ['id_metodo_pago' => 1, 'nombre' => 'EFECTIVO', 'requiere_validacion' => false, 'aplica_recargo' => false, 'porcentaje_recargo' => 0.00, 'activo' => true],
        ['id_metodo_pago' => 2, 'nombre' => 'TRANSFERENCIA', 'requiere_validacion' => true, 'aplica_recargo' => false, 'porcentaje_recargo' => 0.00, 'activo' => true],
        ['id_metodo_pago' => 3, 'nombre' => 'DEPOSITO', 'requiere_validacion' => true, 'aplica_recargo' => false, 'porcentaje_recargo' => 0.00, 'activo' => true],
        ['id_metodo_pago' => 4, 'nombre' => 'YAPE', 'requiere_validacion' => true, 'aplica_recargo' => false, 'porcentaje_recargo' => 0.00, 'activo' => true],
        ['id_metodo_pago' => 5, 'nombre' => 'PLIN', 'requiere_validacion' => true, 'aplica_recargo' => false, 'porcentaje_recargo' => 0.00, 'activo' => true],
        ['id_metodo_pago' => 6, 'nombre' => 'TARJETA', 'requiere_validacion' => false, 'aplica_recargo' => true, 'porcentaje_recargo' => 5.00, 'activo' => true],
        ['id_metodo_pago' => 7, 'nombre' => 'LINK_DE_PAGO', 'requiere_validacion' => false, 'aplica_recargo' => false, 'porcentaje_recargo' => 0.00, 'activo' => true],
    ];
    foreach ($metodosPago as $mp) {
        Illuminate\Support\Facades\DB::table('metodo_pago')->updateOrInsert(['id_metodo_pago' => $mp['id_metodo_pago']], $mp);
    }
    $pdo->exec("SELECT setval('metodo_pago_id_metodo_pago_seq', (SELECT GREATEST(MAX(id_metodo_pago), 7) FROM metodo_pago));");

    $pwdService = new App\Modules\Seguridad\Services\PasswordService();
    $usuariosBase = [
        [
            'doc' => '00412589',
            'nombres' => 'Saúl',
            'apellidos' => 'Alvarado (Gerencia)',
            'correo' => 'gerencia@18experience.pe',
            'telefono' => '952100200',
            'rol' => 'GERENTE',
            'username' => 'admin',
            'pass' => 'Admin123*',
            'es_cliente' => false,
        ],
        [
            'doc' => '45896321',
            'nombres' => 'Lucía',
            'apellidos' => 'Mendoza (Counter)',
            'correo' => 'counter@18experience.pe',
            'telefono' => '952200300',
            'rol' => 'COUNTER',
            'username' => 'counter1',
            'pass' => 'Counter123*',
            'es_cliente' => false,
        ],
        [
            'doc' => '47851236',
            'nombres' => 'Jorge',
            'apellidos' => 'Quispe (Contabilidad)',
            'correo' => 'contabilidad@18experience.pe',
            'telefono' => '952300400',
            'rol' => 'CONTABILIDAD',
            'username' => 'contabilidad1',
            'pass' => 'Conta123*',
            'es_cliente' => false,
        ],
        [
            'doc' => '72345678',
            'nombres' => 'Mariana',
            'apellidos' => 'Cáceres (Turista)',
            'correo' => 'mariana.turista@gmail.com',
            'telefono' => '952400500',
            'rol' => 'CLIENTE',
            'username' => 'turista1',
            'pass' => 'Turista123*',
            'es_cliente' => true,
        ],
    ];

    foreach ($usuariosBase as $u) {
        Illuminate\Support\Facades\DB::table('persona')->updateOrInsert(
            ['numero_documento' => $u['doc']],
            [
                'tipo_documento' => 'DNI',
                'nombres' => $u['nombres'],
                'apellidos' => $u['apellidos'],
                'nacionalidad' => 'PERUANA',
                'telefono' => $u['telefono'],
                'correo' => $u['correo'],
                'activo' => true,
            ]
        );
        $idPersona = Illuminate\Support\Facades\DB::table('persona')->where('numero_documento', $u['doc'])->value('id_persona');
        $idRol = Illuminate\Support\Facades\DB::table('rol')->where('nombre', $u['rol'])->value('id_rol');

        $existeUsuario = Illuminate\Support\Facades\DB::table('usuario')->where('nombre_usuario', $u['username'])->exists();
        if (!$existeUsuario) {
            Illuminate\Support\Facades\DB::table('usuario')->insert([
                'id_persona' => $idPersona,
                'id_rol' => $idRol,
                'nombre_usuario' => $u['username'],
                'password_hash' => $pwdService->hash($u['pass']),
                'activo' => true,
                'fecha_creacion' => now(),
            ]);
        }

        if ($u['es_cliente']) {
            Illuminate\Support\Facades\DB::table('cliente')->updateOrInsert(
                ['id_persona' => $idPersona],
                [
                    'tipo_cliente' => 'DIRECTO',
                    'activo' => true,
                    'fecha_registro' => now(),
                ]
            );
        }
    }

    // Ejecutar DemoTacnaSeeder (Destinos, Tours y Salidas de Tacna)
    (new Database\Seeders\DemoTacnaSeeder())->run();

    echo "[OK] Esquema PostgreSQL y datos semilla verificados correctamente.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERROR] " . $e->getMessage() . "\n");
    exit(1);
}
