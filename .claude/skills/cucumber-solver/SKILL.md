---
name: cucumber-solver
description: Resuelve ejercicios, talleres, laboratorios y proyectos completos de cursos sobre Cucumber, BDD y Gherkin. Analiza enunciados, escribe archivos .feature, programa Step Definitions y ejecuta los tests hasta que pasen.
---

# Resolutor Autónomo de Cursos y Talleres de Cucumber (BDD)

## Objetivo
Actuar como un Ingeniero Senior en QA Automation encargado de resolver de principio a fin cualquier lección, ejercicio práctico, cuestionario o proyecto de un curso de Cucumber y Gherkin dentro del workspace.

## Cuándo usar esta skill
- Cuando el usuario pegue el enunciado de un ejercicio, tarea o laboratorio de Cucumber.
- Cuando el usuario pida resolver archivos `.feature` o *Step Definitions* incompletos en el proyecto.
- Cuando se necesite completar módulos de un curso de BDD con código funcional y verificado.

## Flujo de Trabajo Obligatorio
1. **Inspección del entorno:** Revisa los archivos existentes en el workspace (`pom.xml`, `package.json`, `requirements.txt`, carpetas `features/` o `step_definitions/`) para detectar el lenguaje, el framework y las dependencias ya instaladas. Si el proyecto está vacío, inicialízalo con la estructura estándar del lenguaje detectado o solicitado.
2. **Análisis del requerimiento:** Lee el enunciado del curso, historia de usuario o código con `TODO`s e identifica todos los escenarios necesarios (casos felices, casos borde y validaciones de error).
3. **Implementación de Gherkin (`.feature`):**
   - Escribe escenarios limpios y **declarativos** (enfocados en reglas de negocio, evitando detalles técnicos o selectores de UI).
   - Utiliza `Background`, `Scenario Outline` con `Examples`, Data Tables y `@tags` cuando el ejercicio lo requiera o mejore la cobertura.
4. **Implementación de código (*Step Definitions* y Hooks):**
   - Escribe el código de los pasos usando Cucumber Expressions (`{string}`, `{int}`, `{float}`) y buenas prácticas (Page Object Model o separación de lógica).
   - Configura `@Before` y `@After` si se requiere inicialización o limpieza de datos/navegador.
5. **Ejecución y verificación en terminal:**
   - Ejecuta la suite de Cucumber desde la terminal (`mvn test`, `npx cucumber-js`, `behave`, etc.).
   - Si algún paso queda *undefined*, *pending* o falla, corrige el código automáticamente y vuelve a ejecutar hasta lograr **100% de pruebas en verde**.
6. **Entrega clara:** Al finalizar, presenta un resumen breve de los archivos creados/modificados, la explicación de la solución para el curso y la evidencia de ejecución exitosa.

## Estándares de Calidad
- **Cero pasos sin implementar:** Ningún escenario debe quedar con advertencias de *snippets* faltantes.
- **Respuestas a cuestionarios:** Si el curso incluye preguntas teóricas o de opción múltiple junto al código, respóndelas de forma directa y justificada basándote en la documentación oficial de Cucumber.
