@extends('layouts.app')

@section('title', 'Destinos')
@section('page_title', 'Catálogo de Destinos Turísticos')

@section('content')
<div class="container-fluid">
    <!-- Filtros y Botón Nuevo Destino (CU08) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('destinos.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="buscar" value="{{ $termino ?? '' }}" placeholder="Buscar por nombre, ubicación o descripción...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="estado">
                        <option value="TODOS" {{ ($estado ?? '') === 'TODOS' ? 'selected' : '' }}>Estado: Todos</option>
                        <option value="ACTIVOS" {{ ($estado ?? '') === 'ACTIVOS' ? 'selected' : '' }}>Estado: Solo Activos</option>
                        <option value="INACTIVOS" {{ ($estado ?? '') === 'INACTIVOS' ? 'selected' : '' }}>Estado: Solo Inactivos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100 fw-semibold">
                        <i class="bi bi-filter me-1"></i> FILTRAR
                    </button>
                </div>
                <div class="col-md-2 text-end">
                    <button type="button" class="btn btn-primary w-100 fw-semibold" style="background-color: #1e3a8a; border: none;" data-bs-toggle="modal" data-bs-target="#modalDestino" onclick="prepararNuevoDestino()">
                        <i class="bi bi-plus-circle-fill me-1"></i> NUEVO DESTINO
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Destinos (PnlDestinos) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Nombre</th>
                            <th>Ubicación</th>
                            <th>Altitud</th>
                            <th>Temporada</th>
                            <th class="text-center">Estado</th>
                            <th class="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($destinos as $d)
                            <tr>
                                <td class="ps-4 fw-semibold text-muted">#{{ $d->id_destino }}</td>
                                <td class="fw-bold text-dark">{{ $d->nombre }}</td>
                                <td><i class="bi bi-geo-alt text-danger me-1"></i> {{ $d->ubicacion ?? '—' }}</td>
                                <td>{{ $d->altitud_msnm ? number_format($d->altitud_msnm, 0) . ' msnm' : '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $d->temporada_recomendada ?? 'Todo el año' }}</span></td>
                                <td class="text-center">
                                    @if ($d->activo)
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">ACTIVO</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1">INACTIVO</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" title="Editar" onclick='editarDestino(@json($d))'>
                                            <i class="bi bi-pencil-square"></i> Editar
                                        </button>
                                        <form action="{{ route('destinos.toggle', $d->id_destino) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Desea cambiar el estado de este destino?');">
                                            @csrf
                                            <button type="submit" class="btn {{ $d->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                                <i class="bi {{ $d->activo ? 'bi-eye-slash' : 'bi-eye' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-compass fs-2 d-block mb-2"></i>
                                    No hay destinos turísticos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-end">
                {{ $destinos->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar / Editar Destino (DlgDestino) -->
<div class="modal fade" id="modalDestino" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalDestinoTitulo">Registrar Destino Turístico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDestino" method="POST" action="{{ route('destinos.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formDestinoMethod" value="POST">

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Nombre del Destino *</label>
                            <input type="text" class="form-control" name="nombre" id="dest_nombre" placeholder="Ej: Valle de los Géiseres" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Ubicación</label>
                            <input type="text" class="form-control" name="ubicacion" id="dest_ubicacion" placeholder="Ej: Candarave - Tacna">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Altitud (msnm)</label>
                            <input type="number" step="0.01" class="form-control" name="altitud_msnm" id="dest_altitud" placeholder="Ej: 4320">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Temporada Recomendada</label>
                            <input type="text" class="form-control" name="temporada_recomendada" id="dest_temporada" placeholder="Ej: Mayo a Noviembre">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción del Destino</label>
                        <textarea class="form-control" name="descripcion" id="dest_descripcion" rows="2" placeholder="Resumen de los atractivos del lugar..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">¿Cómo llegar? (Accesibilidad)</label>
                        <textarea class="form-control" name="como_llegar" id="dest_como_llegar" rows="2" placeholder="Ruta terrestre, tiempo estimado de viaje..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Recomendaciones para el Turista</label>
                        <textarea class="form-control" name="recomendaciones" id="dest_recomendaciones" rows="2" placeholder="Llevar ropa abrigadora, bloqueador, pastillas para la altura..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold" style="background-color: #1e3a8a;">Guardar Destino</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function prepararNuevoDestino() {
        document.getElementById('modalDestinoTitulo').innerText = 'Registrar Destino Turístico';
        document.getElementById('formDestino').action = '{{ route('destinos.store') }}';
        document.getElementById('formDestinoMethod').value = 'POST';
        document.getElementById('formDestino').reset();
    }

    function editarDestino(d) {
        document.getElementById('modalDestinoTitulo').innerText = 'Editar Destino: ' + d.nombre;
        document.getElementById('formDestino').action = '/destinos/' + d.id_destino;
        document.getElementById('formDestinoMethod').value = 'PUT';

        document.getElementById('dest_nombre').value = d.nombre;
        document.getElementById('dest_ubicacion').value = d.ubicacion || '';
        document.getElementById('dest_altitud').value = d.altitud_msnm || '';
        document.getElementById('dest_temporada').value = d.temporada_recomendada || '';
        document.getElementById('dest_descripcion').value = d.descripcion || '';
        document.getElementById('dest_como_llegar').value = d.como_llegar || '';
        document.getElementById('dest_recomendaciones').value = d.recomendaciones || '';

        new bootstrap.Modal(document.getElementById('modalDestino')).show();
    }
</script>
@endsection
