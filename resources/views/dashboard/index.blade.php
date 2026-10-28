@extends('layouts.app')

@section('title', 'Inicio')
@section('page_title', 'Inicio — Resumen Operativo')

@section('content')
<div class="container-fluid">
    <!-- Saludo de Bienvenida -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark">Bienvenido, {{ auth()->user()->persona?->nombre_completo ?? auth()->user()->nombre_usuario }}</h4>
        <p class="text-muted small">Resumen de operaciones y estado del día de hoy en 18 Grados Sur.</p>
    </div>

    <!-- Tarjetas de Indicadores del Día (CU02) -->
    <div class="row g-3 mb-4">
        <!-- Reservas Hoy -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">RESERVAS HOY</span>
                        <h2 class="fw-bold my-1 text-primary">{{ $reservasHoy }}</h2>
                        <small class="text-muted">Nuevas reservas activas</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                        <i class="bi bi-bookmark-check-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Salidas Hoy -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">SALIDAS HOY</span>
                        <h2 class="fw-bold my-1 text-success">{{ $salidasHoy }}</h2>
                        <small class="text-muted">Servicios programados</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                        <i class="bi bi-bus-front-fill fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagos por Validar -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">PAGOS POR VALIDAR</span>
                        <h2 class="fw-bold my-1 text-warning">{{ $pagosPendientes }}</h2>
                        <small class="text-muted">Vouchers en revisión</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle">
                        <i class="bi bi-cash-coin fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Recaudado -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase fw-semibold" style="font-size: 0.75rem;">RECAUDACIÓN VALIDADA</span>
                        <h2 class="fw-bold my-1 text-info">S/ {{ number_format($totalRecaudado, 2) }}</h2>
                        <small class="text-muted">Abonos conformes</small>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle">
                        <i class="bi bi-wallet2 fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos Analíticos Interactivos (RF14 - Chart.js) -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark">
                        <i class="bi bi-bar-chart-line-fill me-2 text-primary"></i> EVOLUCIÓN DE VENTAS (SOLES)
                    </h6>
                    <span class="badge bg-light text-primary border">Histórico Mensual</span>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartVentas"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark">
                        <i class="bi bi-pie-chart-fill me-2 text-success"></i> DEMANDA POR TOUR
                    </h6>
                    <span class="badge bg-light text-success border">Pasajeros Totales</span>
                </div>
                <div class="card-body">
                    <div style="height: 250px;">
                        <canvas id="chartTours"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla: Próximas Salidas (CU02 - vw_disponibilidad_salida) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0 text-dark">
                <i class="bi bi-calendar3 me-2 text-primary"></i> PRÓXIMAS SALIDAS PROGRAMADAS
            </h6>
            <span class="badge bg-light text-secondary border">Vista en tiempo real</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">Fecha</th>
                            <th>Hora</th>
                            <th>Tour / Servicio</th>
                            <th class="text-center">Capacidad</th>
                            <th class="text-center">Ocupados</th>
                            <th class="text-center">Disponibles</th>
                            <th class="pe-4 text-end">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proximasSalidas as $salida)
                            <tr>
                                <td class="ps-4 fw-semibold">{{ \Carbon\Carbon::parse($salida->fecha)->format('d/m/Y') }}</td>
                                <td>{{ substr($salida->hora, 0, 5) }}</td>
                                <td class="fw-semibold text-dark">{{ $salida->nombre_producto ?? ($salida->producto ?? $salida->codigo_producto) }}</td>
                                <td class="text-center">{{ $salida->capacidad }}</td>
                                <td class="text-center text-danger fw-semibold">{{ $salida->cupos_ocupados }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">
                                        {{ $salida->cupos_disponibles }} cupos
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <span class="badge {{ $salida->estado === 'PROGRAMADA' ? 'bg-primary' : ($salida->estado === 'COMPLETA' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ $salida->estado }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle me-1"></i> No hay salidas registradas o pendientes para hoy.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Gráfico de Ventas Mensuales
    const ctxVentas = document.getElementById('chartVentas');
    if (ctxVentas) {
        new Chart(ctxVentas, {
            type: 'bar',
            data: {
                labels: {!! json_encode(!empty($labelsMes) ? $labelsMes : ['Sin ventas']) !!},
                datasets: [{
                    label: 'Ventas (S/)',
                    data: {!! json_encode(!empty($dataVentas) ? $dataVentas : [0]) !!},
                    backgroundColor: 'rgba(37, 99, 235, 0.7)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 1.5,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return 'S/ ' + val; }
                        }
                    }
                }
            }
        });
    }

    // 2. Gráfico de Demanda por Tour
    const ctxTours = document.getElementById('chartTours');
    if (ctxTours) {
        new Chart(ctxTours, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(!empty($labelsTours) ? $labelsTours : ['Sin tours']) !!},
                datasets: [{
                    data: {!! json_encode(!empty($dataTours) ? $dataTours : [0]) !!},
                    backgroundColor: [
                        '#2563eb',
                        '#059669',
                        '#d97706',
                        '#dc2626',
                        '#7c3aed',
                        '#0891b2'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    }
                }
            }
        });
    }
});
</script>
@endsection
