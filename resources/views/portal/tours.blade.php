@extends('layouts.app')

@section('title', 'Catálogo de Tours en Tacna — 18 Grados Sur')
@section('page_title', 'Catálogo de tours')

@section('styles')
<style>
    .tour-card { border: 0; border-radius: 1rem; overflow: hidden; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); transition: transform .15s ease, box-shadow .15s ease; }
    .tour-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(15, 23, 42, .12); }
    .tour-cabecera {
        position: relative; color: #fff; padding: 1.25rem 1.25rem 1.1rem; min-height: 120px;
        background:
            linear-gradient(180deg, rgba(10, 20, 48, .1), rgba(10, 20, 48, .75)),
            linear-gradient(135deg, var(--brand-primary), var(--brand-sidebar));
        display: flex; flex-direction: column; justify-content: flex-end;
    }
    .tour-cabecera::after {
        content: ''; position: absolute; inset: auto 0 0 0; height: 55%;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 120' preserveAspectRatio='none'%3E%3Cpath d='M0 120 L60 60 L95 85 L160 25 L215 75 L250 50 L320 100 L360 70 L400 95 L400 120Z' fill='%23e0a800' fill-opacity='.16'/%3E%3C/svg%3E") bottom/100% 100% no-repeat;
    }
    .tour-cabecera > * { position: relative; z-index: 1; }
    .tour-cabecera h5 { font-weight: 800; margin: 0; }
    .tour-tipo { position: absolute; top: .9rem; right: .9rem; z-index: 1; }
    .tour-precio { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1; }
    .salida-chip {
        display: inline-flex; align-items: center; gap: .3rem; font-size: .75rem;
        background: rgba(var(--brand-primary-rgb), .07); color: var(--brand-primary);
        border-radius: 2rem; padding: .2rem .6rem; margin: 0 .25rem .25rem 0;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Tours en Tacna y el sur del Perú</h2>
        <p class="text-muted mb-0">Elige un tour, indica cuántos viajan y recibe tu cotización al instante.</p>
    </div>
    <a href="{{ route('portal.cotizaciones') }}" class="btn btn-outline-primary"><i class="bi bi-receipt me-1"></i> Mis cotizaciones</a>
</div>

<div class="row g-4">
    @forelse($productos as $p)
    @php
        $salidasDisponibles = $p->salidas->take(6)->map(function ($s) {
            $disp = $s->disponibilidad->disponibles;
            return [
                'id' => $s->id_salida,
                'texto' => $s->fecha->locale('es')->translatedFormat('D d M') . ($s->hora ? ' · ' . substr($s->hora, 0, 5) . ' h' : '') . " ({$disp} cupos)",
                'disponibles' => (int) $disp,
            ];
        })->filter(fn ($s) => $s['disponibles'] > 0)->values();
        $simbolo = $p->moneda === 'USD' ? 'US$' : 'S/';
    @endphp
    <div class="col-md-6 col-xl-4">
        <div class="card tour-card h-100">
            <div class="tour-cabecera">
                <span class="badge bg-warning text-dark tour-tipo">{{ ucfirst(strtolower(str_replace('_', ' ', $p->tipo_producto))) }}</span>
                <small class="text-white-50">{{ $p->codigo }}{{ $p->duracion_dias ? ' · ' . $p->duracion_dias . ($p->duracion_dias == 1 ? ' día' : ' días') : '' }}</small>
                <h5>{{ $p->nombre }}</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="text-muted small mb-3">{{ $p->descripcion ?: 'Disfruta de una experiencia turística única con guías certificados y movilidad turística.' }}</p>

                @if($p->destinos->isNotEmpty())
                <div class="mb-3">
                    <small class="fw-semibold text-dark d-block mb-1"><i class="bi bi-pin-map me-1 text-gold"></i>Destinos</small>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($p->destinos as $d)
                            <span class="badge bg-light text-dark border fw-normal">{{ $d->nombre }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="mb-3">
                    <small class="fw-semibold text-dark d-block mb-1"><i class="bi bi-calendar3 me-1 text-gold"></i>Próximas salidas</small>
                    @forelse($salidasDisponibles->take(3) as $s)
                        <span class="salida-chip">{{ $s['texto'] }}</span>
                    @empty
                        <small class="text-muted">Fecha a coordinar con la agencia.</small>
                    @endforelse
                </div>

                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Desde, por persona</small>
                        <span class="tour-precio">{{ $simbolo }} {{ number_format($p->precio_base, 2) }}</span>
                        @if($p->minimo_pasajeros > 1)
                            <small class="d-block text-muted"><i class="bi bi-people me-1"></i>Mínimo {{ $p->minimo_pasajeros }} personas</small>
                        @endif
                    </div>
                    <button type="button" class="btn btn-gold btn-cotizar-tour"
                        data-id="{{ $p->id_producto }}"
                        data-nombre="{{ $p->nombre }}"
                        data-precio="{{ $p->precio_base }}"
                        data-minimo="{{ max(1, (int) $p->minimo_pasajeros) }}"
                        data-salidas='@json($salidasDisponibles)'>
                        <i class="bi bi-receipt me-1"></i> Cotizar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-map fs-1 d-block mb-2 text-gold"></i>
                No hay tours disponibles en este momento. Vuelve pronto.
            </div>
        </div>
    </div>
    @endforelse
</div>

<!-- Modal Solicitar Cotización -->
<div class="modal fade" id="modalSolicitarCotizacion" tabindex="-1" aria-labelledby="tituloCotizar">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('portal.solicitar') }}" class="w-100">
            @csrf
            <input type="hidden" name="id_producto" id="modalCotizarIdProducto">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="tituloCotizar"><i class="bi bi-receipt me-2 text-primary"></i>Solicitar cotización</h5>
                        <small class="text-muted" id="modalCotizarNombreTour"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" id="grupoSalida">
                        <label class="form-label fw-semibold small" for="modalCotizarSalida">Fecha de salida</label>
                        <select name="id_salida" id="modalCotizarSalida" class="form-select"></select>
                        <div class="form-text">Si no eliges una fecha, la agencia la coordinará contigo.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small" for="modalCotizarPasajeros">Pasajeros <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <button type="button" class="btn btn-outline-secondary" data-pasajeros="-1" aria-label="Menos">−</button>
                                <input type="number" name="cantidad_pasajeros" id="modalCotizarPasajeros" class="form-control text-center" value="2" min="1" max="20" required>
                                <button type="button" class="btn btn-outline-secondary" data-pasajeros="1" aria-label="Más">+</button>
                            </div>
                            <div class="form-text" id="modalCotizarMinimo"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Total estimado</label>
                            <div class="form-control bg-light fw-bold text-success" id="modalCotizarTotalEstimado">S/ 0.00</div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold small" for="modalCotizarObs">Comentarios (opcional)</label>
                        <textarea name="observacion" id="modalCotizarObs" rows="2" class="form-control" maxlength="500" placeholder="¿Viajas con niños, adultos mayores o necesitas recojo especial?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-gold"><i class="bi bi-check-circle me-1"></i> Generar mi cotización</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitarCotizacion'));
    const inputPasajeros = document.getElementById('modalCotizarPasajeros');
    const selectSalida = document.getElementById('modalCotizarSalida');
    const total = document.getElementById('modalCotizarTotalEstimado');
    let precioBase = 0;
    let minimo = 1;
    let salidas = [];

    function recalcular() {
        // Mínimo de pasajeros del tour (RN09 / CU15) y tope de cupos de la salida elegida
        let cant = Math.min(20, Math.max(minimo, parseInt(inputPasajeros.value) || minimo));
        const salida = salidas.find(s => String(s.id) === selectSalida.value);
        if (salida && cant > salida.disponibles) cant = Math.max(minimo, salida.disponibles);
        inputPasajeros.min = minimo;
        inputPasajeros.max = salida ? Math.min(20, salida.disponibles) : 20;
        if (String(cant) !== inputPasajeros.value) inputPasajeros.value = cant;
        total.textContent = `S/ ${(cant * precioBase).toFixed(2)}`;
    }

    inputPasajeros.addEventListener('input', recalcular);
    selectSalida.addEventListener('change', recalcular);
    document.querySelectorAll('[data-pasajeros]').forEach(b => b.addEventListener('click', () => {
        inputPasajeros.value = (parseInt(inputPasajeros.value) || 1) + parseInt(b.dataset.pasajeros);
        recalcular();
    }));

    document.querySelectorAll('.btn-cotizar-tour').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('modalCotizarIdProducto').value = this.dataset.id;
            document.getElementById('modalCotizarNombreTour').textContent = this.dataset.nombre;
            precioBase = parseFloat(this.dataset.precio) || 0;
            minimo = parseInt(this.dataset.minimo) || 1;
            document.getElementById('modalCotizarMinimo').textContent = minimo > 1 ? `Este tour requiere mínimo ${minimo} personas.` : '';
            // Solo fechas con cupos suficientes para el grupo mínimo
            salidas = JSON.parse(this.dataset.salidas || '[]').filter(s => s.disponibles >= minimo);

            selectSalida.innerHTML = '<option value="">Coordinar fecha con la agencia</option>'
                + salidas.map(s => `<option value="${s.id}">${s.texto}</option>`).join('');
            if (salidas.length) selectSalida.value = salidas[0].id;
            document.getElementById('grupoSalida').classList.toggle('d-none', salidas.length === 0);

            inputPasajeros.value = Math.max(2, minimo);
            recalcular();
            modal.show();
        });
    });
});
</script>
@endsection
