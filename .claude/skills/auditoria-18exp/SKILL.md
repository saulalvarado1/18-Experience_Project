---
name: auditoria-18exp
description: Ejecuta una auditoría integral del proyecto 18 Experience Web: estado del entorno (PHP/PostgreSQL), inventario de módulos y rutas, cobertura de RF01-RF15, casos de uso CU01-CU24 y reglas RN01-RN17 del SRS FD03 versión PHP, pruebas PHPUnit y estado de Git. Usar cuando el usuario pida revisar el avance, cuántos requerimientos están hechos, hacer una auditoría o verificar qué falta implementar.
---

# Auditoría Integral — 18 Experience Web

Skill para diagnosticar de forma rápida y estructurada el estado técnico y funcional del proyecto (`c:\Users\Usuario\Documents\Gestion\18experience_web`).

---

## 1. Ejecución rápida del script de auditoría

Ejecutar desde PowerShell:

```powershell
powershell -ExecutionPolicy Bypass -File "c:\Users\Usuario\Documents\Gestion\18experience_web\.claude\skills\auditoria-18exp\scripts\audit.ps1"
```

El script verifica automáticamente:
1. **Entorno activo**: PHP 8.2, PostgreSQL en `5432` y `artisan db:show` sobre `db_18_experience`.
2. **Arquitectura Modular**: Módulos presentes en `app/Modules/` con sus `Models`, `Services` y `Controllers`.
3. **Rutas registradas**: Listado de endpoints web (`php artisan route:list --except-vendor`).
4. **Suite de Pruebas**: Resultado en vivo de PHPUnit (`Unit` + `Feature`).
5. **Estado de Git**: Últimos commits y archivos pendientes en el árbol de trabajo.

---

## 2. Matriz de Control de Casos de Uso (CU01 – CU24)

**Fuente de verdad**: SRS `Gestion/FD03_SRS_18_Experience_PHP_DIAGRAMAS_ORIGINALES_Recuperado.docx` y el estado
mantenido en `reglas-negocio-18exp/references/reglas.md`. No usar el PDF antiguo del prototipo Java (RF01–RF05, CU01–CU07).
Verificar cada CU contra **rutas** (`artisan route:list --except-vendor`), **servicio** y **flujos alternos** de su narrativa,
no solo por la existencia del controlador. Tabla orientativa de ubicación:

| Bloque | Casos de Uso | Módulo en `app/Modules/` |
|---|---|---|
| **Seguridad y Accesos** | `CU01` Login + CAPTCHA, `CU02` Logout, `CU03` Control RBAC | `Seguridad` (`AuthController`, `DashboardController`, `CheckRol`) |
| **Usuarios** | `CU04` Registrar usuario, `CU05` Gestionar usuarios | `Usuarios` / `Seguridad` |
| **Clientes** | `CU06` Registrar/editar cliente, `CU07` Consultar cliente e historial | `Clientes` (`ClienteController`, `ClienteService`) |
| **Catálogo y Salidas** | `CU08`–`CU12` Destinos y Productos, `CU13`–`CU14` Programar/gestionar salidas y cupos | `Catalogo` (`DestinoController`, `ProductoController`, `SalidaController`) |
| **Cotizaciones** | `CU15` Generar cotización, `CU16` Exportar PDF, `CU17` Cambiar estado | `Cotizaciones` (`CotizacionController`, `CotizacionService`) |
| **Pagos y Validación** | `CU18` Registrar pago + voucher, `CU19` Validar/rechazar pago (Contabilidad) | `Pagos` (`PagoController`, `PagoService`) |
| **Reservas** | `CU20` Confirmar reserva con `lockForUpdate`, `CU21` Manifiesto de pasajeros, `CU22` Cancelar, `CU23` Completar | `Reservas` (`ReservaController`, `ReservaService`) |
| **Reportes** | `CU24` Reportes de ventas, pagos y ocupación + exportación PDF | `Reportes` (`ReporteController`, `ReporteService`) |

---

## 3. Formato de entrega del reporte

Presentar siempre al usuario:
1. **Resumen Ejecutivo**: CU/RF/RN ✅-🟡-❌ (contando parciales como 0,5), estado de PostgreSQL y resultado de tests (`X passed, Y assertions`).
   Señalar aparte lo implementado **fuera de alcance** del SRS (portal CLIENTE, pasarela Culqi) y las desviaciones técnicas (ver `reglas-negocio-18exp`).
2. **Desglose por Módulo**: Qué módulos están al 100% (con enlaces clicables a sus archivos) y cuáles están pendientes.
3. **Siguiente Paso Recomendado**: El módulo inmediato a construir según el flujo crítico del negocio.
