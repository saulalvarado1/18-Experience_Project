@extends('layouts.app')

@section('title', 'Catálogo de Tours en Tacna — 18 Grados Sur')
@section('page_title', 'Catálogo de tours')

@section('styles')
<style>
    .tour-card {
        border: 0;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(15, 23, 42, .06);
        transition: transform .25s ease, box-shadow .25s ease;
        background: #fff;
    }
    .tour-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, .12);
    }
    .tour-foto-wrapper {
        position: relative;
        height: 200px;
        overflow: hidden;
        background: #0f172a;
        cursor: pointer;
    }
    .tour-foto-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform .4s ease;
    }
    .tour-card:hover .tour-foto-img {
        transform: scale(1.08);
    }
    .tour-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0,0,0,.2) 0%, rgba(0,0,0,0) 40%, rgba(15,23,42,.85) 100%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1rem;
        color: #fff;
    }
    .tour-foto-caption {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: .45rem .85rem;
        font-size: .75rem;
        color: #475569;
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .tour-precio {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--brand-primary, #0f172a);
        line-height: 1;
    }
    .salida-chip {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .72rem;
        background: rgba(15, 23, 42, .05);
        color: #1e293b;
        border-radius: 2rem;
        padding: .2rem .55rem;
        margin: 0 .2rem .2rem 0;
        font-weight: 500;
    }
    .badge-rating {
        background: rgba(15, 23, 42, .75);
        backdrop-filter: blur(4px);
        color: #fbbf24;
        font-weight: 700;
        font-size: .75rem;
        padding: .25rem .5rem;
        border-radius: .4rem;
    }
    .btn-categoria {
        border-radius: 2rem;
        font-size: .82rem;
        font-weight: 600;
        padding: .35rem .9rem;
        transition: all .2s ease;
    }
    .btn-categoria.active {
        background-color: var(--brand-primary, #1e3a8a);
        color: #fff;
        border-color: var(--brand-primary, #1e3a8a);
    }
    .modal-tour-banner {
        height: 220px;
        position: relative;
        overflow: hidden;
        background: #0f172a;
    }
    .modal-tour-banner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .modal-tour-banner .overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, transparent 40%, rgba(15, 23, 42, .9) 100%);
        display: flex;
        align-items: flex-end;
        padding: 1.25rem;
        color: #fff;
    }
</style>
@endsection

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Tours y Experiencias en Tacna</h2>
        <p class="text-muted mb-0">Explora los atractivos turísticos del sur, consulta itinerarios y cotiza en línea en segundos.</p>
    </div>
    <a href="{{ route('portal.cotizaciones') }}" class="btn btn-outline-primary fw-semibold">
        <i class="bi bi-receipt me-1"></i> Mis cotizaciones
    </a>
</div>

<!-- Filtros Interactivos en Vivo -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="buscadorTours" class="form-control bg-light border-start-0" placeholder="Buscar por nombre, destino o palabras clave...">
                </div>
            </div>
            <div class="col-lg-7">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end" id="filtrosCategorias">
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria active" data-cat="TODOS">Todos</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria" data-cat="Aventura & Termas">Géiseres & Termas</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria" data-cat="Más Popular">Tacna Histórica</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria" data-cat="Cultura & Caminata">Valle Viejo & Miculla</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria" data-cat="Enoturismo & Cata">Ruta del Pisco</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria" data-cat="Naturaleza & Relax">Tarata Andina</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cuadrícula de Tours Interactiva -->
<div class="row g-4" id="contenedorTours">
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
        $destinosLista = $p->destinos->pluck('nombre')->join(', ');
        $serviciosLista = $p->servicios->pluck('nombre')->join(', ');
    @endphp
    <div class="col-md-6 col-xl-4 tour-item"
         data-nombre="{{ strtolower($p->nombre) }}"
         data-categoria="{{ $p->badge_categoria }}"
         data-destinos="{{ strtolower($destinosLista) }}"
         data-descripcion="{{ strtolower($p->descripcion) }}">
        <div class="card tour-card h-100">
            <!-- Fotografía del Tour con Hover Zoom y Botón de Apertura -->
            <div class="tour-foto-wrapper btn-ver-detalle"
                 title="Haz clic para ver la fotografía y el itinerario completo"
                 data-id="{{ $p->id_producto }}"
                 data-nombre="{{ $p->nombre }}"
                 data-codigo="{{ $p->codigo }}"
                 data-foto="{{ $p->imagen_url }}"
                 data-foto-desc="{{ $p->foto_descripcion }}"
                 data-descripcion="{{ $p->descripcion }}"
                 data-itinerario="{{ $p->itinerario }}"
                 data-destinos="{{ $destinosLista }}"
                 data-servicios="{{ $serviciosLista }}"
                 data-duracion="{{ $p->duracion_dias }}"
                 data-precio="{{ $simbolo }} {{ number_format($p->precio_base, 2) }}"
                 data-precio-raw="{{ $p->precio_base }}"
                 data-minimo="{{ max(1, (int) $p->minimo_pasajeros) }}"
                 data-salidas='@json($salidasDisponibles)'>
                <img src="{{ $p->imagen_url }}" alt="{{ $p->nombre }}" class="tour-foto-img" loading="lazy">
                <div class="tour-overlay">
                    <div class="d-flex justify-content-between align-items-start w-100">
                        <span class="badge bg-warning text-dark fw-bold shadow-sm">{{ $p->badge_categoria }}</span>
                        <span class="badge-rating shadow-sm"><i class="bi bi-star-fill text-warning me-1"></i>{{ $p->rating }}</span>
                    </div>
                    <div>
                        <small class="text-white-50 d-block">{{ $p->codigo }}{{ $p->duracion_dias ? ' · ' . $p->duracion_dias . ($p->duracion_dias == 1 ? ' día completo' : ' días') : '' }}</small>
                        <h5 class="fw-bold text-white mb-0">{{ $p->nombre }}</h5>
                    </div>
                </div>
            </div>

            <!-- Pie de Foto Informativo / Descripción Visual de la Imagen -->
            <div class="tour-foto-caption" title="{{ $p->foto_descripcion }}">
                <i class="bi bi-camera-fill text-primary"></i>
                <span class="text-truncate"><strong>Foto:</strong> {{ $p->foto_descripcion }}</span>
            </div>

            <!-- Cuerpo de la Tarjeta -->
            <div class="card-body d-flex flex-column p-3">
                <p class="text-muted small mb-3">
                    {{ Str::limit($p->descripcion ?: 'Disfruta de una experiencia turística única con guías certificados y movilidad turística.', 110) }}
                </p>

                <!-- Destinos incluidos -->
                @if($p->destinos->isNotEmpty())
                <div class="mb-3">
                    <small class="fw-semibold text-dark d-block mb-1"><i class="bi bi-geo-alt-fill me-1 text-danger"></i>Puntos que visitarás</small>
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($p->destinos->take(3) as $d)
                            <span class="badge bg-light text-dark border fw-normal">{{ $d->nombre }}</span>
                        @endforeach
                        @if($p->destinos->count() > 3)
                            <span class="badge bg-light text-muted border fw-normal">+{{ $p->destinos->count() - 3 }} más</span>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Próximas salidas con cupos libres -->
                <div class="mb-3">
                    <small class="fw-semibold text-dark d-block mb-1"><i class="bi bi-calendar3 me-1 text-primary"></i>Próximas salidas programadas</small>
                    @forelse($salidasDisponibles->take(2) as $s)
                        <span class="salida-chip"><i class="bi bi-clock"></i> {{ $s['texto'] }}</span>
                    @empty
                        <small class="text-muted fst-italic">Fechas a coordinar a pedido.</small>
                    @endforelse
                </div>

                <!-- Footer con Precio y Acciones -->
                <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block" style="font-size: .75rem;">Desde, por persona</small>
                        <span class="tour-precio">{{ $simbolo }} {{ number_format($p->precio_base, 2) }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm btn-ver-detalle"
                            data-id="{{ $p->id_producto }}"
                            data-nombre="{{ $p->nombre }}"
                            data-codigo="{{ $p->codigo }}"
                            data-foto="{{ $p->imagen_url }}"
                            data-foto-desc="{{ $p->foto_descripcion }}"
                            data-descripcion="{{ $p->descripcion }}"
                            data-itinerario="{{ $p->itinerario }}"
                            data-destinos="{{ $destinosLista }}"
                            data-servicios="{{ $serviciosLista }}"
                            data-duracion="{{ $p->duracion_dias }}"
                            data-precio="{{ $simbolo }} {{ number_format($p->precio_base, 2) }}"
                            data-precio-raw="{{ $p->precio_base }}"
                            data-minimo="{{ max(1, (int) $p->minimo_pasajeros) }}"
                            data-salidas='@json($salidasDisponibles)'
                            title="Ver detalles e itinerario completo">
                            <i class="bi bi-info-circle"></i>
                        </button>
                        <button type="button" class="btn btn-primary btn-sm fw-semibold btn-cotizar-tour"
                            data-id="{{ $p->id_producto }}"
                            data-nombre="{{ $p->nombre }}"
                            data-foto="{{ $p->imagen_url }}"
                            data-precio="{{ $p->precio_base }}"
                            data-minimo="{{ max(1, (int) $p->minimo_pasajeros) }}"
                            data-salidas='@json($salidasDisponibles)'>
                            <i class="bi bi-receipt me-1"></i> Cotizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-map fs-1 d-block mb-2 text-warning"></i>
                No se encontraron tours registrados.
            </div>
        </div>
    </div>
    @endforelse
</div>

<!-- Modal 1: Visor Interactivo de Foto y Detalle de Tour (Lightbox) -->
<div class="modal fade" id="modalDetalleTour" tabindex="-1" aria-labelledby="modalDetalleTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-tour-banner">
                <img id="detalleFotoImg" src="" alt="Foto Tour">
                <div class="overlay">
                    <div>
                        <span class="badge bg-warning text-dark mb-1" id="detalleBadge"></span>
                        <h4 class="fw-bold mb-0 text-white" id="modalDetalleTitulo"></h4>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <!-- Pie de Foto dentro del Modal -->
            <div class="bg-light p-3 border-bottom d-flex align-items-center gap-2">
                <i class="bi bi-camera-fill text-primary fs-5"></i>
                <div class="small text-dark" id="detalleFotoCaption"></div>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-7">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-card-text me-1 text-primary"></i> Acerca de este tour</h6>
                        <p class="text-muted small" id="detalleDescripcion"></p>

                        <h6 class="fw-bold text-dark mb-2 mt-4"><i class="bi bi-pin-map-fill me-1 text-danger"></i> Destinos incluidos</h6>
                        <p class="text-muted small" id="detalleDestinos"></p>

                        <h6 class="fw-bold text-dark mb-2 mt-4"><i class="bi bi-check2-circle me-1 text-success"></i> Servicios y comodidades</h6>
                        <p class="text-muted small" id="detalleServicios"></p>
                    </div>
                    <div class="col-md-5">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <span class="text-muted small d-block">Precio base individual</span>
                            <span class="fs-3 fw-bold text-primary mb-2" id="detallePrecio"></span>
                            
                            <hr class="my-2">
                            <div class="small text-muted mb-3">
                                <div><i class="bi bi-shield-check text-success me-1"></i> Anticipo mínimo: <strong>50%</strong></div>
                                <div><i class="bi bi-person-check text-primary me-1"></i> Guías certificados</div>
                                <div><i class="bi bi-truck text-secondary me-1"></i> Movilidad turística autorizada</div>
                            </div>

                            <button type="button" class="btn btn-primary w-100 fw-semibold" id="btnCotizarDesdeDetalle">
                                <i class="bi bi-receipt me-1"></i> Cotizar este tour
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Solicitar Cotización con Calculadora en Tiempo Real -->
<div class="modal fade" id="modalSolicitarCotizacion" tabindex="-1" aria-labelledby="tituloCotizar" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('portal.solicitar') }}" class="w-100">
            @csrf
            <input type="hidden" name="id_producto" id="modalCotizarIdProducto">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-3">
                        <img id="modalCotizarMiniatura" src="" alt="Miniatura" class="rounded-3 shadow-sm" style="width: 52px; height: 52px; object-fit: cover;">
                        <div>
                            <h5 class="modal-title fw-bold mb-0" id="tituloCotizar">Solicitar cotización</h5>
                            <small class="text-muted fw-semibold" id="modalCotizarNombreTour"></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Selector de Fecha / Salida -->
                    <div class="mb-3" id="grupoSalida">
                        <label class="form-label fw-semibold small" for="modalCotizarSalida"><i class="bi bi-calendar-event me-1 text-primary"></i>Fecha de salida programada</label>
                        <select name="id_salida" id="modalCotizarSalida" class="form-select"></select>
                        <div class="form-text">Si no eliges una fecha, la agencia coordinará una fecha personalizada contigo.</div>
                    </div>

                    <!-- Contador Interactivo de Pasajeros y Calculadora en Vivo -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="row g-3 align-items-center">
                            <div class="col-6">
                                <label class="form-label fw-bold small text-dark mb-1" for="modalCotizarPasajeros">
                                    <i class="bi bi-people-fill text-primary me-1"></i> Pasajeros
                                </label>
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary fw-bold" data-pasajeros="-1" aria-label="Menos">−</button>
                                    <input type="number" name="cantidad_pasajeros" id="modalCotizarPasajeros" class="form-control text-center fw-bold" value="2" min="1" max="20" required>
                                    <button type="button" class="btn btn-outline-secondary fw-bold" data-pasajeros="1" aria-label="Más">+</button>
                                </div>
                                <div class="form-text text-danger" id="modalCotizarMinimo" style="font-size: .75rem;"></div>
                            </div>
                            <div class="col-6">
                                <div class="text-end">
                                    <span class="text-muted small d-block" style="font-size: .75rem;">Total Estimado:</span>
                                    <span class="fs-4 fw-bold text-success" id="modalCotizarTotalEstimado">S/ 0.00</span>
                                    <span class="text-muted d-block" style="font-size: .75rem;">
                                        Anticipo requerido (50%): <strong class="text-dark" id="modalCotizarAnticipo">S/ 0.00</strong>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label fw-semibold small" for="modalCotizarObs">Comentarios especiales (opcional)</label>
                        <textarea name="observacion" id="modalCotizarObs" rows="2" class="form-control" maxlength="500" placeholder="¿Viajas con niños, adultos mayores o requieres punto de recojo especial en Tacna?"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="bi bi-check-circle-fill me-1"></i> Generar mi cotización
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Modales de Bootstrap
    const modalCotizar = new bootstrap.Modal(document.getElementById('modalSolicitarCotizacion'));
    const modalDetalle = new bootstrap.Modal(document.getElementById('modalDetalleTour'));

    // Elementos del Modal Cotizar
    const inputPasajeros = document.getElementById('modalCotizarPasajeros');
    const selectSalida = document.getElementById('modalCotizarSalida');
    const totalEstimado = document.getElementById('modalCotizarTotalEstimado');
    const anticipoEstimado = document.getElementById('modalCotizarAnticipo');
    const miniaturaCotizar = document.getElementById('modalCotizarMiniatura');
    let precioBase = 0;
    let minimo = 1;
    let salidas = [];

    // Recalcular Total y Anticipo del 50% en Vivo (RN05)
    function recalcular() {
        let cant = Math.min(20, Math.max(minimo, parseInt(inputPasajeros.value) || minimo));
        const salida = salidas.find(s => String(s.id) === selectSalida.value);
        if (salida && cant > salida.disponibles) {
            cant = Math.max(minimo, salida.disponibles);
        }
        inputPasajeros.min = minimo;
        inputPasajeros.max = salida ? Math.min(20, salida.disponibles) : 20;
        inputPasajeros.value = cant;

        const total = cant * precioBase;
        const anticipo = total * 0.50; // Regla del 50% de anticipo

        totalEstimado.textContent = `S/ ${total.toFixed(2)}`;
        anticipoEstimado.textContent = `S/ ${anticipo.toFixed(2)}`;
    }

    inputPasajeros.addEventListener('input', recalcular);
    selectSalida.addEventListener('change', recalcular);
    document.querySelectorAll('[data-pasajeros]').forEach(b => b.addEventListener('click', () => {
        inputPasajeros.value = (parseInt(inputPasajeros.value) || 1) + parseInt(b.dataset.pasajeros);
        recalcular();
    }));

    // Apertura del Modal Cotizar
    function abrirCotizador(btn) {
        document.getElementById('modalCotizarIdProducto').value = btn.dataset.id;
        document.getElementById('modalCotizarNombreTour').textContent = btn.dataset.nombre;
        miniaturaCotizar.src = btn.dataset.foto || '';
        precioBase = parseFloat(btn.dataset.precio || btn.dataset.precioRaw) || 0;
        minimo = parseInt(btn.dataset.minimo) || 1;
        document.getElementById('modalCotizarMinimo').textContent = minimo > 1 ? `Mínimo ${minimo} personas requeridas.` : '';
        
        salidas = JSON.parse(btn.dataset.salidas || '[]').filter(s => s.disponibles >= minimo);

        selectSalida.innerHTML = '<option value="">Coordinar fecha personalizada con la agencia</option>'
            + salidas.map(s => `<option value="${s.id}">${s.texto}</option>`).join('');
        if (salidas.length) selectSalida.value = salidas[0].id;
        document.getElementById('grupoSalida').classList.toggle('d-none', salidas.length === 0);

        inputPasajeros.value = Math.max(2, minimo);
        recalcular();
        modalCotizar.show();
    }

    document.querySelectorAll('.btn-cotizar-tour').forEach(btn => {
        btn.addEventListener('click', function () {
            abrirCotizador(this);
        });
    });

    // Apertura del Visor de Detalle y Foto (Lightbox)
    let tourActualParaCotizar = null;
    document.querySelectorAll('.btn-ver-detalle').forEach(elem => {
        elem.addEventListener('click', function (e) {
            // Evitar que el botón interno de cotizar dispare este evento
            if (e.target.closest('.btn-cotizar-tour')) return;

            tourActualParaCotizar = this;
            document.getElementById('detalleFotoImg').src = this.dataset.foto;
            document.getElementById('modalDetalleTitulo').textContent = this.dataset.nombre;
            document.getElementById('detalleBadge').textContent = this.dataset.codigo;
            document.getElementById('detalleFotoCaption').innerHTML = `<strong>Lugar mostrado en la foto:</strong> ${this.dataset.fotoDesc}`;
            document.getElementById('detalleDescripcion').textContent = this.dataset.descripcion || 'Sin descripción detallada disponible.';
            document.getElementById('detalleDestinos').textContent = this.dataset.destinos || 'Coordinado según itinerario.';
            document.getElementById('detalleServicios').textContent = this.dataset.servicios || 'Transporte turístico y guiado.';
            document.getElementById('detallePrecio').textContent = this.dataset.precio;

            modalDetalle.show();
        });
    });

    // Botón dentro del Visor de Detalle para pasar a Cotizar
    document.getElementById('btnCotizarDesdeDetalle').addEventListener('click', function () {
        modalDetalle.hide();
        if (tourActualParaCotizar) {
            setTimeout(() => {
                abrirCotizador(tourActualParaCotizar);
            }, 300);
        }
    });

    // Filtro interactivo en tiempo real por Buscador
    const buscador = document.getElementById('buscadorTours');
    const itemsTours = document.querySelectorAll('.tour-item');

    function filtrarTours() {
        const texto = buscador.value.toLowerCase().trim();
        const catActiva = document.querySelector('.btn-categoria.active').dataset.cat;

        itemsTours.forEach(item => {
            const nombre = item.dataset.nombre;
            const categoria = item.dataset.categoria;
            const destinos = item.dataset.destinos;
            const descripcion = item.dataset.descripcion;

            const coincideTexto = texto === '' || nombre.includes(texto) || destinos.includes(texto) || descripcion.includes(texto);
            const coincideCategoria = catActiva === 'TODOS' || categoria === catActiva;

            if (coincideTexto && coincideCategoria) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    buscador.addEventListener('input', filtrarTours);

    // Filtro interactivo por Categorías (Chips)
    document.querySelectorAll('.btn-categoria').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-categoria').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filtrarTours();
        });
    });
});
</script>
@endsection
