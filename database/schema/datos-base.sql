--
-- PostgreSQL database dump
--

\restrict BoZbgTroCapvRlJkv7q6YogLIRX7g5NhGRXrzameSCssXTyEVIcbQGiQHQ9RLEh

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

--
-- Data for Name: metodo_pago; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.metodo_pago VALUES (1, 'EFECTIVO', false, false, 0.00, true);
INSERT INTO public.metodo_pago VALUES (2, 'TRANSFERENCIA', true, false, 0.00, true);
INSERT INTO public.metodo_pago VALUES (3, 'DEPOSITO', true, false, 0.00, true);
INSERT INTO public.metodo_pago VALUES (4, 'YAPE', true, false, 0.00, true);
INSERT INTO public.metodo_pago VALUES (5, 'PLIN', true, false, 0.00, true);
INSERT INTO public.metodo_pago VALUES (6, 'TARJETA', false, true, 5.00, true);
INSERT INTO public.metodo_pago VALUES (7, 'LINK_DE_PAGO', false, false, 0.00, true);
INSERT INTO public.metodo_pago VALUES (8, 'PASARELA_ONLINE', false, false, 0.00, true);


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.rol VALUES (1, 'GERENTE', 'Administracion, supervision y autorizaciones', true);
INSERT INTO public.rol VALUES (2, 'COUNTER', 'Atencion, cotizaciones, ventas y reservas', true);
INSERT INTO public.rol VALUES (3, 'CONTABILIDAD', 'Validacion de pagos y consultas financieras', true);
INSERT INTO public.rol VALUES (4, 'CLIENTE', 'Turista o cliente con acceso al portal de autoconsulta', true);


--
-- Data for Name: servicio; Type: TABLE DATA; Schema: public; Owner: -
--

INSERT INTO public.servicio VALUES (1, 'TRANSPORTE', 'Servicio de movilidad o transporte', true);
INSERT INTO public.servicio VALUES (2, 'ALOJAMIENTO', 'Servicio de hospedaje', true);
INSERT INTO public.servicio VALUES (3, 'ALIMENTACION', 'Servicio de alimentacion', true);
INSERT INTO public.servicio VALUES (4, 'GUIA', 'Servicio de guiado turistico', true);
INSERT INTO public.servicio VALUES (5, 'ENTRADA', 'Entradas a atractivos o establecimientos', true);
INSERT INTO public.servicio VALUES (6, 'ACTIVIDAD', 'Actividad turistica o recreativa', true);
INSERT INTO public.servicio VALUES (7, 'TRASLADO', 'Traslado de pasajeros', true);
INSERT INTO public.servicio VALUES (8, 'OTRO', 'Otro servicio asociado al producto turistico', true);


--
-- Name: metodo_pago_id_metodo_pago_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.metodo_pago_id_metodo_pago_seq', 9, true);


--
-- Name: rol_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.rol_id_rol_seq', 4, true);


--
-- Name: servicio_id_servicio_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.servicio_id_servicio_seq', 8, true);


--
-- PostgreSQL database dump complete
--

\unrestrict BoZbgTroCapvRlJkv7q6YogLIRX7g5NhGRXrzameSCssXTyEVIcbQGiQHQ9RLEh

