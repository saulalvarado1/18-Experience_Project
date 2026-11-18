# 18 Experience — Sistema de Gestión y Operaciones Turísticas

Sistema integral de gestión para la agencia de viajes y operador turístico **18 Grados Sur** (Tacna, Perú).

## 🚀 Características Principales

- **Seguridad & RBAC:** Control de acceso por roles (Gerente, Counter, Contabilidad, Cliente) con autenticación protegida por CAPTCHA.
- **Catálogo de Operaciones:** Gestión de destinos, tours/paquetes, asignación de itinerarios y programación de salidas con control estricto de cupos y prevención de sobreventa (*pessimistic locking*).
- **Ventas & Reservas:** Emisión de cotizaciones correlativas (`COT-2026-XXXX`), cálculo de anticipos mínimos del 50%, generación de PDF corporativos y registro de reservas con detalle de pasajeros.
- **Finanzas:** Registro de pagos en efectivo, transferencias, Yape/Plin, pasarela online y validación contable segregada.
- **Portal del Cliente:** Autoregistro B2C, consulta de cotizaciones, carga de vouchers y pagos en línea.
- **Contenedores Docker:** Entorno listo para producción y desarrollo local con PHP 8.2, Apache y PostgreSQL 16.

## 🛠️ Tecnologías

- **Backend:** Laravel 12 / PHP 8.2
- **Base de Datos:** PostgreSQL 18 / 16
- **Frontend:** Bootstrap 5, Blade, Bootstrap Icons
- **Documentos:** DomPDF
- **Contenedores:** Docker & Docker Compose
- **Testing:** PHPUnit (Suite completa de 111 pruebas automatizadas)

## 🐳 Ejecución con Docker

```bash
# Iniciar contenedores en segundo plano
docker compose up -d

# Acceder al sistema
http://localhost:8000
```
