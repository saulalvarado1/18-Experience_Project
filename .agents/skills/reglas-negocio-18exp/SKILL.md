---
name: reglas-negocio-18exp
description: >-
  Referencia del dominio de 18 Experience (agencia 18 Grados Sur, Tacna): reglas de negocio RN,
  matriz de roles GERENTE/COUNTER/CONTABILIDAD, estados (ENUM) de cotización, pago, reserva y salida,
  catálogo de casos de uso CU01-CU24 y esquema real de db_18_experience. Usar antes de implementar
  o revisar cualquier caso de uso, al escribir validaciones, consultas SQL o tests de reglas,
  y cuando haya dudas sobre qué rol puede hacer qué.
---

# Dominio de negocio 18 Experience

Fuentes oficiales (prevalecen sobre este resumen si hay contradicción):
`C:\Users\Usuario\Documents\PROYECTO_GESTION_ALVARADO\FD03-EPIS-Informe SRS de Proyecto.pdf` (SRS),
`FD01-...Factibilidad...pdf` y `FD04_SAD_18_EXPERIENCE.pdf` (arquitectura).
El prototipo Java en `18Experience_FINAL/src/experience18/service/*.java` contiene la lógica
original de cada CU: consultarlo para replicar validaciones exactas.

## Cuándo leer cada referencia

- [reglas.md](references/reglas.md): reglas RN, matriz de roles, máquinas de estado y lista de CU
  con su estado de implementación. **Leer siempre** antes de implementar un CU.
- [schema.md](references/schema.md): columnas, tipos y ENUMs reales de cada tabla y la definición
  de las vistas `vw_disponibilidad_salida` y `vw_saldo_reserva`. Leer antes de crear un modelo
  o escribir SQL; no inventar columnas.

## Verificación rápida contra la BD

Si una columna o ENUM no aparece en `schema.md`, confirmarlo en vivo:
`& "C:\xampp\mysql\bin\mysql.exe" -u root -e "USE db_18_experience; SHOW CREATE TABLE <tabla>\G"`
y actualizar `schema.md` en el mismo commit.
