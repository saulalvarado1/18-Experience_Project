@extends('layouts.app')

@section('title', 'Reportes Gerenciales — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Reportes de Ventas y Ocupación (CU24)</h2>
        <p class="text-muted mb-0">Consolidado comercial, recaudación y ocupación de cupos en salidas turísticas.</p>
    </div>
    <div>
        <a href="{{ route('reportes.pdf', ['desde' => $desde, 'hasta' => $hasta]) }}" class="btn btn-outline-danger shadow-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Exportar Reporte Ejecutivo PDF
        </a>
    </div>
</div>

<!-- Filtros de fecha -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reportes.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Fecha Desde</label>
                <input type="date" name="desde" class="form-control" value="{{ $desde ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Fecha Hasta</label>
                <input type="date" name="hasta" class="form-control" value="{{ $hasta ?? '' }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i> Aplicar Filtros</button>
            </div>
        </form>
    </div>
</div>

<!-- Tarjetas de métricas generales -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="fs-6 opacity-75">Cotizaciones Totales</div>
                <div class="fs-2 fw-bold">{{ $metricas['total_cotizaciones'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body">
                <div class="fs-6 opacity-75">Reservas Confirmadas</div>
                <div class="fs-2 fw-bold">{{ $metricas['total_reservas'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-info text-dark">
            <div class="card-body">
                <div class="fs-6 fw-semibold">Ventas Totales Contratadas</div>
                <div class="fs-2 fw-bold">S/ {{ number_format($metricas['total_ventas'], 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm bg-warning text-dark">
            <div class="card-body">
                <div class="fs-6 fw-semibold">Total Pagos Validados</div>
                <div class="fs-2 fw-bold">S/ {{ number_format($metricas['total_recaudado'], 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Ventas por Producto -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-trophy me-2 text-warning"></i>Rendimiento por Producto Turístico
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Reservas</th>
                            <th class="text-center">Pasajeros</th>
                            <th class="text-end">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventasProducto as $vp)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $vp->nombre }}</div>
                                <small class="text-muted">{{ $vp->codigo }}</small>
                            </td>
                            <td class="text-center">{{ $vp->total_reservas }}</td>
                            <td class="text-center">{{ $vp->total_pasajeros }}</td>
                            <td class="text-end fw-bold text-success">S/ {{ number_format($vp->total_ingresos, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-3 text-muted">Sin ventas en el período.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ocupación de Salidas -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-pie-chart me-2 text-primary"></i>Disponibilidad y Ocupación de Salidas
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tour / Fecha</th>
                            <th class="text-center">Capacidad</th>
                            <th class="text-center">Ocupados</th>
                            <th class="text-center">Disponibles</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ocupacion as $oc)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $oc->producto }}</div>
                                <small class="text-muted">{{ $oc->fecha }} {{ $oc->hora }}</small>
                            </td>
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
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">No hay salidas programadas.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
