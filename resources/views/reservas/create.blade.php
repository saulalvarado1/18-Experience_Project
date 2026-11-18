@extends('layouts.app')

@section('title', 'Nueva reserva — 18 Grados Sur')
@section('page_title', 'Nueva reserva')

@section('styles')
<style>
    .resumen-dato small { display: block; color: #64748b; font-size: .75rem; }
    .resumen-dato span { font-weight: 700; color: #0f172a; }
</style>
@endsection

@section('content')
@php
    $titular = $cotizacion->cliente->persona;
    $simbolo = $cotizacion->moneda === 'USD' ? 'US$' : 'S/';
    $items = $cotizacion->detalles->map(fn ($det) => [
        'clave' => $det->id_detalle_cotizacion,
        'nombre' => $det->producto->nombre ?? 'Servicio',
        'salida' => $det->salida,
        'cantidad' => (int) $det->cantidad_pasajeros,
        'punto_recojo' => $puntoRecojoDefecto,
        'pasajeros' => [],
    ])->all();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Nueva reserva</h2>
        <p class="text-muted mb-0">Registra a todos los pasajeros de cada servicio y su punto de recojo para confirmar la reserva.</p>
    </div>
    <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver a reservas</a>
</div>

<!-- Resumen de la cotización -->
<div class="card servicio-card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="fw-bold text-primary fs-5">{{ $cotizacion->codigo }}</div>
                <div class="text-dark">{{ $titular->nombres }} {{ $titular->apellidos }}</div>
                <small class="text-muted">{{ $titular->tipo_documento }} {{ $titular->numero_documento }}</small>
            </div>
            <div class="col-6 col-md-2 resumen-dato"><small>Total contratado</small><span>{{ $simbolo }} {{ number_format($cotizacion->total, 2) }}</span></div>
            <div class="col-6 col-md-2 resumen-dato"><small>Pagos validados</small><span class="text-success">S/ {{ number_format($resumen['validado'], 2) }}</span></div>
            <div class="col-6 col-md-2 resumen-dato"><small>Saldo pendiente</small><span>S/ {{ number_format($resumen['saldo'], 2) }}</span></div>
            <div class="col-6 col-md-2 resumen-dato">
                <small>Anticipo requerido ({{ rtrim(rtrim(number_format($resumen['porcentaje'], 2), '0'), '.') }}%)</small>
                <span>S/ {{ number_format($resumen['anticipo'], 2) }}</span>
                <i class="bi bi-check-circle-fill text-success ms-1" title="Anticipo cubierto"></i>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('reservas.store') }}" id="formReserva">
    @csrf
    <input type="hidden" name="id_cotizacion" value="{{ $cotizacion->id_cotizacion }}">

    @include('reservas._servicios', ['items' => $items, 'titular' => $titular])

    <div class="card servicio-card border-0 shadow-sm mb-4">
        <div class="card-body">
            <label class="form-label small fw-semibold" for="observacion">Observaciones de la reserva (opcional)</label>
            <textarea name="observacion" id="observacion" rows="2" class="form-control" maxlength="500">{{ old('observacion') }}</textarea>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
        <a href="{{ route('reservas.index') }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-gold px-4"
            data-confirm="Se registrarán los pasajeros y se ocuparán los cupos de cada salida."
            data-confirm-title="¿Confirmar la reserva?" data-confirm-ok="Sí, confirmar" data-confirm-variant="success">
            <i class="bi bi-check2-circle me-1"></i> Confirmar reserva
        </button>
    </div>
</form>
@endsection
