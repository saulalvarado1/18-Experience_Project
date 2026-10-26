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
