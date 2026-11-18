---
name: reglas-negocio-18exp
description: >-
  Referencia del dominio de 18 Experience (agencia 18 Grados Sur, Tacna) según el SRS FD03 versión PHP:
  requerimientos RF01-RF15, RNF01-RNF08, reglas RN01-RN17, casos de uso CU01-CU24 con su estado real,
  matriz de roles GERENTE/COUNTER/CONTABILIDAD/CLIENTE, máquinas de estado y esquema real de
  db_18_experience (PostgreSQL). Usar antes de implementar, revisar o probar cualquier caso de uso,
  al escribir validaciones, consultas SQL o tests de reglas, y cuando haya dudas sobre qué rol puede hacer qué.
---

# Dominio de negocio 18 Experience

## Fuentes oficiales (prevalecen sobre este resumen)

En la raíz del workspace (`Gestion/`, carpeta padre de `18experience_web/`):

- `FD03_SRS_18_Experience_PHP_DIAGRAMAS_ORIGINALES_Recuperado.docx` → **SRS vigente**: RF01–RF15,
  RNF01–RNF08, RN01–RN17 y la narrativa completa (flujo básico y alterno) de CU01–CU24.
- `FD04_SAD_18_Experience_Arquitectura_PHP_Gestion_Proyectos_TI.docx` → **SAD**: monolito modular MVC,
  servicios con `DB::transaction` + `lockForUpdate`, comprobantes como archivo privado.

> El `FD03-EPIS-Informe SRS de Proyecto.pdf` (RF01–RF05, CU01–CU07, Java Swing) es la versión **antigua del
> prototipo Java**; no usarla para medir avance.

Para leer un .docx: extraer `word/document.xml` (zip) o usar la skill `docx`.

## Cuándo leer cada referencia

- [reglas.md](references/reglas.md): RF, RN, matriz de roles, máquinas de estado y **estado real de cada CU**.
  Leer siempre antes de implementar un CU. Al completarlo, actualizar la tabla en el mismo commit.
- [schema.md](references/schema.md): columnas, tipos y estados reales de cada tabla y las vistas
  `vw_disponibilidad_salida` y `vw_saldo_reserva`. Leer antes de crear un modelo o escribir SQL.

## Verificación rápida contra la BD (PostgreSQL)

```bash
PGPASSWORD="$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)" "/c/Program Files/PostgreSQL/18/bin/psql.exe" -h 127.0.0.1 -U postgres -d db_18_experience -c '\d <tabla>'
```
El esquema completo está versionado en `database/schema/pgsql-schema.sql`. Si una columna no aparece en
`schema.md`, confirmarla así y actualizar `schema.md` en el mismo commit.

## Desviaciones conocidas respecto de los documentos

| Documento | Implementado | Nota |
|---|---|---|
| MariaDB, Laravel 13, PHP 8.4 | PostgreSQL 18, Laravel 12, PHP 8.2 | Documentar en el SAD (ADR) |
| Migraciones versionadas | BD existente + scripts `database/sql/*.sql` + dump | Ver skill `modulo-dominio-laravel` |
| Policies / Form Requests | Middleware `rol:` + validación en controladores | |
| Código `COT-AAAAMMDD-HHMMSS-XXXX` / `RES-…` | `COT-2026-0001` / `RES-2026-0001` | Pendiente decidir cuál queda |
| Fuera de alcance: portal, pasarela | Portal CLIENTE + Culqi + devoluciones implementados | Deben añadirse a SRS/SAD v2.0 |
