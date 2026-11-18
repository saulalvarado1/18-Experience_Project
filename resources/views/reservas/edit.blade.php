@extends('layouts.app')

@section('title', 'Modificar reserva — 18 Grados Sur')
@section('page_title', 'Modificar reserva')

@section('content')
@php
    $titular = $reserva->cotizacion->cliente->persona;
    $simbolo = $reserva->moneda === 'USD' ? 'US$' : 'S/';
    $items = $reserva->detalles->map(fn ($det) => [
        'clave' => $det->id_detalle_reserva,
        'nombre' => $det->producto->nombre ?? 'Servicio',
        'salida' => $det->salida,
        'cantidad' => (int) $det->cantidad_pasajeros,
        'punto_recojo' => $det->punto_recojo,
        'pasajeros' => $det->pasajeros->sortBy('id_reserva_pasajero')->values()->map(fn ($p) => [
            'tipo_documento' => $p->persona->tipo_documento ?? 'DNI',
            'numero_documento' => $p->persona->numero_documento ?? '',
            'nombres' => $p->persona->nombres ?? '',
            'apellidos' => $p->persona->apellidos ?? '',
            'fecha_nacimiento' => $p->persona?->fecha_nacimiento?->format('Y-m-d'),
            'restriccion_alimentaria' => $p->restriccion_alimentaria,
            'condicion_particular' => $p->condicion_particular,
        ])->all(),
    ])->all();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Modificar reserva {{ $reserva->codigo }}</h2>
        <p class="text-muted mb-0">
            {{ $titular->nombres }} {{ $titular->apellidos }} · Total {{ $simbolo }} {{ number_format($reserva->total_contratado, 2) }}.
            Puedes cambiar puntos de recojo, pasajeros y observaciones; la cantidad de pasajeros y el total se mantienen.
        </p>
    </div>
    <a href="{{ route('reservas.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver a reservas</a>
</div>

<form method="POST" action="{{ route('reservas.update', $reserva->id_reserva) }}" id="formModificarReserva">
    @csrf

    @include('reservas._servicios', ['items' => $items, 'titular' => $titular])

    <div class="card servicio-card border-0 shadow-sm mb-4">
        <div class="card-body">
            <label class="form-label small fw-semibold" for="observacion">Observaciones de la reserva (opcional)</label>
            <textarea name="observacion" id="observacion" rows="2" class="form-control" maxlength="500">{{ old('observacion', $reserva->observacion) }}</textarea>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-end gap-2 mb-4">
        <a href="{{ route('reservas.index') }}" class="btn btn-light">Cancelar</a>
        <button type="submit" class="btn btn-primary px-4"
            data-confirm="Se reemplazarán los pasajeros y puntos de recojo con los datos de este formulario."
            data-confirm-title="¿Guardar los cambios?" data-confirm-ok="Sí, guardar">
            <i class="bi bi-save me-1"></i> Guardar cambios
        </button>
    </div>
</form>
@endsection
