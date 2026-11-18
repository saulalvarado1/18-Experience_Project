--
-- PostgreSQL database dump
--

\restrict vZ1Q5oFHRgawecsAJFUvt4MDZLmLanecztxHeW8NTAdq4O0qF5byQFCFzyn5RIQ

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ajuste_datos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ajuste_datos (
    nombre character varying(100) NOT NULL,
    aplicado_en timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    detalle text
);


--
-- Name: auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditoria (
    id_auditoria bigint NOT NULL,
    id_usuario integer,
    accion character varying(80) NOT NULL,
    entidad character varying(80) NOT NULL,
    id_registro bigint,
    fecha_hora timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    detalle text
);


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.auditoria_id_auditoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.auditoria_id_auditoria_seq OWNED BY public.auditoria.id_auditoria;


--
-- Name: cliente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cliente (
    id_cliente integer NOT NULL,
    id_persona integer NOT NULL,
    fecha_registro timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    tipo_cliente character varying(30) DEFAULT 'DIRECTO'::character varying NOT NULL,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: cliente_id_cliente_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cliente_id_cliente_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cliente_id_cliente_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cliente_id_cliente_seq OWNED BY public.cliente.id_cliente;


--
-- Name: cotizacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cotizacion (
    id_cotizacion integer NOT NULL,
    codigo character varying(30) NOT NULL,
    id_cliente integer NOT NULL,
    id_usuario_counter integer NOT NULL,
    fecha_cotizacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado character varying(30) DEFAULT 'VIGENTE'::character varying NOT NULL,
    moneda character(3) DEFAULT 'PEN'::bpchar NOT NULL,
    total numeric(10,2) DEFAULT 0.00 NOT NULL,
    vigencia_hasta timestamp without time zone,
    observacion text
);


--
-- Name: cotizacion_id_cotizacion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.cotizacion_id_cotizacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: cotizacion_id_cotizacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.cotizacion_id_cotizacion_seq OWNED BY public.cotizacion.id_cotizacion;


--
-- Name: destino; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.destino (
    id_destino integer NOT NULL,
    nombre character varying(120) NOT NULL,
    descripcion text,
    ubicacion character varying(255),
    altitud_msnm numeric(8,2),
    como_llegar text,
    temporada_recomendada character varying(100),
    recomendaciones text,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: destino_id_destino_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.destino_id_destino_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: destino_id_destino_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.destino_id_destino_seq OWNED BY public.destino.id_destino;


--
-- Name: detalle_cotizacion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detalle_cotizacion (
    id_detalle_cotizacion integer NOT NULL,
    id_cotizacion integer NOT NULL,
    id_producto integer NOT NULL,
    id_salida integer,
    cantidad_pasajeros smallint DEFAULT 1 NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    monto_adicional numeric(10,2) DEFAULT 0.00 NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    observacion character varying(500)
);


--
-- Name: detalle_cotizacion_id_detalle_cotizacion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.detalle_cotizacion_id_detalle_cotizacion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: detalle_cotizacion_id_detalle_cotizacion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.detalle_cotizacion_id_detalle_cotizacion_seq OWNED BY public.detalle_cotizacion.id_detalle_cotizacion;


--
-- Name: detalle_reserva; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.detalle_reserva (
    id_detalle_reserva integer NOT NULL,
    id_reserva integer NOT NULL,
    id_producto integer NOT NULL,
    id_salida integer,
    cantidad_pasajeros smallint NOT NULL,
    precio_unitario numeric(10,2) NOT NULL,
    subtotal numeric(10,2) NOT NULL,
    punto_recojo character varying(255),
    estado character varying(30) DEFAULT 'ACTIVO'::character varying NOT NULL,
    observacion character varying(500)
);


--
-- Name: detalle_reserva_id_detalle_reserva_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.detalle_reserva_id_detalle_reserva_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: detalle_reserva_id_detalle_reserva_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.detalle_reserva_id_detalle_reserva_seq OWNED BY public.detalle_reserva.id_detalle_reserva;


--
-- Name: devolucion_pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.devolucion_pago (
    id_devolucion integer NOT NULL,
    id_pago integer NOT NULL,
    monto numeric(10,2) NOT NULL,
    monto_base numeric(10,2) NOT NULL,
    numero_operacion character varying(100) NOT NULL,
    motivo character varying(500) NOT NULL,
    id_usuario integer NOT NULL,
    fecha timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT devolucion_pago_monto_base_check CHECK ((monto_base >= (0)::numeric)),
    CONSTRAINT devolucion_pago_monto_check CHECK ((monto > (0)::numeric))
);


--
-- Name: devolucion_pago_id_devolucion_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.devolucion_pago_id_devolucion_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: devolucion_pago_id_devolucion_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.devolucion_pago_id_devolucion_seq OWNED BY public.devolucion_pago.id_devolucion;


--
-- Name: metodo_pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.metodo_pago (
    id_metodo_pago integer NOT NULL,
    nombre character varying(80) NOT NULL,
    requiere_validacion boolean DEFAULT false NOT NULL,
    aplica_recargo boolean DEFAULT false NOT NULL,
    porcentaje_recargo numeric(5,2) DEFAULT 0.00 NOT NULL,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: metodo_pago_id_metodo_pago_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.metodo_pago_id_metodo_pago_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: metodo_pago_id_metodo_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.metodo_pago_id_metodo_pago_seq OWNED BY public.metodo_pago.id_metodo_pago;


--
-- Name: pago; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pago (
    id_pago integer NOT NULL,
    id_cotizacion integer NOT NULL,
    id_metodo_pago integer NOT NULL,
    id_usuario_registro integer NOT NULL,
    id_usuario_validacion integer,
    fecha_pago timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    moneda character(3) DEFAULT 'PEN'::bpchar NOT NULL,
    monto_base numeric(10,2) NOT NULL,
    recargo numeric(10,2) DEFAULT 0.00 NOT NULL,
    monto_total_pagado numeric(10,2) NOT NULL,
    estado character varying(30) DEFAULT 'PENDIENTE_VALIDACION'::character varying NOT NULL,
    numero_operacion character varying(100),
    ruta_comprobante character varying(500),
    fecha_validacion timestamp without time zone,
    observacion character varying(500),
    monto_devuelto numeric(10,2) DEFAULT 0.00 NOT NULL,
    monto_base_devuelto numeric(10,2) DEFAULT 0.00 NOT NULL
);


--
-- Name: pago_id_pago_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pago_id_pago_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pago_id_pago_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pago_id_pago_seq OWNED BY public.pago.id_pago;


--
-- Name: persona; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.persona (
    id_persona integer NOT NULL,
    tipo_documento character varying(20) NOT NULL,
    numero_documento character varying(30) NOT NULL,
    nombres character varying(100) NOT NULL,
    apellidos character varying(120) NOT NULL,
    fecha_nacimiento date,
    nacionalidad character varying(60),
    genero character varying(30),
    telefono character varying(30),
    correo character varying(120),
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: persona_id_persona_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.persona_id_persona_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: persona_id_persona_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.persona_id_persona_seq OWNED BY public.persona.id_persona;


--
-- Name: producto_destino; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_destino (
    id_producto_destino integer NOT NULL,
    id_producto integer NOT NULL,
    id_destino integer NOT NULL,
    orden_visita smallint,
    observacion character varying(500)
);


--
-- Name: producto_destino_id_producto_destino_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.producto_destino_id_producto_destino_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: producto_destino_id_producto_destino_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.producto_destino_id_producto_destino_seq OWNED BY public.producto_destino.id_producto_destino;


--
-- Name: producto_servicio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_servicio (
    id_producto_servicio integer NOT NULL,
    id_producto integer NOT NULL,
    id_servicio integer NOT NULL,
    incluido boolean DEFAULT true NOT NULL,
    precio_adicional numeric(10,2) DEFAULT 0.00 NOT NULL,
    observacion character varying(500)
);


--
-- Name: producto_servicio_id_producto_servicio_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.producto_servicio_id_producto_servicio_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: producto_servicio_id_producto_servicio_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.producto_servicio_id_producto_servicio_seq OWNED BY public.producto_servicio.id_producto_servicio;


--
-- Name: producto_turistico; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.producto_turistico (
    id_producto integer NOT NULL,
    codigo character varying(30) NOT NULL,
    nombre character varying(150) NOT NULL,
    tipo_producto character varying(50) NOT NULL,
    descripcion text,
    itinerario text,
    duracion_dias smallint DEFAULT 1 NOT NULL,
    precio_base numeric(10,2) NOT NULL,
    moneda character(3) DEFAULT 'PEN'::bpchar NOT NULL,
    porcentaje_reserva numeric(5,2) DEFAULT 50.00 NOT NULL,
    minimo_pasajeros smallint DEFAULT 1 NOT NULL,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: producto_turistico_id_producto_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.producto_turistico_id_producto_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: producto_turistico_id_producto_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.producto_turistico_id_producto_seq OWNED BY public.producto_turistico.id_producto;


--
-- Name: reserva; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reserva (
    id_reserva integer NOT NULL,
    codigo character varying(30) NOT NULL,
    id_cotizacion integer NOT NULL,
    id_usuario_counter integer NOT NULL,
    fecha_reserva timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    estado character varying(30) DEFAULT 'CONFIRMADA'::character varying NOT NULL,
    moneda character(3) DEFAULT 'PEN'::bpchar NOT NULL,
    total_contratado numeric(10,2) NOT NULL,
    fecha_limite_pago timestamp without time zone,
    observacion text
);


--
-- Name: reserva_id_reserva_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reserva_id_reserva_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reserva_id_reserva_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reserva_id_reserva_seq OWNED BY public.reserva.id_reserva;


--
-- Name: reserva_pasajero; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reserva_pasajero (
    id_reserva_pasajero integer NOT NULL,
    id_detalle_reserva integer NOT NULL,
    id_persona integer NOT NULL,
    restriccion_alimentaria character varying(500),
    condicion_particular character varying(500),
    observacion character varying(500)
);


--
-- Name: reserva_pasajero_id_reserva_pasajero_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reserva_pasajero_id_reserva_pasajero_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reserva_pasajero_id_reserva_pasajero_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reserva_pasajero_id_reserva_pasajero_seq OWNED BY public.reserva_pasajero.id_reserva_pasajero;


--
-- Name: rol; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rol (
    id_rol integer NOT NULL,
    nombre character varying(50) NOT NULL,
    descripcion character varying(255),
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: rol_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.rol_id_rol_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rol_id_rol_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.rol_id_rol_seq OWNED BY public.rol.id_rol;


--
-- Name: salida; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.salida (
    id_salida integer NOT NULL,
    id_producto integer NOT NULL,
    fecha date NOT NULL,
    hora time without time zone NOT NULL,
    capacidad smallint NOT NULL,
    estado character varying(30) DEFAULT 'PROGRAMADA'::character varying NOT NULL,
    observacion character varying(500)
);


--
-- Name: salida_id_salida_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.salida_id_salida_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: salida_id_salida_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.salida_id_salida_seq OWNED BY public.salida.id_salida;


--
-- Name: servicio; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.servicio (
    id_servicio integer NOT NULL,
    nombre character varying(100) NOT NULL,
    descripcion text,
    activo boolean DEFAULT true NOT NULL
);


--
-- Name: servicio_id_servicio_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.servicio_id_servicio_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: servicio_id_servicio_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.servicio_id_servicio_seq OWNED BY public.servicio.id_servicio;


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario (
    id_usuario integer NOT NULL,
    id_persona integer NOT NULL,
    id_rol integer NOT NULL,
    nombre_usuario character varying(50) NOT NULL,
    password_hash character varying(255) NOT NULL,
    activo boolean DEFAULT true NOT NULL,
    fecha_creacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ultimo_acceso timestamp without time zone
);


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.usuario_id_usuario_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.usuario_id_usuario_seq OWNED BY public.usuario.id_usuario;


--
-- Name: vw_disponibilidad_salida; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_disponibilidad_salida AS
 SELECT s.id_salida,
    s.id_producto,
    pt.codigo AS codigo_producto,
    pt.nombre AS producto,
    s.fecha,
    s.hora,
    s.capacidad,
    s.estado,
    (COALESCE(count(rp.id_reserva_pasajero), (0)::bigint))::integer AS cupos_ocupados,
    (GREATEST((0)::bigint, (s.capacidad - COALESCE(count(rp.id_reserva_pasajero), (0)::bigint))))::integer AS cupos_disponibles
   FROM ((((public.salida s
     JOIN public.producto_turistico pt ON ((pt.id_producto = s.id_producto)))
     LEFT JOIN public.detalle_reserva dr ON (((dr.id_salida = s.id_salida) AND ((dr.estado)::text <> 'CANCELADO'::text))))
     LEFT JOIN public.reserva r ON (((r.id_reserva = dr.id_reserva) AND ((r.estado)::text <> 'CANCELADA'::text))))
     LEFT JOIN public.reserva_pasajero rp ON ((rp.id_detalle_reserva = dr.id_detalle_reserva)))
  GROUP BY s.id_salida, s.id_producto, pt.codigo, pt.nombre, s.fecha, s.hora, s.capacidad, s.estado;


--
-- Name: vw_saldo_reserva; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vw_saldo_reserva AS
 SELECT r.id_reserva,
    r.codigo AS codigo_reserva,
    r.id_cotizacion,
    r.total_contratado,
    (COALESCE(sum(
        CASE
            WHEN ((p.estado)::text = 'VALIDADO'::text) THEN (p.monto_base - p.monto_base_devuelto)
            ELSE (0)::numeric
        END), (0)::numeric))::numeric(10,2) AS monto_validado,
    (GREATEST((0)::numeric, (r.total_contratado - COALESCE(sum(
        CASE
            WHEN ((p.estado)::text = 'VALIDADO'::text) THEN (p.monto_base - p.monto_base_devuelto)
            ELSE (0)::numeric
        END), (0)::numeric))))::numeric(10,2) AS saldo_pendiente
   FROM (public.reserva r
     LEFT JOIN public.pago p ON ((p.id_cotizacion = r.id_cotizacion)))
  GROUP BY r.id_reserva, r.codigo, r.id_cotizacion, r.total_contratado;


--
-- Name: auditoria id_auditoria; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria ALTER COLUMN id_auditoria SET DEFAULT nextval('public.auditoria_id_auditoria_seq'::regclass);


--
-- Name: cliente id_cliente; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cliente ALTER COLUMN id_cliente SET DEFAULT nextval('public.cliente_id_cliente_seq'::regclass);


--
-- Name: cotizacion id_cotizacion; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizacion ALTER COLUMN id_cotizacion SET DEFAULT nextval('public.cotizacion_id_cotizacion_seq'::regclass);


--
-- Name: destino id_destino; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.destino ALTER COLUMN id_destino SET DEFAULT nextval('public.destino_id_destino_seq'::regclass);


--
-- Name: detalle_cotizacion id_detalle_cotizacion; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_cotizacion ALTER COLUMN id_detalle_cotizacion SET DEFAULT nextval('public.detalle_cotizacion_id_detalle_cotizacion_seq'::regclass);


--
-- Name: detalle_reserva id_detalle_reserva; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_reserva ALTER COLUMN id_detalle_reserva SET DEFAULT nextval('public.detalle_reserva_id_detalle_reserva_seq'::regclass);


--
-- Name: devolucion_pago id_devolucion; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.devolucion_pago ALTER COLUMN id_devolucion SET DEFAULT nextval('public.devolucion_pago_id_devolucion_seq'::regclass);


--
-- Name: metodo_pago id_metodo_pago; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodo_pago ALTER COLUMN id_metodo_pago SET DEFAULT nextval('public.metodo_pago_id_metodo_pago_seq'::regclass);


--
-- Name: pago id_pago; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago ALTER COLUMN id_pago SET DEFAULT nextval('public.pago_id_pago_seq'::regclass);


--
-- Name: persona id_persona; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.persona ALTER COLUMN id_persona SET DEFAULT nextval('public.persona_id_persona_seq'::regclass);


--
-- Name: producto_destino id_producto_destino; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_destino ALTER COLUMN id_producto_destino SET DEFAULT nextval('public.producto_destino_id_producto_destino_seq'::regclass);


--
-- Name: producto_servicio id_producto_servicio; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_servicio ALTER COLUMN id_producto_servicio SET DEFAULT nextval('public.producto_servicio_id_producto_servicio_seq'::regclass);


--
-- Name: producto_turistico id_producto; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_turistico ALTER COLUMN id_producto SET DEFAULT nextval('public.producto_turistico_id_producto_seq'::regclass);


--
-- Name: reserva id_reserva; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva ALTER COLUMN id_reserva SET DEFAULT nextval('public.reserva_id_reserva_seq'::regclass);


--
-- Name: reserva_pasajero id_reserva_pasajero; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_pasajero ALTER COLUMN id_reserva_pasajero SET DEFAULT nextval('public.reserva_pasajero_id_reserva_pasajero_seq'::regclass);


--
-- Name: rol id_rol; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol ALTER COLUMN id_rol SET DEFAULT nextval('public.rol_id_rol_seq'::regclass);


--
-- Name: salida id_salida; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salida ALTER COLUMN id_salida SET DEFAULT nextval('public.salida_id_salida_seq'::regclass);


--
-- Name: servicio id_servicio; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.servicio ALTER COLUMN id_servicio SET DEFAULT nextval('public.servicio_id_servicio_seq'::regclass);


--
-- Name: usuario id_usuario; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario ALTER COLUMN id_usuario SET DEFAULT nextval('public.usuario_id_usuario_seq'::regclass);


--
-- Name: ajuste_datos ajuste_datos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ajuste_datos
    ADD CONSTRAINT ajuste_datos_pkey PRIMARY KEY (nombre);


--
-- Name: auditoria auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT auditoria_pkey PRIMARY KEY (id_auditoria);


--
-- Name: cliente cliente_id_persona_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cliente
    ADD CONSTRAINT cliente_id_persona_key UNIQUE (id_persona);


--
-- Name: cliente cliente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cliente
    ADD CONSTRAINT cliente_pkey PRIMARY KEY (id_cliente);


--
-- Name: cotizacion cotizacion_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizacion
    ADD CONSTRAINT cotizacion_codigo_key UNIQUE (codigo);


--
-- Name: cotizacion cotizacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizacion
    ADD CONSTRAINT cotizacion_pkey PRIMARY KEY (id_cotizacion);


--
-- Name: destino destino_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.destino
    ADD CONSTRAINT destino_nombre_key UNIQUE (nombre);


--
-- Name: destino destino_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.destino
    ADD CONSTRAINT destino_pkey PRIMARY KEY (id_destino);


--
-- Name: detalle_cotizacion detalle_cotizacion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_cotizacion
    ADD CONSTRAINT detalle_cotizacion_pkey PRIMARY KEY (id_detalle_cotizacion);


--
-- Name: detalle_reserva detalle_reserva_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_reserva
    ADD CONSTRAINT detalle_reserva_pkey PRIMARY KEY (id_detalle_reserva);


--
-- Name: devolucion_pago devolucion_pago_numero_operacion_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.devolucion_pago
    ADD CONSTRAINT devolucion_pago_numero_operacion_key UNIQUE (numero_operacion);


--
-- Name: devolucion_pago devolucion_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.devolucion_pago
    ADD CONSTRAINT devolucion_pago_pkey PRIMARY KEY (id_devolucion);


--
-- Name: metodo_pago metodo_pago_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodo_pago
    ADD CONSTRAINT metodo_pago_nombre_key UNIQUE (nombre);


--
-- Name: metodo_pago metodo_pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.metodo_pago
    ADD CONSTRAINT metodo_pago_pkey PRIMARY KEY (id_metodo_pago);


--
-- Name: pago pago_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_pkey PRIMARY KEY (id_pago);


--
-- Name: persona persona_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.persona
    ADD CONSTRAINT persona_pkey PRIMARY KEY (id_persona);


--
-- Name: producto_destino producto_destino_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_destino
    ADD CONSTRAINT producto_destino_pkey PRIMARY KEY (id_producto_destino);


--
-- Name: producto_servicio producto_servicio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_servicio
    ADD CONSTRAINT producto_servicio_pkey PRIMARY KEY (id_producto_servicio);


--
-- Name: producto_turistico producto_turistico_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_turistico
    ADD CONSTRAINT producto_turistico_codigo_key UNIQUE (codigo);


--
-- Name: producto_turistico producto_turistico_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_turistico
    ADD CONSTRAINT producto_turistico_pkey PRIMARY KEY (id_producto);


--
-- Name: reserva reserva_codigo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_codigo_key UNIQUE (codigo);


--
-- Name: reserva reserva_id_cotizacion_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_id_cotizacion_key UNIQUE (id_cotizacion);


--
-- Name: reserva_pasajero reserva_pasajero_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_pasajero
    ADD CONSTRAINT reserva_pasajero_pkey PRIMARY KEY (id_reserva_pasajero);


--
-- Name: reserva reserva_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_pkey PRIMARY KEY (id_reserva);


--
-- Name: rol rol_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_nombre_key UNIQUE (nombre);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id_rol);


--
-- Name: salida salida_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salida
    ADD CONSTRAINT salida_pkey PRIMARY KEY (id_salida);


--
-- Name: servicio servicio_nombre_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.servicio
    ADD CONSTRAINT servicio_nombre_key UNIQUE (nombre);


--
-- Name: servicio servicio_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.servicio
    ADD CONSTRAINT servicio_pkey PRIMARY KEY (id_servicio);


--
-- Name: persona uq_persona_doc; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.persona
    ADD CONSTRAINT uq_persona_doc UNIQUE (tipo_documento, numero_documento);


--
-- Name: usuario usuario_nombre_usuario_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_nombre_usuario_key UNIQUE (nombre_usuario);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_usuario);


--
-- Name: idx_devolucion_pago_pago; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_devolucion_pago_pago ON public.devolucion_pago USING btree (id_pago);


--
-- Name: uq_pago_operacion_pasarela; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_pago_operacion_pasarela ON public.pago USING btree (numero_operacion) WHERE ((id_metodo_pago = 8) AND (numero_operacion IS NOT NULL));


--
-- Name: uq_producto_destino; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_producto_destino ON public.producto_destino USING btree (id_producto, id_destino);


--
-- Name: uq_producto_servicio; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX uq_producto_servicio ON public.producto_servicio USING btree (id_producto, id_servicio);


--
-- Name: auditoria auditoria_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT auditoria_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);


--
-- Name: cliente cliente_id_persona_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cliente
    ADD CONSTRAINT cliente_id_persona_fkey FOREIGN KEY (id_persona) REFERENCES public.persona(id_persona);


--
-- Name: cotizacion cotizacion_id_cliente_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizacion
    ADD CONSTRAINT cotizacion_id_cliente_fkey FOREIGN KEY (id_cliente) REFERENCES public.cliente(id_cliente);


--
-- Name: cotizacion cotizacion_id_usuario_counter_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cotizacion
    ADD CONSTRAINT cotizacion_id_usuario_counter_fkey FOREIGN KEY (id_usuario_counter) REFERENCES public.usuario(id_usuario);


--
-- Name: detalle_cotizacion detalle_cotizacion_id_cotizacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_cotizacion
    ADD CONSTRAINT detalle_cotizacion_id_cotizacion_fkey FOREIGN KEY (id_cotizacion) REFERENCES public.cotizacion(id_cotizacion) ON DELETE CASCADE;


--
-- Name: detalle_cotizacion detalle_cotizacion_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_cotizacion
    ADD CONSTRAINT detalle_cotizacion_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.producto_turistico(id_producto);


--
-- Name: detalle_cotizacion detalle_cotizacion_id_salida_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_cotizacion
    ADD CONSTRAINT detalle_cotizacion_id_salida_fkey FOREIGN KEY (id_salida) REFERENCES public.salida(id_salida);


--
-- Name: detalle_reserva detalle_reserva_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_reserva
    ADD CONSTRAINT detalle_reserva_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.producto_turistico(id_producto);


--
-- Name: detalle_reserva detalle_reserva_id_reserva_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_reserva
    ADD CONSTRAINT detalle_reserva_id_reserva_fkey FOREIGN KEY (id_reserva) REFERENCES public.reserva(id_reserva) ON DELETE CASCADE;


--
-- Name: detalle_reserva detalle_reserva_id_salida_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.detalle_reserva
    ADD CONSTRAINT detalle_reserva_id_salida_fkey FOREIGN KEY (id_salida) REFERENCES public.salida(id_salida);


--
-- Name: devolucion_pago devolucion_pago_id_pago_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.devolucion_pago
    ADD CONSTRAINT devolucion_pago_id_pago_fkey FOREIGN KEY (id_pago) REFERENCES public.pago(id_pago);


--
-- Name: devolucion_pago devolucion_pago_id_usuario_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.devolucion_pago
    ADD CONSTRAINT devolucion_pago_id_usuario_fkey FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario);


--
-- Name: pago pago_id_cotizacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_id_cotizacion_fkey FOREIGN KEY (id_cotizacion) REFERENCES public.cotizacion(id_cotizacion);


--
-- Name: pago pago_id_metodo_pago_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_id_metodo_pago_fkey FOREIGN KEY (id_metodo_pago) REFERENCES public.metodo_pago(id_metodo_pago);


--
-- Name: pago pago_id_usuario_registro_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_id_usuario_registro_fkey FOREIGN KEY (id_usuario_registro) REFERENCES public.usuario(id_usuario);


--
-- Name: pago pago_id_usuario_validacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pago
    ADD CONSTRAINT pago_id_usuario_validacion_fkey FOREIGN KEY (id_usuario_validacion) REFERENCES public.usuario(id_usuario);


--
-- Name: producto_destino producto_destino_id_destino_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_destino
    ADD CONSTRAINT producto_destino_id_destino_fkey FOREIGN KEY (id_destino) REFERENCES public.destino(id_destino);


--
-- Name: producto_destino producto_destino_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_destino
    ADD CONSTRAINT producto_destino_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.producto_turistico(id_producto);


--
-- Name: producto_servicio producto_servicio_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_servicio
    ADD CONSTRAINT producto_servicio_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.producto_turistico(id_producto);


--
-- Name: producto_servicio producto_servicio_id_servicio_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.producto_servicio
    ADD CONSTRAINT producto_servicio_id_servicio_fkey FOREIGN KEY (id_servicio) REFERENCES public.servicio(id_servicio);


--
-- Name: reserva reserva_id_cotizacion_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_id_cotizacion_fkey FOREIGN KEY (id_cotizacion) REFERENCES public.cotizacion(id_cotizacion);


--
-- Name: reserva reserva_id_usuario_counter_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva
    ADD CONSTRAINT reserva_id_usuario_counter_fkey FOREIGN KEY (id_usuario_counter) REFERENCES public.usuario(id_usuario);


--
-- Name: reserva_pasajero reserva_pasajero_id_detalle_reserva_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_pasajero
    ADD CONSTRAINT reserva_pasajero_id_detalle_reserva_fkey FOREIGN KEY (id_detalle_reserva) REFERENCES public.detalle_reserva(id_detalle_reserva) ON DELETE CASCADE;


--
-- Name: reserva_pasajero reserva_pasajero_id_persona_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reserva_pasajero
    ADD CONSTRAINT reserva_pasajero_id_persona_fkey FOREIGN KEY (id_persona) REFERENCES public.persona(id_persona);


--
-- Name: salida salida_id_producto_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.salida
    ADD CONSTRAINT salida_id_producto_fkey FOREIGN KEY (id_producto) REFERENCES public.producto_turistico(id_producto);


--
-- Name: usuario usuario_id_persona_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_id_persona_fkey FOREIGN KEY (id_persona) REFERENCES public.persona(id_persona);


--
-- Name: usuario usuario_id_rol_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_id_rol_fkey FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol);


--
-- PostgreSQL database dump complete
--

\unrestrict vZ1Q5oFHRgawecsAJFUvt4MDZLmLanecztxHeW8NTAdq4O0qF5byQFCFzyn5RIQ

