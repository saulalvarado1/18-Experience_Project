--
-- PostgreSQL database dump
--


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
-- Data for Name: ajuste_datos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.ajuste_datos (nombre, aplicado_en, detalle) FROM stdin;
zona_horaria_utc_a_lima	2026-11-12 10:41:32.054137	cotizacion.vigencia_hasta=4; reserva.fecha_limite_pago=1; cotizacion.fecha_cotizacion=4; reserva.fecha_reserva=1; pago.fecha_pago=1; pago.fecha_validacion=1; devolucion_pago.fecha=0; cliente.fecha_registro=1; usuario.fecha_creacion=2; usuario.ultimo_acceso=3; auditoria.fecha_hora=10; 
\.


--
-- Data for Name: auditoria; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.auditoria (id_auditoria, id_usuario, accion, entidad, id_registro, fecha_hora, detalle) FROM stdin;
1	1	CAMBIO_PRECIO	producto_turistico	1	2026-09-26 21:50:11	Precio anterior: 120.00 | Nuevo precio: 135.00 | Motivo: Actualización de tarifa para la nueva temporada.
2	1	CAMBIO_CAPACIDAD	salida	1	2026-09-27 13:34:59	Capacidad anterior: 17 | Nueva capacidad: 20 | Motivo: Se contratará una movilidad externa de mayor capacidad.
3	1	CAMBIO_CAPACIDAD	salida	1	2026-09-27 13:35:44	Capacidad anterior: 20 | Nueva capacidad: 17 | Motivo: Se utilizará la movilidad propia de la agencia.
4	1	CANCELAR_SALIDA	salida	2	2026-09-27 17:02:44	Estado anterior: PROGRAMADA | Nuevo estado: CANCELADA
5	1	CREAR_COTIZACION	cotizacion	1	2026-09-27 19:50:41	Código: COT-20260927-195040-5D4B | Cliente ID: 1 | Total: PEN 280.00
6	2	EDITAR_COTIZACION	cotizacion	1	2026-09-27 20:16:40	Código: COT-20260927-195040-5D4B | Nuevo total: PEN 280.00
7	2	EDITAR_COTIZACION	cotizacion	1	2026-09-27 20:17:51	Código: COT-20260927-195040-5D4B | Nuevo total: PEN 290.00
8	1	ACEPTAR_COTIZACION	cotizacion	1	2026-09-27 20:26:33	Cotización COT-20260927-195040-5D4B aceptada por el cliente.
9	1	REGISTRAR_PAGO	pago	1	2026-09-27 21:50:20	Cotización ID: 1 | Método: TRANSFERENCIA | Monto base: 145.00 | Recargo: 0.00 | Estado: PENDIENTE_VALIDACION
10	1	VALIDAR_PAGO	pago	1	2026-09-27 22:08:09	Nuevo estado: VALIDADO | Observación: Comprobante verificado correctamente.
11	1	GENERAR_RESERVA	reserva	1	2026-09-27 23:21:28	Código: RES-20260927-232127-C143 | Cotización ID: 1 | Total: PEN 290.00
12	2	EDITAR_RESERVA	reserva	1	2026-09-28 12:38:14	Reserva RES-20260927-232127-C143 modificada.
13	2	EDITAR_RESERVA	reserva	1	2026-09-28 12:39:34	Reserva RES-20260927-232127-C143 modificada.
14	2	CANCELAR_RESERVA	reserva	1	2026-09-28 12:47:22	Reserva RES-20260927-232127-C143 cancelada. Motivo: Cliente solicita cancelar la reserva de prueba.
15	1	CAMBIO_CAPACIDAD	salida	1	2026-09-28 15:14:55	Capacidad anterior: 17 | Nueva capacidad: 18 | Motivo: CAPICIDAD SUPERADA, SE CONTRATARÁ UN MOVIL CON MAYOR CAPACIDAD
16	1	CREAR_COTIZACION	cotizacion	2	2026-09-28 15:17:11	Código: COT-20260928-151711-EB7A | Cliente ID: 1 | Total: PEN 290.00
17	1	ACEPTAR_COTIZACION	cotizacion	2	2026-09-28 15:18:13	Cotización COT-20260928-151711-EB7A aceptada por el cliente.
18	1	REGISTRAR_PAGO	pago	2	2026-09-28 15:18:49	Cotización ID: 2 | Método: EFECTIVO | Monto base: 145.00 | Recargo: 0.00 | Estado: VALIDADO
19	1	GENERAR_RESERVA	reserva	2	2026-09-28 15:20:46	Código: RES-20260928-152046-10C3 | Cotización ID: 2 | Total: PEN 290.00
20	1	CAMBIO_CAPACIDAD	salida	1	2026-09-28 15:23:40	Capacidad anterior: 18 | Nueva capacidad: 17 | Motivo: PRUEBA TERMINADA, VOLVEMOS A LA CAPACIDAD DEL MOVIL DE LA AGENCIA
21	1	REGISTRAR_PAGO	pago	3	2026-09-28 15:25:17	Cotización ID: 2 | Método: EFECTIVO | Monto base: 145.00 | Recargo: 0.00 | Estado: VALIDADO
22	1	COMPLETAR_RESERVA	reserva	2	2026-09-28 15:45:49	Reserva RES-20260928-152046-10C3 completada.
23	1	CREAR_COTIZACION	cotizacion	3	2026-09-28 18:42:44	Código: COT-20260928-184243-697A | Cliente ID: 3 | Total: PEN 280.00
24	1	ACEPTAR_COTIZACION	cotizacion	3	2026-09-28 18:46:12	Cotización COT-20260928-184243-697A aceptada por el cliente.
25	1	REGISTRAR_PAGO	pago	4	2026-09-28 18:50:12	Cotización ID: 3 | Método: EFECTIVO | Monto base: 140.00 | Recargo: 0.00 | Estado: VALIDADO
26	1	CREAR_COTIZACION	cotizacion	4	2026-09-28 19:33:02	Código: COT-20260928-193302-04DA | Cliente ID: 1 | Total: PEN 270.00
27	1	ACEPTAR_COTIZACION	cotizacion	4	2026-09-28 19:35:38	Cotización COT-20260928-193302-04DA aceptada por el cliente.
28	1	REGISTRAR_PAGO	pago	5	2026-09-28 19:36:22	Cotización ID: 4 | Método: EFECTIVO | Monto base: 135.00 | Recargo: 0.00 | Estado: VALIDADO
29	1	GENERAR_RESERVA	reserva	3	2026-09-28 19:39:06	Código: RES-20260928-193905-B979 | Cotización ID: 4 | Total: PEN 270.00
30	1	REGISTRAR_PAGO	pago	6	2026-09-28 19:40:29	Cotización ID: 4 | Método: EFECTIVO | Monto base: 135.00 | Recargo: 0.00 | Estado: VALIDADO
31	1	CREAR_COTIZACION	cotizacion	5	2026-09-28 19:43:37	Código: COT-20260928-194337-5BE1 | Cliente ID: 1 | Total: PEN 270.00
32	1	ACEPTAR_COTIZACION	cotizacion	5	2026-09-28 19:44:00	Cotización COT-20260928-194337-5BE1 aceptada por el cliente.
33	1	REGISTRAR_PAGO	pago	7	2026-09-28 19:44:14	Cotización ID: 5 | Método: EFECTIVO | Monto base: 270.00 | Recargo: 0.00 | Estado: VALIDADO
34	1	GENERAR_RESERVA	reserva	4	2026-09-28 19:45:15	Código: RES-20260928-194515-621F | Cotización ID: 5 | Total: PEN 270.00
35	1	COMPLETAR_RESERVA	reserva	4	2026-09-28 19:45:37	Reserva RES-20260928-194515-621F completada.
36	2	CREAR_COTIZACION	cotizacion	6	2026-09-28 23:08:13	Código: COT-20260928-230813-66B9 | Cliente ID: 5 | Total: PEN 270.00
37	2	ACEPTAR_COTIZACION	cotizacion	6	2026-09-28 23:09:15	Cotización COT-20260928-230813-66B9 aceptada por el cliente.
38	2	REGISTRAR_PAGO	pago	8	2026-09-28 23:20:31	Cotización ID: 6 | Método: TARJETA | Monto base: 135.00 | Recargo: 6.75 | Estado: VALIDADO
39	2	REGISTRAR_PAGO	pago	9	2026-09-28 23:23:18	Cotización ID: 6 | Método: TRANSFERENCIA | Monto base: 20.00 | Recargo: 0.00 | Estado: PENDIENTE_VALIDACION
40	2	CREAR_COTIZACION	cotizacion	7	2026-09-29 10:50:23	Código: COT-20260929-105023-1BE0 | Cliente ID: 6 | Total: PEN 270.00
41	2	ACEPTAR_COTIZACION	cotizacion	7	2026-09-29 14:05:52	Cotización COT-20260929-105023-1BE0 aceptada por el cliente.
42	2	REGISTRAR_PAGO	pago	10	2026-09-29 14:07:02	Cotización ID: 7 | Método: TRANSFERENCIA | Monto base: 135.00 | Recargo: 0.00 | Estado: PENDIENTE_VALIDACION
43	3	VALIDAR_PAGO	pago	10	2026-09-29 14:14:11	Nuevo estado: VALIDADO
44	2	GENERAR_RESERVA	reserva	5	2026-09-29 14:28:35	Código: RES-20260929-142835-B887 | Cotización ID: 7 | Total: PEN 270.00
45	2	REGISTRAR_PAGO	pago	11	2026-09-29 14:30:37	Cotización ID: 7 | Método: EFECTIVO | Monto base: 135.00 | Recargo: 0.00 | Estado: VALIDADO
5467	1	EDITAR_SALIDA	salida	100	2026-11-16 17:02:06	Salida editada: 2026-11-17 07:30 → 2026-11-17 07:31
5470	1	CREAR_COTIZACION	cotizacion	1714	2026-11-16 17:10:11	Cotización COT-2026-0007 generada para cliente Raúl Francisco Miranda Platas. Total: S/ 90.00
134	41	CREAR_COTIZACION	cotizacion	32	2026-10-28 14:44:21	Cotización COT-2026-0001 generada para cliente Saul Urbano. Total: S/ 135.00
5670	1	CREAR_COTIZACION	cotizacion	1782	2026-11-16 17:28:34	Cotización COT-2026-0009 generada para cliente Saul Urbano. Total: S/ 90.00
135	1	ACEPTAR_COTIZACION	cotizacion	32	2026-10-28 14:45:43	Cotización COT-2026-0001 cambió de VIGENTE a ACEPTADA.
136	41	REGISTRAR_PAGO	pago	24	2026-10-28 14:46:46	Pago de S/ 130 (+recargo S/ 0) con EFECTIVO para cotización COT-2026-0001. Estado: VALIDADO
5468	1	EDITAR_SALIDA	salida	100	2026-11-16 17:02:06	Salida editada: 2026-11-17 07:31 → 2026-11-17 07:31
5471	1	CREAR_COTIZACION	cotizacion	1715	2026-11-16 17:10:39	Cotización COT-2026-0008 generada para cliente Saul Urbano. Total: S/ 90.00
5281	195	REGISTRAR_PAGO	pago	1036	2026-11-12 13:07:15	Pago de S/ 45 (+recargo S/ 0) con PASARELA_ONLINE para cotización COT-2026-0004. Estado: VALIDADO
159	41	CREAR_COTIZACION	cotizacion	39	2026-10-28 14:50:20	Cotización COT-2026-0002 generada para cliente Saul Urbano. Total: S/ 90.00
160	1	ACEPTAR_COTIZACION	cotizacion	39	2026-10-28 14:50:31	Cotización COT-2026-0002 cambió de VIGENTE a ACEPTADA.
161	1	GENERAR_RESERVA	reserva	11	2026-10-28 14:51:02	Reserva RES-2026-0001 confirmada para cotización COT-2026-0001. Total: S/ 135.00.
162	41	CREAR_COTIZACION	cotizacion	40	2026-10-28 14:52:35	Cotización COT-2026-0003 generada para cliente Saul Urbano. Total: S/ 90.00
163	41	ACEPTAR_COTIZACION	cotizacion	40	2026-10-28 14:52:39	Cotización COT-2026-0003 cambió de VIGENTE a ACEPTADA.
164	41	CREAR_COTIZACION	cotizacion	41	2026-10-28 14:52:48	Cotización COT-2026-0004 generada para cliente Saul Urbano. Total: S/ 90.00
165	41	ACEPTAR_COTIZACION	cotizacion	41	2026-10-28 15:00:31	Cotización COT-2026-0004 cambió de VIGENTE a ACEPTADA.
5469	41	CREAR_COTIZACION	cotizacion	1713	2026-11-16 17:07:37	Cotización COT-2026-0006 generada para cliente Saul Urbano. Total: S/ 135.00
5280	195	CREAR_COTIZACION	cotizacion	1653	2026-11-12 13:06:55	Cotización COT-2026-0005 generada para cliente Saul Urbano. Total: S/ 270.00
\.


--
-- Data for Name: cliente; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cliente (id_cliente, id_persona, fecha_registro, tipo_cliente, activo) FROM stdin;
1	4	2026-09-26 14:37:32	CORPORATIVO	t
2	5	2026-09-26 15:02:58	DIRECTO	t
3	6	2026-09-28 18:32:04	DIRECTO	t
4	7	2026-09-28 20:01:00	DIRECTO	t
5	9	2026-09-28 23:07:05	DIRECTO	t
6	10	2026-09-29 10:48:15	DIRECTO	t
15	32	2026-10-28 14:37:43	DIRECTO	t
\.


--
-- Data for Name: cotizacion; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cotizacion (id_cotizacion, codigo, id_cliente, id_usuario_counter, fecha_cotizacion, estado, moneda, total, vigencia_hasta, observacion) FROM stdin;
1	COT-20260927-195040-5D4B	1	1	2026-09-27 19:50:40	ACEPTADA	PEN	290.00	\N	Cotización de prueba para tour local. Actualizada por el Counter.
2	COT-20260928-151711-EB7A	1	1	2026-09-28 15:17:11	ACEPTADA	PEN	290.00	\N	\N
3	COT-20260928-184243-697A	3	1	2026-09-28 18:42:43	ACEPTADA	PEN	280.00	\N	\N
4	COT-20260928-193302-04DA	1	1	2026-09-28 19:33:02	ACEPTADA	PEN	270.00	\N	\N
5	COT-20260928-194337-5BE1	1	1	2026-09-28 19:43:37	ACEPTADA	PEN	270.00	\N	\N
6	COT-20260928-230813-66B9	5	2	2026-09-28 23:08:13	ACEPTADA	PEN	270.00	\N	Prueba integral del flujo real de venta y reserva.
7	COT-20260929-105023-1BE0	6	2	2026-09-29 10:50:23	ACEPTADA	PEN	270.00	\N	Prueba integral V2 - flujo real de venta.
1713	COT-2026-0006	15	41	2026-11-16 17:07:37	VIGENTE	PEN	135.00	2026-11-23 17:07:37	Solicitada en línea por el cliente. No
1714	COT-2026-0007	2	1	2026-11-16 17:10:11	VIGENTE	PEN	90.00	2026-11-23 00:00:00	\N
1715	COT-2026-0008	15	1	2026-11-16 17:10:39	VIGENTE	PEN	90.00	2026-11-23 00:00:00	\N
39	COT-2026-0002	15	41	2026-10-28 14:50:20	ACEPTADA	PEN	90.00	2026-11-04 14:50:20	Solicitada en línea por el cliente. 
40	COT-2026-0003	15	41	2026-10-28 14:52:35	ACEPTADA	PEN	90.00	2026-11-04 14:52:35	Solicitada en línea por el cliente.  | Aceptada por el cliente desde el Portal Web.
41	COT-2026-0004	15	41	2026-10-28 14:52:48	ACEPTADA	PEN	90.00	2026-11-04 14:52:48	Solicitada en línea por el cliente.  | Aceptada por el cliente desde el Portal Web.
32	COT-2026-0001	15	41	2026-10-28 14:44:21	ACEPTADA	PEN	135.00	2026-11-04 14:44:21	Solicitada en línea por el cliente. 
1782	COT-2026-0009	15	1	2026-11-16 17:28:34	VIGENTE	PEN	90.00	2026-11-23 00:00:00	\N
1653	COT-2026-0005	15	195	2026-11-12 13:06:55	VIGENTE	PEN	270.00	2026-11-19 13:06:55	Solicitada en línea por el cliente. 
\.


--
-- Data for Name: destino; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.destino (id_destino, nombre, descripcion, ubicacion, altitud_msnm, como_llegar, temporada_recomendada, recomendaciones, activo) FROM stdin;
1	Valle de los Geiseres	Zona natural con actividad geotérmica.	Candarave - Tacna	4320.00	Acceso terrestre desde la ciudad de Tacna.	Mayo a noviembre	Llevar ropa abrigadora, agua, protección solar y aclimatarse previamente.	t
3	Petroglifos de Miculla y Pachía	Complejo arqueológico de petroglifos milenarios sobre roca volcánica en el desierto tacneño y puente colgante.	Distrito de Pachía, a 22 km de Tacna	1200.00	Por carretera asfaltada Tacna - Collpa - La Paz en 30 minutos.	Todo el año, preferente mañana	Llevar sombrero, bloqueador y zapatillas cómodas.	t
4	Campo de la Alianza y Museo Histórico	Monumento histórico nacional y museo de sitio en honor a los defensores de la Batalla del Alto de la Alianza (1880).	Meseta del cerro Intiorko, Tacna	750.00	A 15 minutos en bus turístico desde el Centro Cívico.	Todo el año	Respetar el monumento y las áreas ceremoniales.	t
5	Ruta del Pisco y Bodegas de Pocollay	Tradicional circuito enoturístico por los valles de Pocollay y Magollo para degustación de vinos y pisco de uva negra criolla.	Valle de Pocollay, Tacna	670.00	A 10 minutos de la Plaza de Armas de Tacna.	Marzo a Diciembre (Vendimia en Marzo)	Degustación responsable de piscos y macerados de damasco.	t
6	Mirador y Andenería de Tarata	Pintoresca provincia andina con impresionante andenería preincaica, iglesias coloniales y baños termales medicinales de Tikaco.	Provincia de Tarata, región Tacna	3070.00	Carretera Tacna - Tarata en 1 hora y 45 minutos.	Abril a Noviembre	Ropa abrigadora para la tarde y pastillas para el soroche.	t
\.


--
-- Data for Name: detalle_cotizacion; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.detalle_cotizacion (id_detalle_cotizacion, id_cotizacion, id_producto, id_salida, cantidad_pasajeros, precio_unitario, monto_adicional, subtotal, observacion) FROM stdin;
3	1	1	1	2	135.00	20.00	290.00	\N
4	2	1	1	2	135.00	20.00	290.00	\N
5	3	1	1	2	135.00	10.00	280.00	\N
6	4	1	1	2	135.00	0.00	270.00	\N
7	5	1	3	2	135.00	0.00	270.00	\N
8	6	1	1	2	135.00	0.00	270.00	\N
9	7	1	1	2	135.00	0.00	270.00	\N
1646	1653	1	\N	2	135.00	0.00	270.00	\N
1704	1713	2	81	3	45.00	0.00	135.00	\N
1705	1714	2	81	2	45.00	0.00	90.00	\N
1706	1715	2	82	2	45.00	0.00	90.00	\N
1771	1782	2	82	2	45.00	0.00	90.00	\N
34	32	1	\N	1	135.00	0.00	135.00	\N
41	39	2	\N	2	45.00	0.00	90.00	\N
42	40	2	\N	2	45.00	0.00	90.00	\N
43	41	2	\N	2	45.00	0.00	90.00	\N
\.


--
-- Data for Name: detalle_reserva; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.detalle_reserva (id_detalle_reserva, id_reserva, id_producto, id_salida, cantidad_pasajeros, precio_unitario, subtotal, punto_recojo, estado, observacion) FROM stdin;
1	1	1	1	2	135.00	290.00	Hotel DM Tacna	CANCELADO	\N
2	2	1	1	2	135.00	290.00	Hotel DM Tacna	COMPLETADO	\N
3	3	1	1	2	135.00	270.00	Hotel DM Tacna	ACTIVO	\N
4	4	1	3	2	135.00	270.00	Hotel DM Tacna	COMPLETADO	\N
5	5	1	1	2	135.00	270.00	Hotel Casa Andina Tacna	ACTIVO	\N
11	11	1	\N	1	135.00	135.00	Agencia 18 Grados Sur, Tacna	ACTIVO	\N
\.


--
-- Data for Name: devolucion_pago; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.devolucion_pago (id_devolucion, id_pago, monto, monto_base, numero_operacion, motivo, id_usuario, fecha) FROM stdin;
\.


--
-- Data for Name: metodo_pago; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.metodo_pago (id_metodo_pago, nombre, requiere_validacion, aplica_recargo, porcentaje_recargo, activo) FROM stdin;
1	EFECTIVO	f	f	0.00	t
2	TRANSFERENCIA	t	f	0.00	t
3	DEPOSITO	t	f	0.00	t
4	YAPE	t	f	0.00	t
5	PLIN	t	f	0.00	t
6	TARJETA	f	t	5.00	t
7	LINK_DE_PAGO	f	f	0.00	t
8	PASARELA_ONLINE	f	f	0.00	t
\.


--
-- Data for Name: pago; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.pago (id_pago, id_cotizacion, id_metodo_pago, id_usuario_registro, id_usuario_validacion, fecha_pago, moneda, monto_base, recargo, monto_total_pagado, estado, numero_operacion, ruta_comprobante, fecha_validacion, observacion, monto_devuelto, monto_base_devuelto) FROM stdin;
1	1	2	1	1	2026-09-27 21:50:20	PEN	145.00	0.00	145.00	VALIDADO	TRX-PRUEBA-001	C:\\Users\\Raul\\Downloads\\WhatsApp Image 2026-09-25 at 9.13.58 PM.jpeg	2026-09-27 22:08:09	Adelanto del 50% para el tour local. | [VALIDADO] Comprobante verificado correctamente.	0.00	0.00
2	2	1	1	1	2026-09-28 15:18:49	PEN	145.00	0.00	145.00	VALIDADO	\N	\N	2026-09-28 15:18:49	\N	0.00	0.00
3	2	1	1	1	2026-09-28 15:25:17	PEN	145.00	0.00	145.00	VALIDADO	\N	\N	2026-09-28 15:25:17	\N	0.00	0.00
4	3	1	1	1	2026-09-28 18:50:12	PEN	140.00	0.00	140.00	VALIDADO	\N	\N	2026-09-28 18:50:12	PAGO DEL 50 %	0.00	0.00
5	4	1	1	1	2026-09-28 19:36:22	PEN	135.00	0.00	135.00	VALIDADO	\N	\N	2026-09-28 19:36:22	Adelanto 50% - prueba Paso 67	0.00	0.00
6	4	1	1	1	2026-09-28 19:40:29	PEN	135.00	0.00	135.00	VALIDADO	\N	\N	2026-09-28 19:40:29	Pago de saldo - prueba Paso 67	0.00	0.00
7	5	1	1	1	2026-09-28 19:44:14	PEN	270.00	0.00	270.00	VALIDADO	\N	\N	2026-09-28 19:44:14	Pago completo - prueba completar reserva	0.00	0.00
8	6	6	2	2	2026-09-28 23:20:31	PEN	135.00	6.75	141.75	VALIDADO	TRX-PRUEBA-001	\N	2026-09-28 23:20:31	\N	0.00	0.00
9	6	2	2	\N	2026-09-28 23:23:18	PEN	20.00	0.00	20.00	PENDIENTE_VALIDACION	RX-PRUEBA-002	C:\\Users\\Raul\\Downloads\\VOUCHER_PRUEBA.jpeg	\N	Adelanto para prueba integral.	0.00	0.00
10	7	2	2	3	2026-09-29 14:07:02	PEN	135.00	0.00	135.00	VALIDADO	INT-V2-001	C:\\Users\\Raul\\Downloads\\VOUCHER_PRUEBA.jpeg	2026-09-29 14:14:11	Adelanto 50% - prueba integral V2	0.00	0.00
11	7	1	2	2	2026-09-29 14:30:37	PEN	135.00	0.00	135.00	VALIDADO	\N	\N	2026-09-29 14:30:37	Pago de saldo final - prueba integral V2	0.00	0.00
24	32	1	41	41	2026-10-28 14:46:46	PEN	130.00	0.00	130.00	VALIDADO	12313	comprobantes/eoR2KyqsyHEqfsq5qdkH9zgQO18GswlcOWXjySEj.pdf	2026-10-28 14:46:46	SA	0.00	0.00
1036	41	8	195	195	2026-11-12 13:07:15	PEN	45.00	0.00	45.00	VALIDADO	chr_test_85030a984057c2	\N	2026-11-12 13:07:15	Pago online confirmado por la pasarela culqi.	0.00	0.00
\.


--
-- Data for Name: persona; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.persona (id_persona, tipo_documento, numero_documento, nombres, apellidos, fecha_nacimiento, nacionalidad, genero, telefono, correo, activo) FROM stdin;
2	DNI	00000001	Carlos	Mendoza Torres	\N	\N	\N	\N	\N	t
3	DNI	00000002	Ana María	Flores Quispe	\N	\N	\N	999222333	ana.counter@test.com	t
4	DNI	00000003	Lucia Andrea	Ramos Vega	1995-05-15	Peruana	Femenino	999555666	lucia.cliente@test.com	t
5	DNI	75154758	Raúl Francisco	Miranda Platas	2005-03-19	Peruana	Masculino	983047097	raulfranciscomirandaplatas@gmail.com	t
6	DNI	78541236	Pedro Luis	Quispe Vargas	1998-06-15	Peruana	Masculino	987654321	correo@gmail.com	t
7	DNI	74859621	Daniela Sofia	Mamani Flores	1996-08-12	Peruana	Femenino	987654321	daniela.prueba@correo.com	t
8	DNI	79990001	Mariana Elena	Salazar Quispe	\N	\N	\N	987600101	contabilidad.prueba@18gradossur.test	t
9	DNI	79991001	Valeria Nicole	Torres Mamani	1997-03-14	Peruana	Femenino	987600201	valeria.prueba@correo.test	t
10	DNI	79992001	Andrea Carolina	Vargas Choque	1998-04-18	Peruana	Femenino	987620001	andrea.integral@correo.test	t
11	DNI	79992002	Diego Alonso	Mamani Flores	1900-09-09	Peruana	Masculino	987620002	\N	t
32	DNI	71214741	Saul	Urbano	\N	\N	\N	900605124	sa2023078332@virtual.upt.pe	t
\.


--
-- Data for Name: producto_destino; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.producto_destino (id_producto_destino, id_producto, id_destino, orden_visita, observacion) FROM stdin;
2	1	1	1	Destino principal del recorrido.
3	2	4	1	Destino principal del itinerario
4	3	3	1	Destino principal del itinerario
5	4	5	1	Destino principal del itinerario
6	5	6	1	Destino principal del itinerario
\.


--
-- Data for Name: producto_servicio; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.producto_servicio (id_producto_servicio, id_producto, id_servicio, incluido, precio_adicional, observacion) FROM stdin;
1	1	1	t	0.00	Movilidad turística incluida.
2	1	3	f	25.00	Almuerzo opcional.
\.


--
-- Data for Name: producto_turistico; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.producto_turistico (id_producto, codigo, nombre, tipo_producto, descripcion, itinerario, duracion_dias, precio_base, moneda, porcentaje_reserva, minimo_pasajeros, activo) FROM stdin;
1	TL-GEISER-001	Tour Valle de los Geiseres - Candarave	TOUR_LOCAL	Tour turístico al Valle de los Geiseres de Candarave.	Salida desde Tacna, recorrido turístico por Candarave y visita al Valle de los Geiseres.	1	135.00	PEN	50.00	2	t
2	TL-CITY-001	City Tour Tacna Histórica y Campo de la Alianza	TOUR_LOCAL	Recorrido guiado por el Centro Cívico, Catedral de Tacna, Pila Ornamental francesa, Arco Parabólico y Museo del Alto de la Alianza.	09:00 Partida desde Calle San Martín | 09:30 Centro Cívico | 11:00 Campo de la Alianza | 13:00 Retorno al centro.	1	45.00	PEN	50.00	2	t
3	TL-VALLE-002	Tour Valle Viejo, Miculla y Puente Colgante	TOUR_LOCAL	Visita a los petroglifos de Miculla, cruce de puentes colgantes, degustación de pastelería tradicional en Calana y gastronomía típica en Pachía.	14:00 Partida | 14:45 Complejo Miculla y caminata por puente | 16:30 Visita a bodega en Calana | 18:00 Retorno.	1	60.00	PEN	50.00	2	t
4	TL-PISCO-003	Ruta Enológica del Pisco y Bodegas Tacneñas	TOUR_LOCAL	Circuito exclusivo para conocer el proceso de elaboración del Pisco tacneño con cata guiada de uvas pisqueras y macerados de damasco.	10:00 Salida | 10:30 Bodega Santa Elena | 12:00 Bodega Cuneo | 13:30 Almuerzo campestre | 15:30 Retorno.	1	75.00	PEN	50.00	2	t
5	TL-TARATA-004	Excursión Andina Tarata y Aguas Termales de Tikaco	TOUR_LOCAL	Aventura andina por los miradores de la cordillera, andenería preincaica de Tarata y baños termales naturales medicinales.	07:30 Partida | 09:30 Mirador Apacheta | 11:00 Tarata y templo San Benedicto | 12:30 Termas Tikaco | 17:00 Retorno a Tacna.	1	110.00	PEN	50.00	3	t
\.


--
-- Data for Name: reserva; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reserva (id_reserva, codigo, id_cotizacion, id_usuario_counter, fecha_reserva, estado, moneda, total_contratado, fecha_limite_pago, observacion) FROM stdin;
1	RES-20260927-232127-C143	1	1	2026-09-27 23:21:27	CANCELADA	PEN	290.00	\N	Reserva de prueba del tour local. | [CANCELACIÓN] Cliente solicita cancelar la reserva de prueba.
2	RES-20260928-152046-10C3	2	1	2026-09-28 15:20:46	COMPLETADA	PEN	290.00	\N	\N
3	RES-20260928-193905-B979	4	1	2026-09-28 19:39:05	CONFIRMADA	PEN	270.00	\N	\N
4	RES-20260928-194515-621F	5	1	2026-09-28 19:45:15	COMPLETADA	PEN	270.00	\N	\N
5	RES-20260929-142835-B887	7	2	2026-09-29 14:28:35	CONFIRMADA	PEN	270.00	\N	\N
11	RES-2026-0001	32	1	2026-10-28 14:51:02	CONFIRMADA	PEN	135.00	2026-10-31 14:51:02	\N
\.


--
-- Data for Name: reserva_pasajero; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reserva_pasajero (id_reserva_pasajero, id_detalle_reserva, id_persona, restriccion_alimentaria, condicion_particular, observacion) FROM stdin;
5	1	3	\N	\N	\N
6	1	4	Sin lácteos	Ninguna	\N
7	2	4	\N	\N	\N
8	2	5	\N	\N	\N
9	3	4	\N	\N	\N
10	3	3	\N	\N	\N
11	4	4	\N	\N	\N
12	4	3	\N	\N	\N
13	5	10	\N	\N	\N
14	5	11	\N	\N	\N
20	11	32	\N	\N	\N
\.


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.rol (id_rol, nombre, descripcion, activo) FROM stdin;
1	GERENTE	Administracion, supervision y autorizaciones	t
2	COUNTER	Atencion, cotizaciones, ventas y reservas	t
3	CONTABILIDAD	Validacion de pagos y consultas financieras	t
4	CLIENTE	Turista o cliente con acceso al portal de autoconsulta	t
\.


--
-- Data for Name: salida; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.salida (id_salida, id_producto, fecha, hora, capacidad, estado, observacion) FROM stdin;
1	1	2026-10-16	08:00:00	17	PROGRAMADA	Salida regular desde Tacna. Horario actualizado.
2	1	2026-10-17	08:00:00	17	CANCELADA	Salida temporal para prueba de cancelación.
3	1	2026-09-28	08:00:00	17	PROGRAMADA	Salida de prueba para completar reserva
77	2	2026-11-12	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
5	2	2026-10-28	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
6	2	2026-10-29	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
7	2	2026-10-30	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
8	2	2026-10-31	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
9	2	2026-11-01	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
10	2	2026-11-02	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
11	3	2026-10-28	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
12	3	2026-10-29	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
13	3	2026-10-30	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
14	3	2026-10-31	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
15	3	2026-11-01	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
16	3	2026-11-02	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
17	4	2026-10-28	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
18	4	2026-10-29	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
19	4	2026-10-30	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
20	4	2026-10-31	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
21	4	2026-11-01	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
22	4	2026-11-02	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
23	5	2026-10-28	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
24	5	2026-10-29	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
25	5	2026-10-30	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
26	5	2026-10-31	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
27	5	2026-11-01	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
28	5	2026-11-02	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
78	2	2026-11-13	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
79	2	2026-11-14	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
80	2	2026-11-15	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
81	2	2026-11-16	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
82	2	2026-11-17	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
83	3	2026-11-12	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
84	3	2026-11-13	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
85	3	2026-11-14	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
86	3	2026-11-15	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
87	3	2026-11-16	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
88	3	2026-11-17	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
89	4	2026-11-12	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
90	4	2026-11-13	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
91	4	2026-11-14	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
92	4	2026-11-15	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
93	4	2026-11-16	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
94	4	2026-11-17	09:00:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
95	5	2026-11-12	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
96	5	2026-11-13	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
97	5	2026-11-14	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
98	5	2026-11-15	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
99	5	2026-11-16	07:30:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
100	5	2026-11-17	07:31:00	15	PROGRAMADA	Salida garantizada con movilidad turística climatizada.
\.


--
-- Data for Name: servicio; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.servicio (id_servicio, nombre, descripcion, activo) FROM stdin;
1	TRANSPORTE	Servicio de movilidad o transporte	t
2	ALOJAMIENTO	Servicio de hospedaje	t
3	ALIMENTACION	Servicio de alimentacion	t
4	GUIA	Servicio de guiado turistico	t
5	ENTRADA	Entradas a atractivos o establecimientos	t
6	ACTIVIDAD	Actividad turistica o recreativa	t
7	TRASLADO	Traslado de pasajeros	t
8	OTRO	Otro servicio asociado al producto turistico	t
\.


--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.usuario (id_usuario, id_persona, id_rol, nombre_usuario, password_hash, activo, fecha_creacion, ultimo_acceso) FROM stdin;
195	32	4	71214741	$2y$12$cjxPqDWGL7gz5x0n4wX.Y.dw8.RGXnr6RWMQSya78ZwKVIlGoRxFm	t	2026-11-12 12:35:08	\N
24	5	4	raul.cliente	$2y$12$RUimdFsR3svCH6GBx2axM.IL40S2zRN3nmRZLLhXhjgcRyHUGtLRm	t	2026-10-26 12:07:59	\N
3	8	3	contabilidad1	$2y$12$nBgoVWzVIDSxkec940w8UupYbtd0nsvDWlYS0N.Tp.4WuqXLdpF2O	t	2026-09-28 23:02:38	2026-10-28 15:20:43
2	3	2	counter1	$2y$12$Igi5YuNM4PFmo.QR3t7MDu52gxL9xdWw4ocfWA3gw9xancdb/CQXu	t	2026-09-26 00:08:33	2026-10-28 16:29:42
1	2	1	admin	$2y$12$m/mdcwdrXwCmCWYPtwts4.BogoJvTaLPRvaUpW8RgJrNpSEEjMpaC	t	2026-09-25 22:49:24	2026-11-16 16:18:05
41	32	4	sa2023078332@virtual.upt.pe	$2y$12$CENBpGmoJoFjjBxtP4bB3OzVW42eZ8uzzel1QKjhR63LbxQOjDLGO	t	2026-10-28 14:37:44	2026-11-18 12:06:58
\.


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.auditoria_id_auditoria_seq', 5670, true);


--
-- Name: cliente_id_cliente_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cliente_id_cliente_seq', 104, true);


--
-- Name: cotizacion_id_cotizacion_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cotizacion_id_cotizacion_seq', 1782, true);


--
-- Name: destino_id_destino_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.destino_id_destino_seq', 51, true);


--
-- Name: detalle_cotizacion_id_detalle_cotizacion_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.detalle_cotizacion_id_detalle_cotizacion_seq', 1771, true);


--
-- Name: detalle_reserva_id_detalle_reserva_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.detalle_reserva_id_detalle_reserva_seq', 320, true);


--
-- Name: devolucion_pago_id_devolucion_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.devolucion_pago_id_devolucion_seq', 155, true);


--
-- Name: metodo_pago_id_metodo_pago_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.metodo_pago_id_metodo_pago_seq', 9, true);


--
-- Name: pago_id_pago_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pago_id_pago_seq', 1113, true);


--
-- Name: persona_id_persona_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.persona_id_persona_seq', 700, true);


--
-- Name: producto_destino_id_producto_destino_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.producto_destino_id_producto_destino_seq', 69, true);


--
-- Name: producto_servicio_id_producto_servicio_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.producto_servicio_id_producto_servicio_seq', 44, true);


--
-- Name: producto_turistico_id_producto_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.producto_turistico_id_producto_seq', 89, true);


--
-- Name: reserva_id_reserva_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reserva_id_reserva_seq', 320, true);


--
-- Name: reserva_pasajero_id_reserva_pasajero_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reserva_pasajero_id_reserva_pasajero_seq', 536, true);


--
-- Name: rol_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.rol_id_rol_seq', 4, true);


--
-- Name: salida_id_salida_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.salida_id_salida_seq', 108, true);


--
-- Name: servicio_id_servicio_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.servicio_id_servicio_seq', 8, true);


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.usuario_id_usuario_seq', 214, true);


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

\unrestrict SRRpIlNTWXP9sPvnmREzwsg0UByMUAjdnOFaSL5Df68AMYBVAOpUBdy99gD6jSV

