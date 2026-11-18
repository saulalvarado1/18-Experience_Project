@extends('layouts.app')

@section('title', 'Tours y Paquetes')
@section('page_title', 'Tours y paquetes')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h2 class="fw-bold text-dark mb-1">Tours y paquetes</h2>
        <p class="text-muted mb-0">Catálogo de productos, precios y porcentaje de reserva.</p>
    </div>

    <!-- Filtros y Botón Nuevo Producto -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('tours.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="buscar" value="{{ $termino ?? '' }}" placeholder="Buscar por código, nombre o itinerario...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="tipo">
                        <option value="TODOS" {{ ($tipo ?? '') === 'TODOS' ? 'selected' : '' }}>Tipo: Todos</option>
                        <option value="TOUR_LOCAL" {{ ($tipo ?? '') === 'TOUR_LOCAL' ? 'selected' : '' }}>Tour Local (Tacna)</option>
                        <option value="PAQUETE_NACIONAL" {{ ($tipo ?? '') === 'PAQUETE_NACIONAL' ? 'selected' : '' }}>Paquete Nacional</option>
                        <option value="PAQUETE_INTERNACIONAL" {{ ($tipo ?? '') === 'PAQUETE_INTERNACIONAL' ? 'selected' : '' }}>Paquete Internacional</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="estado">
                        <option value="TODOS" {{ ($estado ?? '') === 'TODOS' ? 'selected' : '' }}>Estado: Todos</option>
                        <option value="ACTIVOS" {{ ($estado ?? '') === 'ACTIVOS' ? 'selected' : '' }}>Solo Activos</option>
                        <option value="INACTIVOS" {{ ($estado ?? '') === 'INACTIVOS' ? 'selected' : '' }}>Solo Inactivos</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-secondary w-100 fw-semibold">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-primary w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTour">
                        <i class="bi bi-plus-circle-fill me-1"></i> Nuevo tour
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Tours (PnlProductos) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">Código</th>
                            <th>Nombre del Tour / Paquete</th>
                            <th>Tipo</th>
                            <th>Precio Base</th>
                            <th class="text-center">Anticipo Mín.</th>
                            <th class="text-center">Mín. Pax</th>
                            <th class="text-center">Estado</th>
                            <th class="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($productos as $p)
                            <tr>
                                <td class="ps-4 fw-bold text-primary">{{ $p->codigo }}</td>
                                <td class="fw-semibold text-dark">
                                    {{ $p->nombre }}
                                    <div class="small text-muted">{{ Str::limit($p->descripcion, 60) }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $p->tipo_producto === 'TOUR_LOCAL' ? 'bg-primary' : ($p->tipo_producto === 'PAQUETE_NACIONAL' ? 'bg-success' : 'bg-warning text-dark') }}">
                                        {{ str_replace('_', ' ', $p->tipo_producto) }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ $p->moneda }} {{ number_format($p->precio_base, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">{{ number_format($p->porcentaje_reserva, 0) }}%</span>
                                </td>
                                <td class="text-center">{{ $p->minimo_pasajeros }} pax</td>
                                <td class="text-center">
                                    @if ($p->activo)
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">ACTIVO</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1">INACTIVO</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        @php $datosTour = $p->only(['id_producto', 'codigo', 'nombre', 'tipo_producto', 'moneda', 'porcentaje_reserva', 'minimo_pasajeros', 'duracion_dias', 'descripcion', 'itinerario']); @endphp
                                        <button type="button" class="btn btn-outline-secondary" title="Editar tour" onclick='abrirModalEditarTour(@json($datosTour))'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="{{ route('tours.composicion', $p->id_producto) }}" class="btn btn-outline-primary" title="Destinos y servicios del tour">
                                            <i class="bi bi-diagram-3"></i> Composición
                                        </a>
                                        <form method="POST" action="{{ route('tours.toggle', $p->id_producto) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn {{ $p->activo ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $p->activo ? 'Desactivar' : 'Activar' }} tour"
                                                data-confirm="{{ $p->activo ? 'El tour dejará de ofrecerse y de poder cotizarse.' : 'El tour volverá a ofrecerse y podrá cotizarse.' }}"
                                                data-confirm-title="¿{{ $p->activo ? 'Desactivar' : 'Activar' }} el tour?" data-confirm-ok="Sí, {{ $p->activo ? 'desactivar' : 'activar' }}"
                                                data-confirm-variant="{{ $p->activo ? 'warning' : 'success' }}">
                                                <i class="bi {{ $p->activo ? 'bi-toggle-on' : 'bi-toggle-off' }}"></i>
                                            </button>
                                        </form>
                                        @if (auth()->user()->tieneRol('GERENTE'))
                                            <button type="button" class="btn btn-outline-warning text-dark" title="Modificar Precio Base (Auditado)" onclick='abrirModalPrecio(@json($p))'>
                                                <i class="bi bi-tag-fill"></i> Precio
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-map fs-2 d-block mb-2"></i>
                                    No hay tours o paquetes registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-end">
                {{ $productos->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Editar Tour (CU09 flujo 1a; el precio se cambia en CU11) -->
<div class="modal fade" id="modalEditarTour" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="formEditarTour" action="" class="w-100">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Editar tour o paquete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Código</label>
                            <input type="text" class="form-control text-uppercase" name="codigo" id="et_codigo" maxlength="30" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="et_nombre" maxlength="180" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo_producto" id="et_tipo_producto" required>
                                <option value="TOUR_LOCAL">Tour Local (Tacna)</option>
                                <option value="PAQUETE_NACIONAL">Paquete Nacional</option>
                                <option value="PAQUETE_INTERNACIONAL">Paquete Internacional</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Moneda</label>
                            <select class="form-select" name="moneda" id="et_moneda" required>
                                <option value="PEN">PEN</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">% reserva</label>
                            <input type="number" step="0.01" min="1" max="100" class="form-control" name="porcentaje_reserva" id="et_porcentaje_reserva" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Mín. pax</label>
                            <input type="number" min="1" class="form-control" name="minimo_pasajeros" id="et_minimo_pasajeros" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Días</label>
                            <input type="number" min="1" class="form-control" name="duracion_dias" id="et_duracion_dias" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="et_descripcion" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Itinerario</label>
                            <textarea class="form-control" name="itinerario" id="et_itinerario" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="form-text mt-2">El precio base se cambia con el botón "Precio" (solo Gerente, auditado).</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Registrar Tour (DlgProducto) -->
<div class="modal fade" id="modalTour" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Registrar Nuevo Tour / Paquete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('tours.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Código *</label>
                            <input type="text" class="form-control text-uppercase" name="codigo" placeholder="Ej: TL-MICULLA-001" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nombre del Tour / Paquete *</label>
                            <input type="text" class="form-control" name="nombre" placeholder="Ej: Tour Petroglifos de Miculla" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo de Producto *</label>
                            <select class="form-select" name="tipo_producto" id="tipo_producto" required onchange="ajustarMinimoAnticipo()">
                                <option value="TOUR_LOCAL">Tour Local (Tacna)</option>
                                <option value="PAQUETE_NACIONAL">Paquete Nacional</option>
                                <option value="PAQUETE_INTERNACIONAL">Paquete Internacional</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Precio Base *</label>
                            <input type="number" step="0.01" class="form-control" name="precio_base" placeholder="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Moneda *</label>
                            <select class="form-select" name="moneda" required>
                                <option value="PEN">Soles (PEN)</option>
                                <option value="USD">Dólares (USD)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Anticipo Reserva (%) *</label>
                            <input type="number" step="0.01" class="form-control" name="porcentaje_reserva" id="porcentaje_reserva" value="50" min="50" required>
                            <small class="text-muted" style="font-size: 0.75rem;">Mínimo 50% en Tours Locales (RN05)</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Mínimo Pasajeros *</label>
                            <input type="number" class="form-control" name="minimo_pasajeros" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Duración en Días *</label>
                            <input type="number" class="form-control" name="duracion_dias" value="1" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción del Servicio</label>
                        <textarea class="form-control" name="descripcion" rows="2" placeholder="Resumen comercial del tour..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Itinerario y Horarios</label>
                        <textarea class="form-control" name="itinerario" rows="3" placeholder="Punto de encuentro, paradas, duración aproximada de cada actividad..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Precio (CU11 - DlgCambiarPrecio) -->
<div class="modal fade" id="modalPrecio" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-lock-fill me-1"></i> Modificar Precio Base (Gerencia)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPrecio" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i> Esta acción quedará registrada en el módulo de auditoría (RN04 y RN16).
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Producto Seleccionado</label>
                        <input type="text" class="form-control bg-light" id="precio_nombre" readonly>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Precio Actual</label>
                            <input type="text" class="form-control bg-light fw-bold" id="precio_actual" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-dark">Nuevo Precio Base *</label>
                            <input type="number" step="0.01" class="form-control border-primary fw-bold" name="nuevo_precio" id="nuevo_precio" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Motivo de la Modificación *</label>
                        <textarea class="form-control" name="motivo" id="motivo" rows="3" placeholder="Ej: Ajuste de tarifas por incremento de combustible o nueva temporada..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning fw-semibold">Actualizar y Auditar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function ajustarMinimoAnticipo() {
        const tipo = document.getElementById('tipo_producto').value;
        const input = document.getElementById('porcentaje_reserva');
        if (tipo === 'TOUR_LOCAL') {
            input.min = "50";
            if (parseFloat(input.value) < 50) input.value = "50";
        } else {
            input.min = "10";
        }
    }

    function abrirModalEditarTour(p) {
        ['codigo', 'nombre', 'tipo_producto', 'moneda', 'porcentaje_reserva', 'minimo_pasajeros', 'duracion_dias', 'descripcion', 'itinerario']
            .forEach(c => { document.getElementById('et_' + c).value = p[c] ?? ''; });
        document.getElementById('formEditarTour').action = '/tours/' + p.id_producto + '/editar';
        new bootstrap.Modal(document.getElementById('modalEditarTour')).show();
    }

    function abrirModalPrecio(producto) {
        document.getElementById('precio_nombre').value = '[' + producto.codigo + '] ' + producto.nombre;
        document.getElementById('precio_actual').value = producto.moneda + ' ' + parseFloat(producto.precio_base).toFixed(2);
        document.getElementById('nuevo_precio').value = '';
        document.getElementById('motivo').value = '';
        document.getElementById('formPrecio').action = '/tours/' + producto.id_producto + '/precio';

        new bootstrap.Modal(document.getElementById('modalPrecio')).show();
    }
</script>
@endsection
