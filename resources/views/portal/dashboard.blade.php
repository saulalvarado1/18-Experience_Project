@extends('layouts.app')

@section('title', 'Mi Portal — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-person-circle text-primary me-2"></i>Bienvenido, {{ Auth::user()->persona->nombres }}</h2>
        <p class="text-muted mb-0">Portal de Autoconsulta del Turista — Consulta tus presupuestos, reservas y boletos de viaje.</p>
    </div>
    <div>
        <a href="{{ route('portal.tours') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-compass me-1"></i> Explorar Tours
        </a>
    </div>
</div>

<!-- Tarjetas de Resumen para el Cliente -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-6 opacity-75">Mis Cotizaciones</div>
                        <div class="fs-2 fw-bold">{{ $totalCotizaciones }}</div>
                    </div>
                    <i class="bi bi-file-earmark-spreadsheet fs-1 opacity-50"></i>
                </div>
                <a href="{{ route('portal.cotizaciones') }}" class="btn btn-sm btn-light mt-3 w-100 fw-semibold text-primary">
                    Ver Presupuestos <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-success text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-6 opacity-75">Mis Viajes Confirmados</div>
                        <div class="fs-2 fw-bold">{{ $totalReservas }}</div>
                    </div>
                    <i class="bi bi-calendar-check fs-1 opacity-50"></i>
                </div>
                <a href="{{ route('portal.reservas') }}" class="btn btn-sm btn-light mt-3 w-100 fw-semibold text-success">
                    Ver Mis Boletos <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-dark text-white">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-6 opacity-75">Total Invertido en Tours</div>
                        <div class="fs-2 fw-bold text-warning">S/ {{ number_format($totalInvertido, 2) }}</div>
                    </div>
                    <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                </div>
                <a href="{{ route('portal.tours') }}" class="btn btn-sm btn-outline-light mt-3 w-100 fw-semibold">
                    Planificar Nuevo Tour <i class="bi bi-plus-circle ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Próximos Viajes Confirmados -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-geo-alt-fill text-danger me-2"></i>Mis Próximos Viajes</span>
                <a href="{{ route('portal.reservas') }}" class="small text-decoration-none">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($reservas->take(3) as $r)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-primary">{{ $r->codigo }}</span>
                                <span class="badge bg-success">{{ $r->estado }}</span>
                            </div>
                            @foreach($r->detalles as $det)
                                <div class="fw-semibold text-dark">{{ $det->producto->nombre ?? 'Tour' }}</div>
                                <div class="small text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    {{ $det->salida && $det->salida->fecha ? $det->salida->fecha->format('d/m/Y') : 'Por definir' }}
                                    — {{ $det->salida->hora ?? '' }}
                                    ({{ $det->cantidad_pasajeros }} pasajero{{ $det->cantidad_pasajeros > 1 ? 's' : '' }})
                                </div>
                            @endforeach
                            <div class="mt-2 text-end">
                                <a href="{{ route('portal.voucher', $r->id_reserva) }}" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Boleto
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-airplane fs-2 d-block mb-2"></i>
                            Aún no tienes viajes confirmados.
                            <div class="mt-2">
                                <a href="{{ route('portal.tours') }}" class="btn btn-sm btn-outline-primary">Ver destinos en Tacna</a>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Cotizaciones Recientes -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt text-primary me-2"></i>Cotizaciones y Presupuestos</span>
                <a href="{{ route('portal.cotizaciones') }}" class="small text-decoration-none">Ver todas</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($cotizaciones as $c)
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark">{{ $c->codigo }}</span>
                                <span class="badge {{ $c->estado === 'ACEPTADA' ? 'bg-success' : ($c->estado === 'VIGENTE' ? 'bg-info text-dark' : 'bg-secondary') }}">
                                    {{ $c->estado }}
                                </span>
                            </div>
                            <div class="text-muted small">
                                Total: <strong class="text-success">{{ $c->moneda }} {{ number_format($c->total, 2) }}</strong> | Vence: {{ $c->vigencia_hasta ? $c->vigencia_hasta->format('d/m/Y') : '-' }}
                            </div>
                            <div class="mt-2 d-flex justify-content-end gap-2">
                                <a href="{{ route('cotizaciones.pdf', $c->id_cotizacion) }}" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-file-earmark-pdf"></i> PDF
                                </a>
                                @if($c->estado === 'VIGENTE')
                                    <form action="{{ route('portal.aceptar', $c->id_cotizacion) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Deseas aceptar esta cotización?')">
                                            Aceptar Presupuesto
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            No tienes cotizaciones registradas.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
