@extends('layouts.app')

@section('title', 'Reportes Gerenciales — 18 Grados Sur')
@section('page_title', 'Reportes')

@section('content')
@php
    $periodo = ($desde || $hasta)
        ? 'del ' . ($desde ? \Carbon\Carbon::parse($desde)->format('d/m/Y') : 'inicio') . ' al ' . ($hasta ? \Carbon\Carbon::parse($hasta)->format('d/m/Y') : 'hoy')
        : 'todo el historial';
    $estadosReserva = ['CONFIRMADA' => 'bg-success', 'COMPLETADA' => 'bg-primary', 'CANCELADA' => 'bg-secondary'];
    $totalSaldos = array_sum(array_map(fn ($r) => (float) $r->saldo, $saldos));
    $metodosLabels = array_map(fn ($m) => ucfirst(strtolower(str_replace('_', ' ', $m))), array_keys($resumen['ventas_por_metodo']));
    $metodosValores = array_values($resumen['ventas_por_metodo']);
    $topLabels = array_map(fn ($p) => $p->nombre, $resumen['productos_top']);
    $topValores = array_map(fn ($p) => (int) $p->total_pasajeros, $resumen['productos_top']);
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Reportes de ventas y ocupación</h2>
        <p class="text-muted mb-0">Reservas, ventas, saldos y disponibilidad · <strong>{{ $periodo }}</strong></p>
    </div>
    <a href="{{ route('reportes.pdf', ['desde' => $desde, 'hasta' => $hasta]) }}" class="btn btn-outline-danger shadow-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> Generar PDF
    </a>
</div>

<!-- Período (CU24 pasos 1-2) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.index') }}" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <label class="form-label small fw-semibold" for="desde">Desde</label>
                <input type="date" name="desde" id="desde" class="form-control" value="{{ $desde ?? '' }}">
            </div>
            <div class="col-sm-4">
                <label class="form-label small fw-semibold" for="hasta">Hasta</label>
                <input type="date" name="hasta" id="hasta" class="form-control" value="{{ $hasta ?? '' }}">
            </div>
            <div class="col-sm-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i> Consultar</button>
                @if($desde || $hasta)
                    <a href="{{ route('reportes.index') }}" class="btn btn-light" title="Quitar filtro"><i class="bi bi-x-lg"></i></a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Pestañas (CU24 paso 3) -->
<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
    @foreach([
        'resumen' => ['bi-graph-up', 'Resumen'],
        'reservas' => ['bi-bookmark-check', 'Reservas'],
        'ventas' => ['bi-cash-coin', 'Ventas'],
        'saldos' => ['bi-hourglass-split', 'Saldos pendientes'],
        'disponibilidad' => ['bi-calendar-event', 'Disponibilidad'],
    ] as $id => [$icono, $titulo])
        <li class="nav-item" role="presentation">
            <button class="nav-link text-nowrap {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-{{ $id }}" type="button" role="tab">
                <i class="bi {{ $icono }} me-1"></i>{{ $titulo }}
                @if($id === 'saldos' && count($saldos))<span class="badge bg-warning text-dark ms-1">{{ count($saldos) }}</span>@endif
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content">
    <!-- ===== RESUMEN ===== -->
    <div class="tab-pane fade show active" id="tab-resumen" role="tabpanel">
        <div class="row g-3 mb-4">
            @foreach([
                ['Total vendido', 'S/ ' . number_format($resumen['total_vendido'], 2), 'bi-cash-stack', '#059669'],
                ['Clientes nuevos', $resumen['clientes_nuevos'], 'bi-person-plus', 'var(--brand-primary)'],
                ['Reservas vigentes', $resumen['reservas_vigentes'], 'bi-bookmark-check', '#0284c7'],
                ['Saldos por cobrar', 'S/ ' . number_format($totalSaldos, 2), 'bi-hourglass-split', '#b45309'],
            ] as [$label, $valor, $icono, $color])
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <small class="text-muted">{{ $label }}</small>
                                <i class="bi {{ $icono }} fs-5" style="color: {{ $color }}"></i>
                            </div>
                            <div class="fs-4 fw-bold text-dark mt-1">{{ $valor }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-trophy text-gold me-2"></i>Productos más reservados (pasajeros)</div>
                    <div class="card-body">
                        @if(count($topValores))
                            <canvas id="graficoProductos" height="220" aria-label="Productos más reservados"></canvas>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin reservas en el período.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 fw-bold"><i class="bi bi-pie-chart text-primary me-2"></i>Ventas por método de pago</div>
                    <div class="card-body">
                        @if(count($metodosValores))
                            <canvas id="graficoMetodos" height="220" aria-label="Ventas por método de pago"></canvas>
                        @else
                            <p class="text-muted text-center py-4 mb-0">Sin pagos validados en el período.</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 fw-bold">Rendimiento por producto turístico</div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>Producto</th><th class="text-center">Reservas</th><th class="text-center">Pasajeros</th><th class="text-end">Ingresos contratados</th></tr>
                            </thead>
                            <tbody>
                                @forelse($ventasProducto as $vp)
                                <tr>
                                    <td><div class="fw-bold">{{ $vp->nombre }}</div><small class="text-muted">{{ $vp->codigo }}</small></td>
                                    <td class="text-center">{{ $vp->total_reservas }}</td>
                                    <td class="text-center">{{ $vp->total_pasajeros }}</td>
                                    <td class="text-end fw-bold text-success">S/ {{ number_format($vp->total_ingresos, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-3 text-muted">Sin ventas en el período.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== RESERVAS ===== -->
    <div class="tab-pane fade" id="tab-reservas" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Código</th><th>Fecha</th><th>Cliente</th><th>Servicios</th><th class="text-end">Total</th><th class="text-end">Saldo</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        @forelse($reservas as $r)
                        <tr>
                            <td class="fw-bold text-primary">{{ $r->codigo }}</td>
                            <td>{{ \Carbon\Carbon::parse($r->fecha_reserva)->format('d/m/Y') }}</td>
                            <td>{{ $r->cliente }}</td>
                            <td class="small">{{ $r->servicios }}</td>
                            <td class="text-end">S/ {{ number_format($r->total_contratado, 2) }}</td>
                            <td class="text-end {{ (float) $r->saldo > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">S/ {{ number_format($r->saldo, 2) }}</td>
                            <td><span class="badge {{ $estadosReserva[$r->estado] ?? 'bg-secondary' }}">{{ $r->estado }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay reservas en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== VENTAS (pagos validados) ===== -->
    <div class="tab-pane fade" id="tab-ventas" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between">
                <span class="fw-bold">Pagos validados</span>
                <span class="fw-bold text-success">Total S/ {{ number_format($resumen['total_vendido'], 2) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Fecha</th><th>Cotización</th><th>Cliente</th><th>Método</th><th>Operación</th><th class="text-end">Monto</th></tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $v)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($v->fecha_pago)->format('d/m/Y H:i') }}</td>
                            <td class="fw-semibold text-primary">{{ $v->cotizacion }}</td>
                            <td>{{ $v->cliente }}</td>
                            <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $v->metodo) }}</span></td>
                            <td class="small text-muted">{{ $v->numero_operacion ?: '—' }}</td>
                            <td class="text-end fw-bold">S/ {{ number_format($v->monto, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay pagos validados en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== SALDOS PENDIENTES ===== -->
    <div class="tab-pane fade" id="tab-saldos" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between">
                <span class="fw-bold">Reservas confirmadas con saldo</span>
                <span class="fw-bold text-danger">Por cobrar S/ {{ number_format($totalSaldos, 2) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Código</th><th>Cliente</th><th>Servicios</th><th class="text-end">Total</th><th class="text-end">Pagado</th><th class="text-end">Saldo</th></tr>
                    </thead>
                    <tbody>
                        @forelse($saldos as $r)
                        <tr>
                            <td class="fw-bold text-primary">{{ $r->codigo }}</td>
                            <td>{{ $r->cliente }}</td>
                            <td class="small">{{ $r->servicios }}</td>
                            <td class="text-end">S/ {{ number_format($r->total_contratado, 2) }}</td>
                            <td class="text-end text-success">S/ {{ number_format($r->pagado, 2) }}</td>
                            <td class="text-end fw-bold text-danger">S/ {{ number_format($r->saldo, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay saldos pendientes en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== DISPONIBILIDAD ===== -->
    <div class="tab-pane fade" id="tab-disponibilidad" role="tabpanel">
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Tour</th><th>Fecha</th><th class="text-center">Capacidad</th><th class="text-center">Ocupados</th><th class="text-center">Disponibles</th><th class="text-center">Estado</th></tr>
                    </thead>
                    <tbody>
                        @forelse($ocupacion as $oc)
                        <tr>
                            <td class="fw-semibold">{{ $oc->producto }}</td>
                            <td>{{ \Carbon\Carbon::parse($oc->fecha)->format('d/m/Y') }} {{ substr($oc->hora, 0, 5) }}</td>
                            <td class="text-center">{{ $oc->capacidad }}</td>
                            <td class="text-center text-primary fw-bold">{{ $oc->cupos_ocupados }}</td>
                            <td class="text-center text-success fw-bold">{{ $oc->cupos_disponibles }}</td>
                            <td class="text-center">
                                @if($oc->estado === 'COMPLETA')
                                    <span class="badge bg-danger">LLENO</span>
                                @elseif($oc->estado === 'PROGRAMADA')
                                    <span class="badge bg-success">DISPONIBLE</span>
                                @else
                                    <span class="badge bg-secondary">{{ $oc->estado }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay salidas en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;
    const colores = ['#1e3a8a', '#e0a800', '#059669', '#0284c7', '#b45309', '#7c3aed', '#dc2626'];
    const formatoSoles = v => 'S/ ' + Number(v).toLocaleString('es-PE', { minimumFractionDigits: 2 });

    const prod = document.getElementById('graficoProductos');
    if (prod) {
        new Chart(prod, {
            type: 'bar',
            data: { labels: @json($topLabels), datasets: [{ label: 'Pasajeros', data: @json($topValores), backgroundColor: '#1e3a8a', borderRadius: 6 }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
        });
    }

    const met = document.getElementById('graficoMetodos');
    if (met) {
        new Chart(met, {
            type: 'doughnut',
            data: { labels: @json($metodosLabels), datasets: [{ data: @json($metodosValores), backgroundColor: colores }] },
            options: { plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => `${c.label}: ${formatoSoles(c.raw)}` } } } },
        });
    }
});
</script>
@endsection
