@extends('layouts.app')

@section('title', 'Mis Reservas y Boletos — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-check text-success me-2"></i>Mis Reservas y Boletos de Viaje</h2>
        <p class="text-muted mb-0">Consulta tus itinerarios confirmados y descarga tus vouchers oficiales para abordar las unidades.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código Reserva</th>
                    <th>Fecha Reserva</th>
                    <th>Tours e Itinerario</th>
                    <th>Total Contratado</th>
                    <th>Estado</th>
                    <th class="text-end">Boleto / Voucher</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservas as $r)
                <tr>
                    <td class="fw-bold text-primary">{{ $r->codigo }}</td>
                    <td>{{ $r->fecha_reserva ? $r->fecha_reserva->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        @foreach($r->detalles as $det)
                            <div class="fw-semibold text-dark">{{ $det->producto->nombre ?? 'Tour' }}</div>
                            <div class="small text-muted">
                                <i class="bi bi-clock me-1"></i>
                                Salida: <strong>{{ $det->salida && $det->salida->fecha ? $det->salida->fecha->format('d/m/Y') : 'Por coordinar' }}</strong>
                                {{ $det->salida ? $det->salida->hora : '' }} | {{ $det->cantidad_pasajeros }} pasajero{{ $det->cantidad_pasajeros > 1 ? 's' : '' }}
                            </div>
                        @endforeach
                    </td>
                    <td class="fw-bold text-success">{{ $r->moneda }} {{ number_format($r->total_contratado, 2) }}</td>
                    <td>
                        @if($r->estado === 'CONFIRMADA')
                            <span class="badge bg-success">CONFIRMADA</span>
                        @elseif($r->estado === 'COMPLETADA')
                            <span class="badge bg-primary">COMPLETADA</span>
                        @else
                            <span class="badge bg-secondary">CANCELADA</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('portal.voucher', $r->id_reserva) }}" class="btn btn-sm btn-success shadow-sm">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Descargar Boleto PDF
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-ticket-perforated fs-2 d-block mb-2"></i>
                        No tienes reservas registradas en este momento.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reservas->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $reservas->links() }}
    </div>
    @endif
</div>
@endsection
