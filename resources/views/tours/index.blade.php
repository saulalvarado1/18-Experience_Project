@extends('layouts.app')

@section('title', 'Tours y Paquetes')
@section('page_title', 'Catálogo de Tours y Paquetes')

@section('content')
<div class="container-fluid">
    <!-- Filtros y Botón Nuevo Producto (CU09) -->
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
                    <button type="button" class="btn btn-primary w-100 fw-semibold" style="background-color: #1e3a8a; border: none;" data-bs-toggle="modal" data-bs-target="#modalTour">
                        <i class="bi bi-plus-circle-fill me-1"></i> NUEVO TOUR
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
                    <button type="submit" class="btn btn-primary fw-semibold" style="background-color: #1e3a8a;">Guardar Producto</button>
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
