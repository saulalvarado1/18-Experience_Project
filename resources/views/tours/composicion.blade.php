@extends('layouts.app')

@section('title', 'Composición del tour — 18 Grados Sur')
@section('page_title', 'Composición del tour')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">{{ $producto->nombre }}</h2>
        <p class="text-muted mb-0">
            <span class="badge bg-light text-dark border me-1">{{ $producto->codigo }}</span>
            Define los destinos que se visitan (en orden) y los servicios incluidos o adicionales.
        </p>
    </div>
    <a href="{{ route('tours.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Volver a tours</a>
</div>

<div class="row g-4">
    <!-- ================= Destinos ================= -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-pin-map text-gold me-2"></i>Destinos del itinerario
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tours.destinos.asignar', $producto->id_producto) }}" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold" for="id_destino">Destino</label>
                        <select name="id_destino" id="id_destino" class="form-select" required {{ $destinosDisponibles->isEmpty() ? 'disabled' : '' }}>
                            <option value="">-- Selecciona --</option>
                            @foreach($destinosDisponibles as $d)
                                <option value="{{ $d->id_destino }}" {{ old('id_destino') == $d->id_destino ? 'selected' : '' }}>{{ $d->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4 col-sm-2">
                        <label class="form-label small fw-semibold" for="orden_visita">Orden</label>
                        <input type="number" name="orden_visita" id="orden_visita" class="form-control" min="1" value="{{ old('orden_visita', $siguienteOrden) }}" required>
                    </div>
                    <div class="col-8 col-sm-4">
                        <button type="submit" class="btn btn-primary w-100" {{ $destinosDisponibles->isEmpty() ? 'disabled' : '' }}>
                            <i class="bi bi-plus-lg me-1"></i> Agregar
                        </button>
                    </div>
                    <div class="col-12">
                        <input type="text" name="observacion" class="form-control form-control-sm" maxlength="500" placeholder="Observación (opcional), ej: parada de 40 minutos">
                    </div>
                    @if($destinosDisponibles->isEmpty())
                        <div class="col-12 form-text">Todos los destinos activos ya están asignados.</div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Orden</th><th>Destino</th><th>Observación</th><th class="text-end">Acciones</th></tr>
                        </thead>
                        <tbody>
                            @forelse($producto->destinos as $d)
                                <tr>
                                    <td><span class="badge bg-primary rounded-pill">{{ $d->pivot->orden_visita }}</span></td>
                                    <td class="fw-semibold">{{ $d->nombre }}<div class="small text-muted">{{ $d->ubicacion }}</div></td>
                                    <td class="small text-muted">{{ $d->pivot->observacion ?: '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('tours.destinos.quitar', [$producto->id_producto, $d->id_destino]) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar destino"
                                                data-confirm="El destino {{ $d->nombre }} dejará de formar parte del itinerario." data-confirm-title="¿Quitar destino?" data-confirm-ok="Sí, quitar" data-confirm-variant="danger">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay destinos asignados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= Servicios ================= -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-bag-check text-gold me-2"></i>Servicios del producto
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('tours.servicios.asignar', $producto->id_producto) }}" class="row g-2 align-items-end mb-4" id="formServicio">
                    @csrf
                    <div class="col-sm-6">
                        <label class="form-label small fw-semibold" for="id_servicio">Servicio</label>
                        <select name="id_servicio" id="id_servicio" class="form-select" required {{ $serviciosDisponibles->isEmpty() ? 'disabled' : '' }}>
                            <option value="">-- Selecciona --</option>
                            @foreach($serviciosDisponibles as $s)
                                <option value="{{ $s->id_servicio }}" {{ old('id_servicio') == $s->id_servicio ? 'selected' : '' }}>{{ ucfirst(strtolower($s->nombre)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-sm-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="incluido" value="1" id="incluido" {{ old('incluido', '1') ? 'checked' : '' }}>
                            <label class="form-check-label small" for="incluido">Incluido</label>
                        </div>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="form-label small fw-semibold" for="precio_adicional">Precio adicional</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" name="precio_adicional" id="precio_adicional" class="form-control" min="0" step="0.01" value="{{ old('precio_adicional', '0.00') }}">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" name="observacion" class="form-control form-control-sm" maxlength="500" placeholder="Observación (opcional), ej: almuerzo típico">
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-primary w-100" {{ $serviciosDisponibles->isEmpty() ? 'disabled' : '' }}>
                            <i class="bi bi-plus-lg me-1"></i> Agregar
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Servicio</th><th>Condición</th><th>Observación</th><th class="text-end">Acciones</th></tr>
                        </thead>
                        <tbody>
                            @forelse($producto->servicios as $s)
                                <tr>
                                    <td class="fw-semibold">{{ ucfirst(strtolower($s->nombre)) }}</td>
                                    <td>
                                        @if($s->pivot->incluido)
                                            <span class="badge bg-success">Incluido</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Adicional S/ {{ number_format($s->pivot->precio_adicional, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $s->pivot->observacion ?: '—' }}</td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('tours.servicios.quitar', [$producto->id_producto, $s->id_servicio]) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Quitar servicio"
                                                data-confirm="El servicio dejará de ofrecerse con este producto." data-confirm-title="¿Quitar servicio?" data-confirm-ok="Sí, quitar" data-confirm-variant="danger">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay servicios asignados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Servicio incluido: el precio adicional es cero (SRS CU10, flujo 4a)
    const incluido = document.getElementById('incluido');
    const precio = document.getElementById('precio_adicional');
    const sincronizar = () => {
        precio.disabled = incluido.checked;
        if (incluido.checked) precio.value = '0.00';
    };
    incluido.addEventListener('change', sincronizar);
    sincronizar();
});
</script>
@endsection
