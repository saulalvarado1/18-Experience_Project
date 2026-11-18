# Requerimientos, reglas, roles, estados y casos de uso

> Numeración del SRS FD03 versión PHP. Estado verificado contra el código el 12/11/2026.
> Leyenda: ✅ completo · 🟡 parcial · ❌ pendiente. Al cambiar un estado, actualizar esta tabla en el mismo commit.

## Requerimientos funcionales (RF)

| RF | Requerimiento | CU | Estado |
| :--- | :--- | :--- | :---: |
| RF01 | Acceso al sistema por rol | CU01, CU03 | ✅ |
| RF02 | Gestión de usuarios | CU04, CU05 | ✅ |
| RF03 | Resumen operativo | CU02 | ✅ |
| RF04 | Gestión de clientes e historial | CU06, CU07 | ✅ |
| RF05 | Catálogo de destinos | CU08 | ✅ |
| RF06 | Tours y paquetes | CU09, CU10, CU11 | ✅ |
| RF07 | Salidas y cupos | CU12, CU13, CU14 | ✅ |
| RF08 | Gestión de cotizaciones | CU15, CU17 | 🟡 |
| RF09 | Documentos de cotización (PDF) | CU16 | ✅ |
| RF10 | Registro de pagos | CU18 | ✅ |
| RF11 | Control (validación) de pagos | CU19 | ✅ |
| RF12 | Registro de reservas con pasajeros | CU20 | ✅ |
| RF13 | Seguimiento de reservas | CU21, CU22, CU23 | ✅ |
| RF14 | Reportes de gestión | CU24 | ✅ |
| RF15 | Registro de operaciones (auditoría) | transversal | ✅ |

RNF01–RNF08 (compatibilidad, usabilidad, seguridad, integridad, control de cupos, documentos, trazabilidad,
mantenibilidad): cumplidos en diseño; falta prueba formal con los criterios de aceptación del SRS.

## Matriz de roles

| Módulo / acción | GERENTE | COUNTER | CONTABILIDAD | CLIENTE | Grupo de rutas |
| :--- | :---: | :---: | :---: | :---: | :--- |
| Inicio (dashboard) | ✔ | ✔ | ✔ | — | `auth` |
| Clientes, Destinos, Tours, Salidas | ✔ | ✔ | ✖ | ✖ | `rol:GERENTE,COUNTER` |
| Cambiar precio base de tour | ✔ | ✖ | ✖ | ✖ | grupo GERENTE,COUNTER + chequeo en controlador |
| Cotizaciones, Reservas | ✔ | ✔ | ✖ | ✖ | `rol:GERENTE,COUNTER` |
| Ver bandeja / registrar pagos | ✔ | ✔ | ✔ | ✖ | `rol:GERENTE,COUNTER,CONTABILIDAD` |
| Validar / rechazar / devolver pagos | ✔ | ✖ | ✔ | ✖ | `rol:GERENTE,CONTABILIDAD` |
| Reportes | ✔ | ✖ | ✔ | ✖ | `rol:GERENTE,CONTABILIDAD` |
| Usuarios y contraseñas | ✔ | ✖ | ✖ | ✖ | `rol:GERENTE` |
| Portal (cotizar, aceptar, pagar, boletos) | ✖ | ✖ | ✖ | ✔ | `rol:CLIENTE` *(fuera del SRS)* |

Contabilidad **no** registra clientes ni reservas; Counter **no** valida pagos (segregación de funciones).
Permisos siempre en el servidor (middleware), nunca solo ocultando botones (SAD).

## Reglas de negocio (RN01–RN17)

| RN | Regla (resumen del SRS) | Dónde se aplica | Estado |
| :--- | :--- | :--- | :---: |
| RN01 | Acceso solo a usuarios autorizados; funciones por rol; usuarios los administra Gerencia | middleware `rol`, `AuthService` | ✅ |
| RN02 | Cliente único por (tipo, número) de documento; directos y corporativos | `ClienteService`, `ValidadorPersonaService` | ✅ |
| RN03 | Destinos, tours y paquetes consistentes y sin duplicados | `CatalogoService` (registro, edición, composición y estado) | ✅ |
| RN04 | Solo Gerencia cambia precios, con motivo | `CatalogoService::cambiarPrecio` | ✅ |
| RN05 | Anticipo mínimo según `porcentaje_reserva`; tour local ≥ 50 % | `CatalogoService` (alta), `ReservaService` | ✅ |
| RN06 | Salida con servicio, fecha, hora y capacidad; sin duplicar producto+fecha+hora | `SalidaService::programarSalida` | ✅ |
| RN07 | Ocupados ≤ capacidad; capacidad no baja de los ocupados | `SalidaService`, `ReservaService` (`lockForUpdate`) | ✅ |
| RN08 | Solo se cancelan salidas sin cupos ocupados, con registro | `SalidaService::cancelarSalida` | ✅ |
| RN09 | Cotización con ≥ 1 servicio y montos consistentes; solo se aceptan vigentes con cupos | `CotizacionService::registrarDetalles` (mínimo de pasajeros, salida futura con cupos, misma moneda, sin repetir) | ✅ |
| RN10 | Pagos solo a cotización aceptada / reserva confirmada, sin superar saldo; recargo informado | `PagoService::registrar` | ✅ |
| RN11 | Pagos que requieren verificación llevan comprobante; Gerencia/Contabilidad validan o rechazan (motivo) | `PagoService::registrar` (operación y comprobante obligatorios) | ✅ |
| RN12 | Reserva solo con cotización aceptada, anticipo validado, cupos, pasajeros completos y sin duplicar | `ReservaService::verificarReservable` / `confirmarDesdeCotizacion` | ✅ |
| RN13 | Pasajeros identificados por servicio, sin duplicados y con datos para la operación | `ReservaService::resolverPasajeros` + `ClienteService::obtenerOCrearPersona` | ✅ |
| RN14 | Reserva confirmada se modifica según condiciones; al cancelar, motivo y liberar cupos | `ReservaService::modificar` / `cancelar` | ✅ |
| RN15 | Completar solo tras el servicio, sin saldo y sin salidas canceladas | `ReservaService::completar` | ✅ |
| RN16 | Auditar cotizaciones, pagos, reservas, precios y capacidades | `Auditoria::registrar()` | ✅ |
| RN17 | Reportes por períodos válidos distinguiendo pagos confirmados, saldos y reservas vigentes | `ReporteService` + `validarPeriodo` | ✅ |

Reglas propias de lo implementado fuera del SRS (pasarela): ver skill `ventas-transaccional` §3b.

## Máquinas de estado

```
cotizacion.estado:  VIGENTE ─► ACEPTADA ─► (reserva generada)
                       ├─► NO_ACEPTADA
                       ├─► CANCELADA
                       └─► SIN_DISPONIBILIDAD   (si la salida ya no tiene cupos)
pago.estado:        PENDIENTE_VALIDACION ─► VALIDADO | RECHAZADO
                    VALIDADO ─► DEVUELTO (devolución total por pasarela; parcial sigue VALIDADO)
reserva.estado:     CONFIRMADA ─► COMPLETADA (servicio realizado y saldo 0) | CANCELADA (libera cupos)
detalle_reserva:    ACTIVO ─► COMPLETADO | CANCELADO
salida.estado:      PROGRAMADA ⇄ COMPLETA (ocupados == capacidad) ─► FINALIZADA | CANCELADA
```

Los cupos ocupados se cuentan como pasajeros (`reserva_pasajero`) de `detalle_reserva` no
`CANCELADO` cuya `reserva` no esté `CANCELADA` — misma lógica que `vw_disponibilidad_salida`.

## Casos de uso (CU01–CU24)

| CU | Nombre | Actor | Estado | Pendiente |
| :--- | :--- | :--- | :---: | :--- |
| CU01 | Iniciar sesión (CAPTCHA) | Usuario | ✅ | |
| CU02 | Consultar panel de inicio | Usuario | ✅ | |
| CU03 | Cerrar sesión | Usuario | ✅ | |
| CU04 | Gestionar usuarios | Gerente | ✅ | buscar por texto/rol/estado, editar (`UsuarioService`), roles solo del personal |
| CU05 | Cambiar contraseña de usuario (≥ 8) | Gerente | ✅ | `UsuarioService::cambiarContrasena`, tests `CambiarContrasenaTest` |
| CU06 | Gestionar clientes | Counter | ✅ | |
| CU07 | Consultar historial del cliente | Counter | ✅ | |
| CU08 | Gestionar destinos | Counter | ✅ | |
| CU09 | Gestionar tours y paquetes | Counter | ✅ | editar, activar/desactivar, búsqueda sin mayúsculas e itinerario |
| CU10 | Asignar destinos y servicios al producto | Counter | ✅ | `/tours/{id}/composicion`, índices únicos, tests `ComposicionProductoTest` |
| CU11 | Cambiar precio del producto | Gerente | ✅ | |
| CU12 | Programar y editar salida | Counter | ✅ | `SalidaService::editarSalida` (`EDITAR_SALIDA`) |
| CU13 | Cambiar capacidad de salida | Counter | ✅ | |
| CU14 | Cancelar salida | Counter | ✅ | |
| CU15 | Registrar y editar cotización | Counter | 🟡 | editar ✅ · salida futura y con cupos ✅ · mínimo de pasajeros ✅ · **pendiente decidir**: salida obligatoria en tour local y formato de código |
| CU16 | Generar PDF de cotización | Counter | ✅ | |
| CU17 | Aceptar cotización | Counter | ✅ | |
| CU18 | Registrar pago | Counter | ✅ | n.º de operación y comprobante obligatorios si el método requiere validación |
| CU19 | Validar o rechazar pago | Contabilidad | ✅ | |
| CU20 | Generar reserva | Counter | ✅ | `/reservas/nueva?cotizacion=ID`, tests `GenerarReservaTest` |
| CU21 | Modificar reserva | Counter | ✅ | `ReservaService::modificar`, `/reservas/{id}/editar`, tests `ModificarReservaTest` |
| CU22 | Cancelar reserva | Counter | ✅ | |
| CU23 | Completar reserva | Counter | ✅ | valida salidas realizadas y no canceladas; servicios a COMPLETADO |
| CU24 | Consultar reportes y exportar PDF | Contabilidad | ✅ | pestañas Resumen/Reservas/Ventas/Saldos/Disponibilidad, período validado, PDF con barras |

## Códigos correlativos

SRS: `COT-AAAAMMDD-HHMMSS-XXXX` / `RES-…` (formato del prototipo, ej. `COT-20260927-195040-5D4B`).
Código actual: `COT-2026-0001` / `RES-2026-0001` (`CotizacionService::generarCodigo`). Columna `codigo` varchar(30), única.
