---
name: entorno-18exp
description: >-
  Levanta y verifica el entorno local de 18 Experience Web (XAMPP PHP 8.2, MySQL/MariaDB
  con db_18_experience, Composer) y ejecuta la suite de pruebas PHPUnit. Usar al iniciar
  una sesión de trabajo, cuando un test falle con "SQLSTATE[HY000] [2002]" (MySQL caído),
  cuando 'php' o 'composer' no se reconozcan, o antes de hacer commit.
---

# Entorno local de 18 Experience Web

El proyecto Laravel está en `18experience_web/` (raíz del workspace: `Gestion/`).
PHP y MySQL vienen de XAMPP en `C:\xampp`. No asumas que `php`/`composer` están en el PATH
de la sesión: usa siempre rutas absolutas.

| Herramienta | Ruta |
| :--- | :--- |
| PHP 8.2 | `C:\xampp\php\php.exe` |
| Composer | `C:\xampp\php\composer.bat` |
| MySQL server | `C:\xampp\mysql\bin\mysqld.exe` |
| MySQL cliente | `C:\xampp\mysql\bin\mysql.exe -u root` (sin contraseña) |

## 1. Levantar MySQL

1. Verificar estado: `powershell -File .agents/skills/entorno-18exp/scripts/start-env.ps1 -CheckOnly`
   (ruta relativa a `18experience_web/`).
2. Si responde `MYSQL_OFFLINE`, arrancar el servidor **como tarea daemon** (`IsDaemon=true`),
   nunca en primer plano porque bloquea:
   `& "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone`
3. Volver a ejecutar el paso 1 hasta obtener `MYSQL_ONLINE`.

Si la BD no existe (instalación nueva), restaurarla desde el respaldo:
`cmd /c "C:\xampp\mysql\bin\mysql.exe -u root -e \"CREATE DATABASE IF NOT EXISTS db_18_experience CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\""`
`cmd /c "C:\xampp\mysql\bin\mysql.exe -u root db_18_experience < C:\Users\Usuario\Documents\PROYECTO_GESTION_ALVARADO\db_18_experience_cloud.sql"`

> El servidor remoto `157.173.102.28:3306` del prototipo Java **no es accesible** desde esta red
> (puerto filtrado). Trabajar siempre contra la BD local.

## 2. Ejecutar pruebas

`powershell -File .agents/skills/entorno-18exp/scripts/run-tests.ps1` (todas)
`powershell -File .agents/skills/entorno-18exp/scripts/run-tests.ps1 -Filter Cotizacion` (filtradas)

- Los tests corren contra **MySQL real** (en `phpunit.xml` SQLite está comentado a propósito,
  porque las vistas `vw_*` y los ENUM no existen en SQLite). No reactivar SQLite.
- Éxito = salida con `Tests: N passed` y código 0. Antes de commit, la suite completa debe pasar.

## 3. Servidor de desarrollo

Ejecutar como daemon: `& "C:\xampp\php\php.exe" artisan serve --port=8000` dentro de
`18experience_web/`. Usuarios de la BD semilla: `admin` (GERENTE), `counter1` (COUNTER),
`contabilidad1` (CONTABILIDAD). Sus contraseñas son hashes PBKDF2 heredados del prototipo Java
y no se conocen; para pruebas manuales crear un usuario con `PasswordService::hash()` vía tinker.

## Problemas conocidos

- `The zip extension ... missing` en Composer → descomentar `extension=zip` en `C:\xampp\php\php.ini`.
- `Module "openssl" is already loaded` → dejar `;extension=openssl` comentado (ya viene compilado).
- PowerShell rompe `php -r "..."` con `$`: escapar como `` `$ `` o usar un archivo `.php` temporal.
