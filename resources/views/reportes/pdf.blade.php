<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Gerencial Ejecutivo — 18 Grados Sur</title>
    <style>
        @page {
            margin: 28px 30px 30px 30px;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #2b2b2b;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .logo {
            max-height: 48px;
        }
        .doc-title {
            font-size: 15px;
            font-weight: bold;
            color: #0056b3;
            text-align: right;
        }
        .metricas-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .metricas-table td {
            background-color: #f4f7fa;
            border: 1px solid #dcdcdc;
            padding: 8px 10px;
            text-align: center;
            width: 25%;
        }
        .metricas-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
        }
        .metricas-valor {
            font-size: 14px;
            font-weight: bold;
            color: #0056b3;
            margin-top: 4px;
        }
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #003366;
            margin-top: 10px;
            margin-bottom: 6px;
            border-bottom: 1px solid #cce;
            padding-bottom: 3px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .data-table th {
            background-color: #0056b3;
            color: #ffffff;
            font-weight: bold;
            padding: 5px 6px;
            font-size: 9px;
            text-align: left;
        }
        .data-table td {
            border-bottom: 1px solid #eee;
            padding: 4px 6px;
            font-size: 9.5px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ddd;
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
                    <div style="font-size: 16px; font-weight: bold; color: #0056b3;">18 GRADOS SUR</div>
                @endif
                <div style="font-size: 9px; color: #666; margin-top: 2px;">
                    Miranda & Escobar Perú Travels S.A.C. — Tacna, Perú
                </div>
            </td>
            <td style="width: 50%;">
                <div class="doc-title">REPORTE GERENCIAL EJECUTIVO</div>
                <div style="text-align: right; font-size: 9px; color: #555; margin-top: 2px;">
                    Generado el: {{ $fechaEmision }}
                </div>
            </td>
        </tr>
    </table>

    <table class="metricas-table">
        <tr>
            <td>
                <div class="metricas-label">Cotizaciones</div>
                <div class="metricas-valor">{{ $metricas['total_cotizaciones'] }}</div>
            </td>
            <td>
                <div class="metricas-label">Reservas Confirmadas</div>
                <div class="metricas-valor">{{ $metricas['total_reservas'] }}</div>
            </td>
            <td>
                <div class="metricas-label">Total Ventas Contratadas</div>
                <div class="metricas-valor">S/ {{ number_format($metricas['total_ventas'], 2) }}</div>
            </td>
            <td>
                <div class="metricas-label">Pagos Validados</div>
                <div class="metricas-valor" style="color: #28a745;">S/ {{ number_format($metricas['total_recaudado'], 2) }}</div>
            </td>
        </tr>
    </table>

    @if($resumen)
    @php
        // Gráficos para DomPDF (sin JavaScript): barras HTML con ancho proporcional (CU24).
        $maxPax = max(1, ...array_map(fn ($p) => (int) $p->total_pasajeros, $resumen['productos_top'] ?: [(object) ['total_pasajeros' => 1]]));
        $maxMetodo = max(1, ...(array_values($resumen['ventas_por_metodo']) ?: [1]));
    @endphp
    <div class="section-title">Resumen del período: {{ $desde ? \Carbon\Carbon::parse($desde)->format('d/m/Y') : 'inicio' }} al {{ $hasta ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : 'hoy' }}</div>
    <table class="metricas-table">
        <tr>
            <td><div class="metricas-label">Clientes nuevos</div><div class="metricas-valor">{{ $resumen['clientes_nuevos'] }}</div></td>
            <td><div class="metricas-label">Total vendido (pagos validados)</div><div class="metricas-valor" style="color: #28a745;">S/ {{ number_format($resumen['total_vendido'], 2) }}</div></td>
            <td><div class="metricas-label">Reservas vigentes</div><div class="metricas-valor">{{ $resumen['reservas_vigentes'] }}</div></td>
        </tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 14px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                <div style="font-weight: bold; font-size: 10px; margin-bottom: 6px;">Productos más reservados (pasajeros)</div>
                @forelse($resumen['productos_top'] as $p)
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 9px;">
                        <tr style="page-break-inside: avoid;">
                            <td style="width: 40%;">{{ \Illuminate\Support\Str::limit($p->nombre, 28) }}</td>
                            <td style="width: 50%;"><div style="background: #1e3a8a; height: 10px; width: {{ round($p->total_pasajeros * 100 / $maxPax) }}%;"></div></td>
                            <td style="width: 10%; text-align: right;">{{ $p->total_pasajeros }}</td>
                        </tr>
                    </table>
                @empty
                    <div style="font-size: 9px; color: #777;">Sin reservas en el período.</div>
                @endforelse
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 10px;">
                <div style="font-weight: bold; font-size: 10px; margin-bottom: 6px;">Ventas por método de pago</div>
                @forelse($resumen['ventas_por_metodo'] as $metodo => $monto)
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 4px; font-size: 9px;">
                        <tr style="page-break-inside: avoid;">
                            <td style="width: 30%;">{{ str_replace('_', ' ', $metodo) }}</td>
                            <td style="width: 45%;"><div style="background: #e0a800; height: 10px; width: {{ round($monto * 100 / $maxMetodo) }}%;"></div></td>
                            <td style="width: 25%; text-align: right;">S/ {{ number_format($monto, 2) }}</td>
                        </tr>
                    </table>
                @empty
                    <div style="font-size: 9px; color: #777;">Sin pagos validados en el período.</div>
                @endforelse
            </td>
        </tr>
    </table>
    @endif

    <div class="section-title">1. Rendimiento Comercial por Producto Turístico</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Código</th>
                <th style="width: 45%;">Nombre del Producto / Tour</th>
                <th style="width: 12%;" class="text-center">N.º Reservas</th>
                <th style="width: 13%;" class="text-center">Pasajeros</th>
                <th style="width: 15%;" class="text-right">Total Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ventasProducto as $vp)
            <tr>
                <td>{{ $vp->codigo }}</td>
                <td><strong>{{ $vp->nombre }}</strong></td>
                <td class="text-center">{{ $vp->total_reservas }}</td>
                <td class="text-center">{{ $vp->total_pasajeros }}</td>
                <td class="text-right" style="font-weight: bold; color: #0056b3;">S/ {{ number_format($vp->total_ingresos, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center py-2" style="color: #888;">Sin registros comerciales registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">2. Disponibilidad y Ocupación de Salidas Turísticas</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 45%;">Tour / Salida</th>
                <th style="width: 15%;" class="text-center">Fecha y Hora</th>
                <th style="width: 10%;" class="text-center">Capacidad</th>
                <th style="width: 10%;" class="text-center">Ocupados</th>
                <th style="width: 10%;" class="text-center">Disponibles</th>
                <th style="width: 10%;" class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ocupacion as $oc)
            <tr>
                <td>{{ $oc->producto }}</td>
                <td class="text-center">{{ $oc->fecha }} {{ $oc->hora }}</td>
                <td class="text-center">{{ $oc->capacidad }}</td>
                <td class="text-center" style="font-weight: bold;">{{ $oc->cupos_ocupados }}</td>
                <td class="text-center" style="font-weight: bold; color: #28a745;">{{ $oc->cupos_disponibles }}</td>
                <td class="text-center">{{ $oc->estado }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-2" style="color: #888;">Sin salidas registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        18 Experience Web — Sistema de Gestión Comercial y Turística | Confidencial para Gerencia General
    </div>

</body>
</html>
