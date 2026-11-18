---
name: modulo-dominio-laravel
description: >-
  Procedimiento y plantillas para crear o extender un módulo de dominio en 18 Experience Web
  siguiendo la arquitectura Monolito Modular (app/Modules/<Modulo>/{Models,Services,Controllers}).
  Usar cuando se implemente un nuevo caso de uso (CU) o módulo: Ventas/Cotizaciones, Finanzas/Pagos,
  Reservas, Reportes, Usuarios, o al añadir modelos sobre tablas existentes de db_18_experience.
---

# Crear un módulo de dominio

La BD **ya existe** y es la fuente de verdad: no se crean migraciones de Laravel para tablas de negocio
(un `artisan migrate` sobre la base actual intentaría cargar el dump). Los cambios de esquema van en
scripts idempotentes `database/sql/AAAA_MM_DD_*.sql` + regenerar `database/schema/pgsql-schema.sql` (ver `entorno-18exp`).
Antes de escribir un modelo, revisar columnas y ENUMs en
[schema.md](../reglas-negocio-18exp/references/schema.md) y las reglas en
[reglas.md](../reglas-negocio-18exp/references/reglas.md).

## Pasos

1. **Carpetas**: `app/Modules/<Modulo>/Models`, `Services`, `Controllers` y la vista en
   `resources/views/<recurso>/index.blade.php`. Namespace `App\Modules\<Modulo>\...`
   (PSR-4 ya cubierto por `App\`).
2. **Modelo** — copiar [Model.php.stub](resources/Model.php.stub). Obligatorio en todos:
   `$table` en singular, `$primaryKey = 'id_<tabla>'`, `public $timestamps = false;`,
   `$casts` para `activo` (boolean), fechas y `decimal:2`. Reutilizar modelos de otros módulos
   (p. ej. `App\Modules\Seguridad\Models\Persona`, `App\Modules\Catalogo\Models\Salida`); no duplicarlos.
3. **Servicio** — copiar [Service.php.stub](resources/Service.php.stub). Toda regla de negocio va
   aquí, no en el controlador:
   - Validaciones de negocio → `throw new \InvalidArgumentException('mensaje en español')`.
   - Escrituras múltiples → `DB::transaction(fn () => ...)`.
   - Operación crítica → `Auditoria::registrar($idUsuario, 'ACCION_EN_MAYUSCULAS', 'tabla', $id, 'detalle')`.
   - Un módulo solo llama a **servicios** de otro módulo, nunca escribe sus tablas directamente.
4. **Controlador** — copiar [Controller.php.stub](resources/Controller.php.stub). Inyecta el servicio
   por constructor, captura `\InvalidArgumentException` → `back()->withInput()->with('error', ...)`
   y éxito → `redirect()->route(...)->with('success', ...)`. Endpoints AJAX devuelven JSON.
5. **Rutas** — registrar en `routes/web.php` **dentro del grupo de rol correcto**
   (matriz en [reglas.md](../reglas-negocio-18exp/references/reglas.md#matriz-de-roles)).
   Nombrar `recurso.accion`. Restricciones finas dentro de un grupo (p. ej. solo GERENTE cambia precio)
   se validan además en el controlador con `Auth::user()->tieneRol('GERENTE')`.
6. **Vista** — `@extends('layouts.app')` (pantallas sin sesión: `layouts.guest`), `@section('title')`,
   `@section('page_title')` (título corto, sin códigos CU) y un encabezado
   `<h2 class="fw-bold text-dark mb-1">Título</h2><p class="text-muted">descripción</p>`.
   Bootstrap 5 + Bootstrap Icons (CDN ya incluidos), formularios en modales.
   - **Colores**: solo los de `resources/views/partials/marca.blade.php` (`btn-primary` = azul marino,
     `btn-gold` = acción destacada/pago, `text-gold`). No poner `style="background-color: ..."` en botones.
   - **Tablas**: `table table-hover align-middle` dentro de `.table-responsive`, cabecera `table-light`.
     En celular se convierten solas en tarjetas (`.tabla-movil`, usa el texto de cada `<th>`);
     la columna de botones debe llamarse "Acciones" o ser `td.text-end`. Excluir una tabla con `no-tabla-movil`.
   - **Confirmaciones**: nunca `confirm()`; usar `data-confirm="mensaje"` + opcionales
     `data-confirm-title`, `data-confirm-ok`, `data-confirm-variant` (primary|success|warning|danger).
   - **Mensajes**: el layout ya muestra `success|warning|info|error` y `$errors`; no repetirlos en la vista.
   - Textos en minúsculas normales (no TODO EN MAYÚSCULAS); montos con `S/`.
   - **`@json` solo con una variable**: `@json([...])` o `@json($x->only([...]))` rompe la vista
     (Blade separa los argumentos por comas). Armar el array en `@php ... @endphp` y luego `@json($datos)`.
   El menú lateral (`layouts/app.blade.php`) ya tiene los enlaces; si el módulo es nuevo, añadirlo
   ahí respetando el `@if (auth()->user()->tieneRol(...))` correspondiente.
7. **Tests** — copiar [FeatureTest.php.stub](resources/FeatureTest.php.stub) a `tests/Feature/`.
   Cubrir como mínimo: acceso permitido por rol, acceso 403 para rol no autorizado, caso feliz,
   cada regla de negocio que debe rechazar, y que se escribe la fila de `auditoria`.
8. **Verificar y versionar** — ejecutar la suite completa (skill `entorno-18exp`); con todo en verde:
   `git add . ; git commit -m "feat(<modulo>): <CUxx> ..."`.

## Convenciones de pruebas (importante)

- Corren contra la base PostgreSQL de desarrollo con sus datos. **Toda escritura va dentro de
  `DB::beginTransaction()` ... `finally { DB::rollBack(); }`** para no ensuciar la BD.
- No usar `RefreshDatabase` ni factories de `users` (borrarían/no aplican al esquema real).
- Autenticar con usuarios semilla por rol: `Usuario::where('id_rol', Rol::where('nombre','COUNTER')->value('id_rol'))->first()` + `actingAs()`.
- Usar fechas futuras y códigos con `uniqid()` para no chocar con datos existentes.
