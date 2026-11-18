<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo ?? 'Documento' }} - 18 Experience</title>
    <style>
        @page { margin: 28px 32px 60px 32px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1f2937; }
        .header { width: 100%; border-bottom: 3px solid #ca8a04; padding-bottom: 8px; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .empresa { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .sub { color: #6b7280; font-size: 9px; }
        .doc-box { text-align: right; }
        .doc-titulo { font-size: 13px; font-weight: bold; color: #111827; }
        .codigo { font-size: 11px; color: #1e3a8a; font-weight: bold; }
        h3 { font-size: 11px; color: #1e3a8a; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; margin: 14px 0 6px; text-transform: uppercase; }
        table.datos { width: 100%; border-collapse: collapse; }
        table.datos th { background: #1e3a8a; color: #fff; padding: 5px; font-size: 9.5px; text-align: left; }
        table.datos td { padding: 5px; border-bottom: 1px solid #e5e7eb; }
        table.datos tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .total td { font-weight: bold; background: #f3f4f6; }
        .footer { position: fixed; bottom: -40px; left: 0; right: 0; text-align: center; font-size: 8.5px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width: 90px;">
                <img src="{{ public_path('img/logo-18-grados-sur.png') }}" style="height: 55px;">
            </td>
            <td>
                <div class="empresa">18 EXPERIENCE — 18 GRADOS SUR</div>
                <div class="sub">Miranda &amp; Escobar Perú Travels S.A.C. · Tacna, Perú</div>
            </td>
            <td class="doc-box">
                <div class="doc-titulo">{{ $titulo ?? 'DOCUMENTO' }}</div>
                @isset($codigo)<div class="codigo">{{ $codigo }}</div>@endisset
                <div class="sub">Emitido: {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    {{-- Contenido específico del documento: reemplazar este bloque --}}
    @yield('contenido')

    <div class="footer">
        Documento generado por el sistema 18 Experience · Uso interno y del cliente destinatario
    </div>
</body>
</html>
