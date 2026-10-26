---
name: pdf-dompdf-18exp
description: >-
  Genera documentos PDF corporativos de 18 Experience (cotización CU16, reportes CU24, voucher de
  reserva) con barryvdh/laravel-dompdf y vistas Blade. Usar al crear o corregir cualquier PDF
  descargable del sistema, o cuando un PDF salga con estilos rotos, tildes mal codificadas o sin logo.
---

# PDFs con DomPDF

Paquete instalado: `barryvdh/laravel-dompdf` ^3.1 (facade `Barryvdh\DomPDF\Facade\Pdf`).
Logo: `public/img/logo-18-grados-sur.png`. Plantilla base: [plantilla.blade.php](resources/plantilla.blade.php).

## Pasos

1. Copiar la plantilla a `resources/views/pdf/<documento>.blade.php` y rellenar solo la sección
   `contenido`. Mantener el encabezado (logo, razón social, título, código, fecha) y el pie.
2. En el servicio del módulo (no en el controlador) construir los datos y devolver el PDF:
   ```php
   use Barryvdh\DomPDF\Facade\Pdf;

   return Pdf::loadView('pdf.cotizacion', compact('cotizacion', 'detalles'))
       ->setPaper('a4', 'portrait')
       ->download("Cotizacion_{$cotizacion->codigo}.pdf");   // ->stream() para vista previa
   ```
3. Ruta GET dentro del grupo de rol del módulo, p. ej. `cotizaciones/{id}/pdf` → `cotizaciones.pdf`.
4. Test: `->get('/cotizaciones/'.$id.'/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');`

## Restricciones de DomPDF (evitan el 90 % de los fallos)

- **Sin Bootstrap ni CDN**: CSS inline en `<style>` dentro de la vista. No hay Flexbox ni Grid;
  maquetar con `<table>`. `float` y `position:absolute` funcionan con limitaciones.
- Fuente `DejaVu Sans` (incluida) para que tildes, `ñ` y `S/` se rendericen; `<meta charset="UTF-8">`.
- Imágenes por ruta absoluta del sistema: `public_path('img/logo-18-grados-sur.png')`, no URL.
- Montos: `number_format($monto, 2)` precedido de la moneda (`S/` para PEN, `US$` para USD).
- Evitar cortes de filas: `tr { page-break-inside: avoid; }`. Salto manual: `<div style="page-break-after: always"></div>`.
- Gráficos (CU24): DomPDF no ejecuta JavaScript, así que Chart.js no sirve dentro del PDF. Generar el gráfico como
  barras con `<table>`/`<div>` de ancho porcentual, o una imagen PNG con GD guardada temporalmente.
- No incluir datos sensibles de pasajeros (condición médica) en PDFs dirigidos al cliente salvo que el CU lo pida.
