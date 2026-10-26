<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Voucher de Viaje {{ $reserva->codigo }} — 18 Grados Sur</title>
    <style>
        @page {
            margin: 25px 30px 30px 30px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #2b2b2b;
            line-height: 1.35;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #28a745;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .logo {
            max-height: 48px;
        }
        .voucher-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 4px;
            display: inline-block;
        }
        .doc-box {
            text-align: right;
        }
        .reserva-code {
            font-size: 16px;
            font-weight: bold;
            color: #155724;
            margin-top: 4px;
        }
        .section-title {
            background-color: #f0fdf4;
            border-left: 4px solid #28a745;
            padding: 5px 8px;
            font-size: 10.5px;
            font-weight: bold;
            color: #166534;
            margin-top: 10px;
            margin-bottom: 8px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
            width: 20%;
        }
        .info-val {
            width: 30%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th {
            background-color: #198754;
            color: white;
            font-weight: bold;
            font-size: 9px;
            padding: 6px;
            text-align: left;
        }
        .data-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px 6px;
            font-size: 9.5px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .instructions-box {
            border: 1px solid #bbf7d0;
            background-color: #f0fdf4;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9px;
            color: #166534;
            margin-top: 10px;
        }
        .qr-placeholder {
            border: 2px dashed #198754;
            padding: 8px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            color: #198754;
            border-radius: 6px;
            background: #fff;
        }
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                @if(!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" class="logo" alt="18 Grados Sur">
                @else
                    <div style="font-size: 16px; font-weight: bold; color: #155724;">18 GRADOS SUR</div>
                @endif
                <div style="font-size: 9px; color: #555; margin-top: 2px;">
                    Miranda & Escobar Perú Travels S.A.C. — RUC: 20601234567<br>
                    Calle San Martín 456, Tacna — Perú | Central: (052) 789456
                </div>
            </td>
            <td style="width: 50%;" class="doc-box">
                <span class="voucher-badge">BOLETO / VOUCHER DE VIAJE</span>
                <div class="reserva-code">{{ $reserva->codigo }}</div>
                <div style="font-size: 9px; color: #666; margin-top: 2px;">
                    Fecha de Emisión: {{ $reserva->fecha_reserva ? $reserva->fecha_reserva->format('d/m/Y H:i') : date('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="info-label">Titular de Reserva:</td>
            <td class="info-val"><strong>{{ $reserva->cotizacion->cliente->persona->nombreCompleto() }}</strong></td>
            <td class="info-label">Doc. Identidad:</td>
            <td class="info-val">{{ $reserva->cotizacion->cliente->persona->tipo_documento }}: {{ $reserva->cotizacion->cliente->persona->numero_documento }}</td>
        </tr>
        <tr>
            <td class="info-label">Teléfono:</td>
            <td class="info-val">{{ $reserva->cotizacion->cliente->persona->telefono ?? 'N/A' }}</td>
            <td class="info-label">Estado Boleto:</td>
            <td class="info-val"><strong style="color: #198754;">CONFIRMADO / EMITIDO</strong></td>
        </tr>
        <tr>
            <td class="info-label">Cotización Origen:</td>
            <td class="info-val">{{ $reserva->cotizacion->codigo }}</td>
            <td class="info-label">Total Contratado:</td>
            <td class="info-val"><strong>{{ $reserva->moneda }} {{ number_format($reserva->total_contratado, 2) }}</strong></td>
        </tr>
    </table>

    <div class="section-title">1. Itinerario y Servicios Turísticos Programados</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 40%;">Tour o Paquete</th>
                <th style="width: 25%;" class="text-center">Fecha y Hora de Salida</th>
                <th style="width: 30%;">Punto de Encuentro / Recojo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reserva->detalles as $idx => $d)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>
                    <strong>{{ $d->producto->nombre }}</strong>
                    <div style="font-size: 8.5px; color: #666;">Código: {{ $d->producto->codigo }} ({{ $d->cantidad_pasajeros }} pasajeros)</div>
                </td>
                <td class="text-center">
                    @if($d->salida)
                        <strong>{{ $d->salida->fecha ? $d->salida->fecha->format('d/m/Y') : '' }}</strong><br>
                        <span style="color: #198754; font-weight: bold;">{{ $d->salida->hora }}</span>
                    @else
                        Por confirmar con Counter
                    @endif
                </td>
                <td>{{ $d->punto_recojo ?? 'Agencia Central 18 Grados Sur (Calle San Martín 456, Tacna)' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">2. Manifiesto de Pasajeros Registrados</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 45%;">Apellidos y Nombres</th>
                <th style="width: 25%;">Documento de Identidad</th>
                <th style="width: 25%;">Restricciones / Observaciones</th>
            </tr>
        </thead>
        <tbody>
            @php $countPasajero = 1; @endphp
            @foreach($reserva->detalles as $det)
                @forelse($det->pasajeros as $p)
                <tr>
                    <td class="text-center">{{ $countPasajero++ }}</td>
                    <td><strong>{{ $p->persona ? $p->persona->nombreCompleto() : 'Pasajero Titular' }}</strong></td>
                    <td>{{ $p->persona ? $p->persona->tipo_documento . ': ' . $p->persona->numero_documento : '-' }}</td>
                    <td>{{ $p->restriccion_alimentaria ?? 'Ninguna' }}</td>
                </tr>
                @empty
                <tr>
                    <td class="text-center">{{ $countPasajero++ }}</td>
                    <td><strong>{{ $reserva->cotizacion->cliente->persona->nombreCompleto() }}</strong> (Titular)</td>
                    <td>{{ $reserva->cotizacion->cliente->persona->tipo_documento }}: {{ $reserva->cotizacion->cliente->persona->numero_documento }}</td>
                    <td>Ninguna indicada</td>
                </tr>
                @endforelse
            @endforeach
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 75%; vertical-align: top;">
                <div class="instructions-box">
                    <strong>Recomendaciones para el día del viaje:</strong>
                    <ul style="margin: 4px 0 0 14px; padding: 0;">
                        <li>Presentarse 15 minutos antes de la hora indicada en el punto de encuentro con su DNI/Pasaporte físico original.</li>
                        <li>Llevar ropa abrigadora para tours andinos (Palca, Tarata, Miculla) y protección solar/gorra.</li>
                        <li>Cualquier reprogramación debe coordinarse con al menos 24 horas de anticipación.</li>
                        <li>Teléfono de asistencia de emergencia para el turista: <strong>+51 952 789 456</strong>.</li>
                    </ul>
                </div>
            </td>
            <td style="width: 25%; vertical-align: top; padding-left: 14px;">
                <div class="qr-placeholder">
                    <div style="font-size: 24px; margin-bottom: 2px;">[ QR ]</div>
                    <div>VALIDACIÓN DIGITAL</div>
                    <div style="font-size: 8px; color: #555;">{{ $reserva->codigo }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        18 Experience Web — Miranda & Escobar Perú Travels S.A.C. | Boleto electrónico válido para abordar la unidad de transporte turístico
    </div>

</body>
</html>
