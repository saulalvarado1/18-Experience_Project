---
name: entorno-18exp
description: >-
  Levanta y verifica el entorno local de 18 Experience Web (XAMPP PHP 8.2, PostgreSQL 18 con
  db_18_experience, Composer) y ejecuta la suite de pruebas PHPUnit. Usar al iniciar una sesión de trabajo,
  cuando un test falle con "SQLSTATE[08006]" o "connection refused" (PostgreSQL caído), cuando 'php' o
  'composer' no se reconozcan, al restaurar la base desde el esquema versionado, o antes de hacer commit.
---

# Entorno local de 18 Experience Web

El proyecto Laravel está en `18experience_web/` (raíz del workspace: `Gestion/`).
No asumas que `php`, `composer` o `psql` están en el PATH: usa rutas absolutas.

| Herramienta | Ruta |
| :--- | :--- |
| PHP 8.2 | `C:\xampp\php\php.exe` |
| Composer | `C:\xampp\php\composer.bat` |
| PostgreSQL 18 (servicio Windows `postgresql-x64-18`, puerto 5432) | `C:\Program Files\PostgreSQL\18\bin\` (`psql.exe`, `pg_dump.exe`) |

Credenciales: solo en `.env` (`DB_*`). **Nunca** escribirlas en documentos, skills ni commits.
Para psql: `PGPASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)" "/c/Program Files/PostgreSQL/18/bin/psql.exe" -h 127.0.0.1 -U postgres -d db_18_experience`.

## 1. Verificar / levantar la base

1. `powershell -File .claude/skills/entorno-18exp/scripts/start-env.ps1 -CheckOnly` (desde `18experience_web/`).
2. Si responde `PGSQL_OFFLINE`, sin `-CheckOnly` intenta `net start postgresql-x64-18`
   (requiere administrador). El servicio arranca solo con Windows.
3. Para el uso diario basta `Gestion/iniciar-sistema.bat` (verifica PostgreSQL y abre `artisan serve`).

**Base nueva / restaurar** (no usar `php artisan migrate` sobre una base vacía sin revisar: cargaría el dump):
```bash
psql ... -c "CREATE DATABASE db_18_experience"
psql ... -d db_18_experience -v ON_ERROR_STOP=1 -f database/schema/pgsql-schema.sql
psql ... -d db_18_experience -v ON_ERROR_STOP=1 -f database/schema/datos-base.sql
```
Faltan usuarios y datos de demostración (no hay seeder todavía).

**Cambios de esquema**: script idempotente en `database/sql/AAAA_MM_DD_descripcion.sql`, aplicarlo con
`psql -v ON_ERROR_STOP=1 -f ...`, ejecutarlo dos veces para probar idempotencia y regenerar
`database/schema/pgsql-schema.sql` con `pg_dump --schema-only --no-owner --no-privileges`.
**Antes de corregir datos** hacer respaldo con `pg_dump --data-only` en `storage/app/private/backups/` (ignorado por git).

## 2. Ejecutar pruebas

`powershell -File .claude/skills/entorno-18exp/scripts/run-tests.ps1` (todas)
`powershell -File .claude/skills/entorno-18exp/scripts/run-tests.ps1 -Filter Cotizacion` (filtradas)

- Corren contra la **base de desarrollo real** (SQLite comentado en `phpunit.xml`: las vistas `vw_*` no existen ahí).
  Toda escritura va dentro de `DB::beginTransaction()` / `DB::rollBack()`.
- `phpunit.xml` usa `CACHE_STORE=array`: un fallo de caché en producción no se ve en tests.
- Éxito = `Tests: N passed` y código 0. Antes de commit, la suite completa debe pasar.

## 3. Servidor de desarrollo

`& "C:\xampp\php\php.exe" artisan serve --port=8000` dentro de `18experience_web/` (como proceso en segundo plano).
Usuarios: `admin` (GERENTE), `counter1` (COUNTER), `contabilidad1` (CONTABILIDAD) con hashes PBKDF2 heredados
de contraseña desconocida; para pruebas manuales el usuario les asigna una con `PasswordService::hash()` vía tinker.
Clientes: se registran en `/registro` (usuario = correo).

## Configuración que no se debe romper

- `APP_TIMEZONE` y `DB_TIMEZONE` = `America/Lima` (si difieren, las fechas se guardan desfasadas 5 h).
- `CACHE_STORE=file` (no existe la tabla `cache`; `throttle` la necesita). `QUEUE_CONNECTION=database` sin tabla `jobs`: no usar colas sin crearla.
- `MAIL_MAILER=log`: los correos solo se escriben en `storage/logs`.
- Pasarela: `PAYMENT_GATEWAY`, `CULQI_*` en `.env`; sin `CULQI_PUBLIC_KEY` el pago online se oculta.

## Problemas conocidos

- `The zip extension ... missing` en Composer → descomentar `extension=zip` en `C:\xampp\php\php.ini`.
- `Module "openssl" is already loaded` → dejar `;extension=openssl` comentado (ya viene compilado).
- PowerShell rompe `php -r "..."` con `$`: escapar como `` `$ `` o usar un archivo `.php` temporal.
- Python: abrir un archivo con `open(p,'w')` dentro de la misma expresión que lo lee lo **vacía** antes de leerlo;
  leer primero en una variable y escribir después.
