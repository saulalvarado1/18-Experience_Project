@extends('layouts.app')

@section('title', 'Mis Reservas y Boletos — 18 Grados Sur')
@section('page_title', 'Mis reservas')

@section('styles')
<style>
    .reserva-card { border: 0; border-radius: .9rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); overflow: hidden; }
    .reserva-card.CANCELADA { opacity: .7; }
    .reserva-lateral {
        background: var(--brand-primary); color: #fff;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        padding: 1.25rem; text-align: center; min-width: 140px;
    }
    .reserva-card.CANCELADA .reserva-lateral { background: #64748b; }
    .reserva-lateral .dia { font-size: 2.2rem; font-weight: 800; line-height: 1; }
    .reserva-lateral .mes { text-transform: uppercase; letter-spacing: .1em; font-size: .8rem; color: var(--brand-gold); font-weight: 700; }
    .reserva-codigo { font-weight: 700; color: var(--brand-primary); letter-spacing: .02em; }
    .tramo + .tramo { border-top: 1px dashed #e2e8f0; padding-top: .6rem; margin-top: .6rem; }
    @media (max-width: 575.98px) {
        .reserva-lateral { flex-direction: row; gap: .75rem; min-width: 0; padding: .75rem 1rem; justify-content: flex-start; }
        .reserva-lateral .dia { font-size: 1.6rem; }
    }
</style>
@endsection

@section('content')
@php
    $estados = [
        'CONFIRMADA' => ['Confirmada', 'bg-success'],
        'COMPLETADA' => ['Viaje realizado', 'bg-primary'],
        'CANCELADA' => ['Cancelada', 'bg-secondary'],
    ];
@endphp

<div class="mb-4">
    <h2 class="fw-bold text-dark mb-1">Mis reservas</h2>
    <p class="text-muted mb-0">Tus viajes confirmados. Descarga el boleto y preséntalo el día de la salida.</p>
</div>

@forelse($reservas as $r)
    @php
        [$estadoTexto, $estadoClase] = $estados[$r->estado] ?? [$r->estado, 'bg-secondary'];
        $primeraSalida = $r->detalles->pluck('salida')->filter(fn ($s) => $s && $s->fecha)->sortBy('fecha')->first();
        $pasajeros = $r->detalles->sum('cantidad_pasajeros');
    @endphp
    <div class="card reserva-card {{ $r->estado }} mb-3">
        <div class="d-flex flex-column flex-sm-row">
            <div class="reserva-lateral">
                @if($primeraSalida)
                    <div class="dia">{{ $primeraSalida->fecha->format('d') }}</div>
                    <div>
                        <div class="mes">{{ $primeraSalida->fecha->locale('es')->translatedFormat('M Y') }}</div>
                        @if($primeraSalida->hora)<small class="opacity-75">{{ substr($primeraSalida->hora, 0, 5) }} h</small>@endif
                    </div>
                @else
                    <i class="bi bi-calendar-event fs-2"></i>
                    <small class="opacity-75">Fecha por coordinar</small>
                @endif
            </div>

            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <span class="reserva-codigo">{{ $r->codigo }}</span>
                        <span class="badge {{ $estadoClase }} ms-2">{{ $estadoTexto }}</span>
                    </div>
                    <span class="fw-bold text-dark">{{ $r->moneda === 'USD' ? 'US$' : 'S/' }} {{ number_format($r->total_contratado, 2) }}</span>
                </div>

                @foreach($r->detalles as $det)
                    <div class="tramo">
                        <div class="fw-semibold text-dark"><i class="bi bi-geo-alt-fill text-gold me-1"></i>{{ $det->producto->nombre ?? 'Tour' }}</div>
                        <small class="text-muted">
                            {{ $det->salida && $det->salida->fecha ? $det->salida->fecha->format('d/m/Y') : 'Fecha por coordinar' }}
                            {{ $det->salida && $det->salida->hora ? '· ' . substr($det->salida->hora, 0, 5) . ' h' : '' }}
                            · {{ $det->cantidad_pasajeros }} {{ $det->cantidad_pasajeros == 1 ? 'pasajero' : 'pasajeros' }}
                        </small>
                    </div>
                @endforeach

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3 pt-3 border-top">
                    <small class="text-muted">
                        Reservado el {{ $r->fecha_reserva ? $r->fecha_reserva->format('d/m/Y') : '-' }} · {{ $pasajeros }} {{ $pasajeros == 1 ? 'pasajero' : 'pasajeros' }}
                    </small>
                    @if($r->estado !== 'CANCELADA')
                        <a href="{{ route('portal.voucher', $r->id_reserva) }}" class="btn btn-sm btn-gold">
                            <i class="bi bi-ticket-perforated me-1"></i> Descargar boleto PDF
                        </a>
                    @else
                        <small class="text-muted"><i class="bi bi-x-circle me-1"></i>Reserva cancelada</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card reserva-card">
        <div class="card-body text-center py-5">
            <i class="bi bi-ticket-perforated fs-1 text-gold d-block mb-3"></i>
            <h5 class="fw-bold">Aún no tienes reservas</h5>
            <p class="text-muted mb-4">Cuando pagues el anticipo de una cotización, confirmaremos tu reserva y aparecerá aquí.</p>
            <a href="{{ route('portal.cotizaciones') }}" class="btn btn-primary"><i class="bi bi-receipt me-1"></i> Ver mis cotizaciones</a>
        </div>
    </div>
@endforelse

@if($reservas->hasPages())
    <div class="mt-3">{{ $reservas->links() }}</div>
@endif
@endsection
