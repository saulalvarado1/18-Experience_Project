@extends('layouts.app')

@section('title', 'Catálogo de Tours en Tacna — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-compass text-primary me-2"></i>Catálogo de Tours en Tacna</h2>
        <p class="text-muted mb-0">Explora las mejores rutas turísticas del sur del Perú y solicita tu presupuesto al instante.</p>
    </div>
</div>

<div class="row g-4">
    @forelse($productos as $p)
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-primary">{{ $p->codigo }}</span>
                    <span class="badge bg-warning text-dark">{{ $p->tipo_producto }}</span>
                </div>
                <h5 class="fw-bold mb-0 mt-2">{{ $p->nombre }}</h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted small mb-3">{{ $p->descripcion ?? 'Disfruta de una experiencia turística única con guías certificados y movilidad turística.' }}</p>

                    @if($p->destinos && $p->destinos->count() > 0)
                    <div class="mb-3">
                        <small class="fw-bold text-dark d-block mb-1">Destinos incluidos:</small>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($p->destinos as $d)
                                <span class="badge bg-light text-dark border">{{ $d->nombre }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <small class="fw-bold text-dark d-block mb-1">Próximas salidas programadas:</small>
                        @if($p->salidas && $p->salidas->count() > 0)
                            <div class="small text-muted">
                                @foreach($p->salidas->take(2) as $s)
                                    <div><i class="bi bi-calendar3 me-1 text-primary"></i> {{ $s->fecha ? $s->fecha->format('d/m/Y') : '' }} a las {{ $s->hora }}</div>
                                @endforeach
                            </div>
                        @else
                            <small class="text-muted">Salidas diarias previa coordinación.</small>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-top mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Precio por persona:</small>
                        <span class="fs-4 fw-bold text-success">{{ $p->moneda }} {{ number_format($p->precio_base, 2) }}</span>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm btn-cotizar-tour shadow-sm"
                        data-id="{{ $p->id_producto }}"
                        data-nombre="{{ $p->nombre }}"
                        data-precio="{{ $p->precio_base }}">
                        <i class="bi bi-receipt me-1"></i> Cotizar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-map fs-1 d-block mb-2"></i>
        No hay tours registrados en el catálogo en este momento.
    </div>
    @endforelse
</div>

<!-- Modal Solicitar Cotización -->
<div class="modal fade" id="modalSolicitarCotizacion" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('portal.solicitar') }}">
            @csrf
            <input type="hidden" name="id_producto" id="modalCotizarIdProducto">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-receipt me-2"></i>Solicitar Cotización de Tour</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tour Seleccionado</label>
                        <input type="text" id="modalCotizarNombreTour" class="form-control bg-light fw-bold" readonly>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">N.º de Pasajeros <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad_pasajeros" id="modalCotizarPasajeros" class="form-control" value="2" min="1" max="20" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Presupuesto Estimado</label>
                            <input type="text" id="modalCotizarTotalEstimado" class="form-control bg-light fw-bold text-success" readonly value="S/ 0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comentarios o requerimientos especiales</label>
                        <textarea name="observacion" rows="3" class="form-control" placeholder="¿Viajas con niños, adultos mayores o requieres recojo especial?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Generar Mi Cotización</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarCotizacion'));
    const inputId = document.getElementById('modalCotizarIdProducto');
    const inputNombre = document.getElementById('modalCotizarNombreTour');
    const inputPasajeros = document.getElementById('modalCotizarPasajeros');
    const inputTotal = document.getElementById('modalCotizarTotalEstimado');

    let precioBase = 0;

    function recalcular() {
        const cant = parseInt(inputPasajeros.value) || 1;
        inputTotal.value = `S/ ${(cant * precioBase).toFixed(2)}`;
    }

    inputPasajeros.addEventListener('input', recalcular);

    document.querySelectorAll('.btn-cotizar-tour').forEach(btn => {
        btn.addEventListener('click', function () {
            inputId.value = this.getAttribute('data-id');
            inputNombre.value = this.getAttribute('data-nombre');
            precioBase = parseFloat(this.getAttribute('data-precio')) || 0;
            recalcular();
            modal.show();
        });
    });
});
</script>
@endsection
