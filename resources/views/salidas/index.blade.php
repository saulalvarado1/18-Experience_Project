@extends('layouts.app')

@section('title', 'Salidas')
@section('page_title', 'Programación de Salidas y Cupos')

@section('content')
<div class="container-fluid">
    <!-- Filtros y Botón Programar Salida (CU12) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('salidas.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Desde:</label>
                    <input type="date" class="form-control form-control-sm" name="desde" value="{{ $desde ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Hasta:</label>
                    <input type="date" class="form-control form-control-sm" name="hasta" value="{{ $hasta ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Tour / Paquete:</label>
                    <select class="form-select form-select-sm" name="id_producto">
                        <option value="">Todos los Tours</option>
                        @foreach ($productos as $pr)
                            <option value="{{ $pr->id_producto }}" {{ ($idProducto ?? '') == $pr->id_producto ? 'selected' : '' }}>
                                {{ $pr->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Estado:</label>
                    <select class="form-select form-select-sm" name="estado">
                        <option value="TODOS" {{ ($estado ?? '') === 'TODOS' ? 'selected' : '' }}>Todos</option>
                        <option value="PROGRAMADA" {{ ($estado ?? '') === 'PROGRAMADA' ? 'selected' : '' }}>Programada</option>
                        <option value="COMPLETA" {{ ($estado ?? '') === 'COMPLETA' ? 'selected' : '' }}>Completa</option>
                        <option value="FINALIZADA" {{ ($estado ?? '') === 'FINALIZADA' ? 'selected' : '' }}>Finalizada</option>
                        <option value="CANCELADA" {{ ($estado ?? '') === 'CANCELADA' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary btn-sm w-100 fw-semibold mt-auto" style="height: 31px;">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-semibold mt-auto" style="height: 31px; background-color: #1e3a8a; border: none;" data-bs-toggle="modal" data-bs-target="#modalSalida">
                        <i class="bi bi-calendar-plus me-1"></i> PROGRAMAR
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Salidas (PnlSalidas - vw_disponibilidad_salida) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Tour / Servicio</th>
                            <th class="text-center">Capacidad</th>
                            <th class="text-center">Ocupados</th>
                            <th class="text-center">Disponibles</th>
                            <th class="text-center">Estado</th>
                            <th class="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salidas as $s)
                            <tr>
                                <td class="ps-4 fw-semibold text-muted">#{{ $s->id_salida }}</td>
                                <td class="fw-bold">{{ \Carbon\Carbon::parse($s->fecha)->format('d/m/Y') }}</td>
                                <td><i class="bi bi-clock me-1 text-muted"></i>{{ substr($s->hora, 0, 5) }}</td>
                                <td class="fw-semibold text-dark">{{ $s->nombre_producto ?? ($s->producto ?? $s->codigo_producto) }}</td>
                                <td class="text-center">{{ $s->capacidad }}</td>
                                <td class="text-center text-danger fw-semibold">{{ $s->cupos_ocupados }}</td>
                                <td class="text-center">
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">
                                        {{ $s->cupos_disponibles }} cupos
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($s->estado === 'PROGRAMADA')
                                        <span class="badge bg-primary">PROGRAMADA</span>
                                    @elseif ($s->estado === 'COMPLETA')
                                        <span class="badge bg-secondary">COMPLETA</span>
                                    @elseif ($s->estado === 'FINALIZADA')
                                        <span class="badge bg-success">FINALIZADA</span>
                                    @elseif ($s->estado === 'CANCELADA')
                                        <span class="badge bg-danger">CANCELADA</span>
                                    @else
                                        <span class="badge bg-warning">{{ $s->estado }}</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if (!in_array($s->estado, ['CANCELADA', 'FINALIZADA']))
                                            <button type="button" class="btn btn-outline-secondary" title="Cambiar Capacidad (Auditado)" onclick='abrirModalCapacidad(@json($s))'>
                                                <i class="bi bi-people"></i> Capacidad
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" title="Cancelar Salida" onclick='abrirModalCancelar(@json($s))'>
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        @else
                                            <span class="text-muted small px-2">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                    No se encontraron salidas programadas con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-end">
                {{ $salidas->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Programar Salida (CU12 - DlgSalida) -->
<div class="modal fade" id="modalSalida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Programar Salida Turística</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('salidas.store') }}">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tour / Paquete *</label>
                        <select class="form-select" name="id_producto" required>
                            <option value="">Seleccione el tour...</option>
                            @foreach ($productos as $pr)
                                <option value="{{ $pr->id_producto }}">[{{ $pr->codigo }}] {{ $pr->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Fecha de Salida *</label>
                            <input type="date" class="form-control" name="fecha" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Hora (HH:mm) *</label>
                            <input type="time" class="form-control" name="hora" value="08:00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Capacidad de Pasajeros *</label>
                        <input type="number" class="form-control" name="capacidad" value="17" min="1" required>
                        <small class="text-muted" style="font-size: 0.75rem;">Capacidad estándar del vehículo: 17 pasajeros.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observaciones</label>
                        <textarea class="form-control" name="observacion" rows="2" placeholder="Notas sobre vehículo, conductor o punto de partida..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold" style="background-color: #1e3a8a;">Guardar Salida</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cambiar Capacidad (CU13 - DlgCambiarCapacidad) -->
<div class="modal fade" id="modalCapacidad" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold">Ajustar Capacidad de Salida</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCapacidad" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Salida Seleccionada</label>
                        <input type="text" class="form-control bg-light" id="cap_detalle" readonly>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Capacidad Actual</label>
                            <input type="text" class="form-control bg-light fw-bold" id="cap_actual" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-danger">Cupos Ocupados</label>
                            <input type="text" class="form-control bg-light fw-bold text-danger" id="cap_ocupados" readonly>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Nueva Capacidad Máxima *</label>
                        <input type="number" class="form-control border-primary fw-bold" name="nueva_capacidad" id="nueva_capacidad" required>
                        <small class="text-muted" style="font-size: 0.75rem;">No puede ser menor a los cupos ya ocupados (RN07).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Motivo del Ajuste *</label>
                        <textarea class="form-control" name="motivo" id="cap_motivo" rows="3" placeholder="Ej: Cambio de minivan a bus de 25 asientos..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold" style="background-color: #1e3a8a;">Actualizar Capacidad</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Cancelar Salida (CU14) -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger bg-opacity-10">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i> Cancelar Salida Programada</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCancelar" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted small">Solo se permite cancelar salidas que no tengan pasajeros con reserva confirmada (RN08).</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Salida</label>
                        <input type="text" class="form-control bg-light" id="canc_detalle" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Motivo de Cancelación *</label>
                        <textarea class="form-control" name="motivo" rows="3" placeholder="Ej: Condiciones climáticas adversas, mantenimiento de vía..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Regresar</button>
                    <button type="submit" class="btn btn-danger fw-semibold">Confirmar Cancelación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function abrirModalCapacidad(s) {
        document.getElementById('cap_detalle').value = s.fecha + ' ' + s.hora.substring(0, 5) + ' - ' + (s.nombre_producto || s.producto || s.codigo_producto);
        document.getElementById('cap_actual').value = s.capacidad + ' asientos';
        document.getElementById('cap_ocupados').value = s.cupos_ocupados + ' pasajeros';
        document.getElementById('nueva_capacidad').min = s.cupos_ocupados;
        document.getElementById('nueva_capacidad').value = s.capacidad;
        document.getElementById('cap_motivo').value = '';
        document.getElementById('formCapacidad').action = '/salidas/' + s.id_salida + '/capacidad';

        new bootstrap.Modal(document.getElementById('modalCapacidad')).show();
    }

    function abrirModalCancelar(s) {
        document.getElementById('canc_detalle').value = s.fecha + ' ' + s.hora.substring(0, 5) + ' - ' + (s.nombre_producto || s.producto || s.codigo_producto);
        document.getElementById('formCancelar').action = '/salidas/' + s.id_salida + '/cancelar';

        new bootstrap.Modal(document.getElementById('modalCancelar')).show();
    }
</script>
@endsection
