@extends('layouts.app')

@section('title', 'Mi Portal — 18 Grados Sur')
@section('page_title', 'Mi portal')

@section('styles')
<style>
    .hero-portal {
        border-radius: 1rem; color: #fff; padding: 1.75rem; position: relative; overflow: hidden;
        background:
            radial-gradient(circle at 90% 10%, rgba(224, 168, 0, .25), transparent 40%),
            linear-gradient(135deg, var(--brand-primary), var(--brand-sidebar));
    }
    .hero-portal h2 { font-weight: 800; }
    .stat-card { border: 0; border-radius: .9rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); height: 100%; }
    .stat-icon {
        width: 48px; height: 48px; border-radius: .75rem; display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .stat-valor { font-size: 1.75rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .viaje-fecha {
        width: 56px; text-align: center; border-radius: .6rem; background: rgba(var(--brand-primary-rgb), .08);
        color: var(--brand-primary); padding: .35rem 0; flex-shrink: 0;
    }
    .viaje-fecha .dia { font-size: 1.35rem; font-weight: 800; line-height: 1; }
    .viaje-fecha .mes { font-size: .65rem; font-weight: 700; text-transform: uppercase; color: #b45309; }
</style>
@endsection

@section('content')
@php
    $estados = [
        'VIGENTE' => ['Por aceptar', 'bg-info text-dark'],
        'ACEPTADA' => ['Aceptada', 'bg-success'],
        'NO_ACEPTADA' => ['No aceptada', 'bg-secondary'],
        'CANCELADA' => ['Cancelada', 'bg-secondary'],
        'SIN_DISPONIBILIDAD' => ['Sin cupos', 'bg-danger'],
    ];
    $porAceptar = $cotizaciones->where('estado', 'VIGENTE')->count();
    $porPagar = $cotizaciones->where('estado', 'ACEPTADA')->filter(fn ($c) => !$c->reserva)->count();
    $proximos = $reservas->where('estado', 'CONFIRMADA')->take(3);
@endphp

<div class="hero-portal mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <small class="text-gold fw-bold text-uppercase" style="letter-spacing:.08em;">Portal del cliente</small>
            <h2 class="mb-1">¡Hola, {{ Auth::user()->persona->nombres ?? 'viajero' }}!</h2>
            <p class="mb-0 text-white-50">Aquí tienes tus cotizaciones, pagos y boletos de viaje.</p>
        </div>
        <a href="{{ route('portal.tours') }}" class="btn btn-gold px-4">
            <i class="bi bi-compass me-1"></i> Explorar tours
        </a>
    </div>
</div>

@if($porAceptar > 0 || $porPagar > 0)
<div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <i class="bi bi-bell-fill me-2"></i>
        @if($porAceptar > 0)
            Tienes <strong>{{ $porAceptar }} {{ $porAceptar == 1 ? 'cotización por aceptar' : 'cotizaciones por aceptar' }}</strong>{{ $porPagar > 0 ? ' y ' : '.' }}
        @endif
        @if($porPagar > 0)
            <strong>{{ $porPagar }} {{ $porPagar == 1 ? 'cotización pendiente de pago' : 'cotizaciones pendientes de pago' }}</strong>.
        @endif
    </div>
    <a href="{{ route('portal.cotizaciones') }}" class="btn btn-sm btn-dark">Revisar ahora <i class="bi bi-arrow-right ms-1"></i></a>
</div>
@endif

<!-- Resumen -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('portal.cotizaciones') }}" class="text-decoration-none">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: rgba(var(--brand-primary-rgb), .1); color: var(--brand-primary);"><i class="bi bi-receipt"></i></div>
                    <div>
                        <div class="text-muted small">Mis cotizaciones</div>
                        <div class="stat-valor">{{ $totalCotizaciones }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="{{ route('portal.reservas') }}" class="text-decoration-none">
            <div class="card stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: rgba(5, 150, 105, .1); color: #059669;"><i class="bi bi-bookmark-check"></i></div>
                    <div>
                        <div class="text-muted small">Viajes confirmados</div>
                        <div class="stat-valor">{{ $totalReservas }}</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="card stat-card">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon" style="background: var(--brand-gold-soft); color: #b45309;"><i class="bi bi-wallet2"></i></div>
                <div>
                    <div class="text-muted small">Total en viajes</div>
                    <div class="stat-valor">S/ {{ number_format($totalInvertido, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Próximos viajes -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-geo-alt-fill text-gold me-2"></i>Mis próximos viajes</span>
                <a href="{{ route('portal.reservas') }}" class="small text-decoration-none fw-semibold">Ver todos</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($proximos as $r)
                    @php $salida = $r->detalles->pluck('salida')->filter(fn ($s) => $s && $s->fecha)->sortBy('fecha')->first(); @endphp
                    <div class="list-group-item p-3 d-flex gap-3 align-items-center">
                        <div class="viaje-fecha">
                            @if($salida)
                                <div class="dia">{{ $salida->fecha->format('d') }}</div>
                                <div class="mes">{{ $salida->fecha->locale('es')->translatedFormat('M') }}</div>
                            @else
                                <i class="bi bi-calendar-event fs-4"></i>
                            @endif
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-dark text-truncate">{{ $r->detalles->first()->producto->nombre ?? 'Tour' }}</div>
                            <small class="text-muted">{{ $r->codigo }} · {{ $r->detalles->sum('cantidad_pasajeros') }} pasajero(s)</small>
                        </div>
                        <a href="{{ route('portal.voucher', $r->id_reserva) }}" class="btn btn-sm btn-outline-primary" title="Descargar boleto">
                            <i class="bi bi-ticket-perforated"></i><span class="d-none d-sm-inline ms-1">Boleto</span>
                        </a>
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-airplane fs-2 d-block mb-2 text-gold"></i>
                        Aún no tienes viajes confirmados.
                        <div class="mt-3"><a href="{{ route('portal.tours') }}" class="btn btn-sm btn-outline-primary">Ver tours en Tacna</a></div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Cotizaciones recientes -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt text-primary me-2"></i>Cotizaciones recientes</span>
                <a href="{{ route('portal.cotizaciones') }}" class="small text-decoration-none fw-semibold">Ver todas</a>
            </div>
            <div class="list-group list-group-flush">
                @forelse($cotizaciones as $c)
                    @php [$eTexto, $eClase] = $estados[$c->estado] ?? [$c->estado, 'bg-secondary']; @endphp
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="min-w-0">
                                <span class="fw-bold text-primary">{{ $c->codigo }}</span>
                                <span class="badge {{ $eClase }} ms-1">{{ $eTexto }}</span>
                                @if($c->reserva)<span class="badge bg-primary ms-1">Reservada</span>@endif
                                <div class="small text-muted text-truncate">{{ $c->detalles->first()->producto->nombre ?? 'Tour' }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold text-dark">S/ {{ number_format($c->total, 2) }}</div>
                                @if($c->estado === 'VIGENTE')
                                    <small class="text-muted">vence {{ $c->vigencia_hasta ? $c->vigencia_hasta->format('d/m') : '-' }}</small>
                                @endif
                            </div>
                        </div>
                        @if($c->estado === 'VIGENTE')
                            <div class="mt-2 d-flex justify-content-end gap-2">
                                <a href="{{ route('cotizaciones.pdf', $c->id_cotizacion) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                                <form action="{{ route('portal.aceptar', $c->id_cotizacion) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" data-confirm="Después podrás pagar el anticipo para asegurar tus cupos." data-confirm-title="¿Aceptar esta cotización?" data-confirm-ok="Sí, aceptar" data-confirm-variant="success">
                                        <i class="bi bi-check2"></i> Aceptar
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No tienes cotizaciones todavía.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
