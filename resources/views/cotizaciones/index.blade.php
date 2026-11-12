@extends('layouts.app')

@section('title', 'Cotizaciones — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Gestión de Cotizaciones (CU15–CU17)</h2>
        <p class="text-muted mb-0">Emisión de cotizaciones comerciales, exportación en PDF corporativo y seguimiento de estado.</p>
    </div>
    <div>
        <a href="{{ route('cotizaciones.create') }}" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Nueva Cotización
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('cotizaciones.index') }}" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="termino" class="form-control" placeholder="Buscar por código COT, documento o cliente..." value="{{ $filtros['termino'] ?? '' }}">
                </div>
            </div>
            <div class="col-md-4">
                <select name="estado" class="form-select">
                    <option value="TODOS" {{ ($filtros['estado'] ?? '') === 'TODOS' ? 'selected' : '' }}>Todos los estados</option>
                    <option value="VIGENTE" {{ ($filtros['estado'] ?? '') === 'VIGENTE' ? 'selected' : '' }}>VIGENTE</option>
                    <option value="ACEPTADA" {{ ($filtros['estado'] ?? '') === 'ACEPTADA' ? 'selected' : '' }}>ACEPTADA</option>
                    <option value="NO_ACEPTADA" {{ ($filtros['estado'] ?? '') === 'NO_ACEPTADA' ? 'selected' : '' }}>NO ACEPTADA</option>
                    <option value="CANCELADA" {{ ($filtros['estado'] ?? '') === 'CANCELADA' ? 'selected' : '' }}>CANCELADA</option>
                    <option value="SIN_DISPONIBILIDAD" {{ ($filtros['estado'] ?? '') === 'SIN_DISPONIBILIDAD' ? 'selected' : '' }}>SIN DISPONIBILIDAD</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Emisión / Vigencia</th>
                    <th>Canal / Atendido</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $c)
                <tr>
                    <td class="fw-bold text-primary">{{ $c->codigo }}</td>
                    <td>
                        <div class="fw-semibold">{{ $c->cliente->persona->nombreCompleto() }}</div>
                        <small class="text-muted">{{ $c->cliente->persona->tipo_documento }}: {{ $c->cliente->persona->numero_documento }}</small>
                    </td>
                    <td>
                        <div>{{ $c->fecha_cotizacion ? $c->fecha_cotizacion->format('d/m/Y') : '-' }}</div>
                        <small class="text-muted">Vence: {{ $c->vigencia_hasta ? $c->vigencia_hasta->format('d/m/Y') : 'Sin fecha' }}</small>
                    </td>
                    <td>
                        @if(($c->usuarioCounter->rol->nombre ?? '') === 'CLIENTE' || str_contains($c->observacion ?? '', 'cliente') || str_contains($c->observacion ?? '', 'línea'))
                            <span class="badge bg-light text-primary border" title="Solicitada directamente por el turista desde el portal"><i class="bi bi-globe"></i> Portal Web</span>
                        @else
                            <span class="text-secondary"><i class="bi bi-person-badge"></i> {{ $c->usuarioCounter->nombre_usuario ?? 'Oficina' }}</span>
                        @endif
                    </td>
                    <td class="fw-bold text-success">{{ $c->moneda }} {{ number_format($c->total, 2) }}</td>
                    <td>
                        @if($c->estado === 'ACEPTADA')
                            <span class="badge bg-success">ACEPTADA</span>
                        @elseif($c->estado === 'VIGENTE')
                            <span class="badge bg-info text-dark">VIGENTE</span>
                        @elseif($c->estado === 'SIN_DISPONIBILIDAD')
                            <span class="badge bg-danger">SIN CUPO</span>
                        @else
                            <span class="badge bg-secondary">{{ $c->estado }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('cotizaciones.pdf', $c->id_cotizacion) }}" class="btn btn-sm btn-outline-danger" title="Descargar PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>
                            @php
                                $telCliente = preg_replace('/[^0-9]/', '', $c->cliente->persona->telefono ?? '');
                                $msgWs = urlencode("Hola " . ($c->cliente->persona->nombres ?? 'estimado cliente') . ", 18 Grados Sur te saluda. Te compartimos tu cotización " . $c->codigo . " por un total de S/ " . number_format($c->total, 2) . ".");
                            @endphp
                            @if(!empty($telCliente))
                            <a href="https://wa.me/51{{ $telCliente }}?text={{ $msgWs }}" target="_blank" class="btn btn-sm btn-outline-success" title="Enviar por WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                            @endif
                            @if($c->estado === 'VIGENTE')
                            <form action="{{ route('cotizaciones.estado', $c->id_cotizacion) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="estado" value="ACEPTADA">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar como Aceptada por el Cliente" onclick="return confirm('¿Aceptar cotización y verificar cupos?')">
                                    <i class="bi bi-check2-circle"></i>
                                </button>
                            </form>
                            <form action="{{ route('cotizaciones.estado', $c->id_cotizacion) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="estado" value="CANCELADA">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="Cancelar Cotización" onclick="return confirm('¿Cancelar esta cotización?')">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No se encontraron cotizaciones con los filtros aplicados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cotizaciones->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $cotizaciones->links() }}
    </div>
    @endif
</div>
@endsection
