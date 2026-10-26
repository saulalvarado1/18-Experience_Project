<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización {{ $cotizacion->codigo }} — 18 Grados Sur</title>
    <style>
        @page {
            margin: 28px 32px 36px 32px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #2b2b2b;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .logo {
            max-height: 52px;
        }
        .company-title {
            font-size: 15px;
            font-weight: bold;
            color: #0056b3;
        }
        .company-meta {
            font-size: 9.5px;
            color: #555;
        }
        .doc-title-box {
            background-color: #f0f4f8;
            border: 1px solid #c9d6df;
            padding: 8px 12px;
            text-align: right;
            border-radius: 4px;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #003366;
            margin: 0;
        }
        .doc-codigo {
            font-size: 12px;
            font-weight: bold;
            color: #d9534f;
            margin-top: 2px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #444;
            width: 18%;
        }
        .info-val {
            width: 32%;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 16px;
        }
        .items-table th {
            background-color: #0056b3;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
        }
        .items-table td {
            border-bottom: 1px solid #e0e0e0;
            padding: 6px 8px;
            font-size: 10.5px;
        }
        .items-table tr:nth-child(even) {
            background-color: #fbfbfb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-table {
            width: 45%;
            margin-left: auto;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .totals-table td {
            padding: 4px 8px;
        }
        .totals-table .total-row {
            background-color: #0056b3;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
        }
        .terms-box {
            border: 1px dashed #bbb;
            background-color: #fafafa;
            padding: 8px 10px;
            font-size: 9px;
            color: #555;
            border-radius: 4px;
        }
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8.5px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" class="logo" alt="18 Grados Sur">
                @else
                    <div class="company-title">18 GRADOS SUR</div>
                @endif
                <div class="company-meta" style="margin-top: 4px;">
                    Miranda & Escobar Perú Travels S.A.C. — RUC: 20601234567<br>
                    Calle San Martín 456, Tacna — Perú | Tel: (052) 789456 | reservas@18gradosur.pe
                </div>
            </td>
            <td style="width: 45%; vertical-align: middle;">
                <div class="doc-title-box">
                    <div class="doc-title">COTIZACIÓN TURÍSTICA</div>
                    <div class="doc-codigo">{{ $cotizacion->codigo }}</div>
                    <div style="font-size: 9px; color: #666; margin-top: 3px;">
                        Emisión: {{ $cotizacion->fecha_cotizacion ? $cotizacion->fecha_cotizacion->format('d/m/Y') : date('d/m/Y') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="info-label">Cliente:</td>
            <td class="info-val"><strong>{{ $cotizacion->cliente->persona->nombreCompleto() }}</strong></td>
            <td class="info-label">Doc. Identidad:</td>
            <td class="info-val">{{ $cotizacion->cliente->persona->tipo_documento }} {{ $cotizacion->cliente->persona->numero_documento }}</td>
        </tr>
        <tr>
            <td class="info-label">Teléfono:</td>
            <td class="info-val">{{ $cotizacion->cliente->persona->telefono ?? 'No registrado' }}</td>
            <td class="info-label">Correo:</td>
            <td class="info-val">{{ $cotizacion->cliente->persona->correo ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <td class="info-label">Vigente hasta:</td>
            <td class="info-val"><strong>{{ $cotizacion->vigencia_hasta ? $cotizacion->vigencia_hasta->format('d/m/Y') : '7 días' }}</strong></td>
            <td class="info-label">Atendido por:</td>
            <td class="info-val">{{ $cotizacion->usuarioCounter->nombre_usuario ?? 'Counter' }}</td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 45%;">Tour / Detalle del Servicio</th>
                <th style="width: 18%;" class="text-center">Salida</th>
                <th style="width: 10%;" class="text-center">Pasajeros</th>
                <th style="width: 11%;" class="text-right">P. Unitario</th>
                <th style="width: 11%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cotizacion->detalles as $index => $det)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $det->producto->nombre }}</strong>
                    <div style="font-size: 9px; color: #666;">Código: {{ $det->producto->codigo }} | Tipo: {{ $det->producto->tipo_producto }}</div>
                    @if($det->observacion)
                    <div style="font-size: 9px; color: #555; font-style: italic;">Obs: {{ $det->observacion }}</div>
                    @endif
                </td>
                <td class="text-center">
                    @if($det->salida)
                        {{ $det->salida->fecha ? $det->salida->fecha->format('d/m/Y') : '' }}<br>
                        <small style="color: #666;">{{ $det->salida->hora }}</small>
                    @else
                        <span style="color: #888;">Por programar</span>
                    @endif
                </td>
                <td class="text-center">{{ $det->cantidad_pasajeros }}</td>
                <td class="text-right">{{ $cotizacion->moneda }} {{ number_format($det->precio_unitario, 2) }}</td>
                <td class="text-right"><strong>{{ $cotizacion->moneda }} {{ number_format($det->subtotal, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr class="total-row">
            <td style="padding: 6px 10px;">TOTAL GENERAL:</td>
            <td class="text-right" style="padding: 6px 10px;">{{ $cotizacion->moneda }} {{ number_format($cotizacion->total, 2) }}</td>
        </tr>
    </table>

    <div class="terms-box">
        <strong>Condiciones Comerciales y de Reserva:</strong>
        <ul style="margin: 4px 0 0 16px; padding: 0;">
            <li>Los precios están expresados en Soles (PEN) e incluyen impuestos de ley.</li>
            <li>Para confirmar su reserva se requiere un anticipo mínimo del 50% del valor total (RN05).</li>
            <li>Los pagos por transferencia, depósito, Yape o Plin requieren validación por nuestro dpto. de Contabilidad.</li>
            <li>Esta cotización no reserva cupos de forma automática; la disponibilidad se asegura al validar el anticipo y emitir la reserva.</li>
        </ul>
    </div>

    <div class="footer">
        18 Experience Web — Sistema de Gestión Comercial | Página 1 de 1 — Impreso el {{ date('d/m/Y H:i:s') }}
    </div>

</body>
</html>
