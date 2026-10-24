@extends('layouts.app')

@section('title', 'Clientes')
@section('page_title', 'Módulo de Clientes')

@section('content')
<div class="container-fluid">
    <!-- Barra Superior de Acciones y Filtros (CU06) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('clientes.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" name="buscar" value="{{ $termino ?? '' }}" placeholder="Buscar por DNI, nombres, apellidos, teléfono o correo...">
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
                    <button type="button" class="btn btn-primary w-100 fw-semibold" style="background-color: #1e3a8a; border: none;" data-bs-toggle="modal" data-bs-target="#modalCliente" onclick="prepararNuevoCliente()">
                        <i class="bi bi-person-plus-fill me-1"></i> NUEVO CLIENTE
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Clientes (PnlClientes) -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary text-uppercase" style="font-size: 0.75rem;">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Documento</th>
                            <th>Nombres y Apellidos</th>
                            <th>Contacto</th>
                            <th>Tipo</th>
                            <th class="text-center">Estado</th>
                            <th class="pe-4 text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $cliente)
                            <tr>
                                <td class="ps-4 fw-semibold text-muted">#{{ $cliente->id_cliente }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $cliente->persona->tipo_documento }}</span>
                                    <strong class="ms-1">{{ $cliente->persona->numero_documento }}</strong>
                                </td>
                                <td class="fw-bold text-dark">{{ $cliente->persona->nombre_completo }}</td>
                                <td>
                                    <div class="small"><i class="bi bi-telephone text-muted me-1"></i> {{ $cliente->persona->telefono ?? '—' }}</div>
                                    <div class="small text-muted"><i class="bi bi-envelope me-1"></i> {{ $cliente->persona->correo ?? '—' }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $cliente->tipo_cliente === 'CORPORATIVO' ? 'bg-info text-dark' : 'bg-light text-secondary border' }}">
                                        {{ $cliente->tipo_cliente }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($cliente->activo)
                                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-2 py-1">ACTIVO</span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger fw-bold px-2 py-1">INACTIVO</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" title="Ver Historial" onclick="verHistorial({{ $cliente->id_cliente }})">
                                            <i class="bi bi-clock-history"></i> Historial
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" title="Editar" onclick='editarCliente(@json($cliente))'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <form action="{{ route('clientes.toggle', $cliente->id_cliente) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de cambiar el estado de este cliente?');">
                                            @csrf
                                            <button type="submit" class="btn {{ $cliente->activo ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $cliente->activo ? 'Desactivar' : 'Activar' }}">
                                                <i class="bi {{ $cliente->activo ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-2 d-block mb-2"></i>
                                    No se encontraron clientes registrados con los criterios seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $clientes->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrar / Editar Cliente (DlgCliente) -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalClienteTitulo">Registrar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCliente" method="POST" action="{{ route('clientes.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">

                <div class="modal-body p-4">
                    <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Datos de Identidad (Persona)</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo de Documento *</label>
                            <select class="form-select" name="tipo_documento" id="tipo_documento" required>
                                <option value="DNI">DNI (Perú)</option>
                                <option value="PASAPORTE">Pasaporte</option>
                                <option value="CE">Carnet de Extranjería</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Número de Documento *</label>
                            <input type="text" class="form-control" name="numero_documento" id="numero_documento" placeholder="Ej: 75154758" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nombres *</label>
                            <input type="text" class="form-control" name="nombres" id="nombres" placeholder="Nombres completos" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Apellidos *</label>
                            <input type="text" class="form-control" name="apellidos" id="apellidos" placeholder="Apellidos paterno y materno" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fecha Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" id="fecha_nacimiento">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Nacionalidad</label>
                            <input type="text" class="form-control" name="nacionalidad" id="nacionalidad" value="PERUANA">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Género</label>
                            <select class="form-select" name="genero" id="genero">
                                <option value="">Seleccionar</option>
                                <option value="M">Masculino</option>
                                <option value="F">Femenino</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Información de Contacto y Comercial</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono / WhatsApp</label>
                            <input type="text" class="form-control" name="telefono" id="telefono" placeholder="Ej: 952123456">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control" name="correo" id="correo" placeholder="cliente@correo.com">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Tipo Cliente *</label>
                            <select class="form-select" name="tipo_cliente" id="tipo_cliente" required>
                                <option value="DIRECTO">Directo</option>
                                <option value="CORPORATIVO">Corporativo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold" style="background-color: #1e3a8a;">Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Historial Comercial del Cliente (DlgHistorialCliente - CU07) -->
<div class="modal fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title fw-bold" id="historialTitulo">Historial del Cliente</h5>
                    <small class="text-muted" id="historialSubtitulo">Documento: - | Teléfono: -</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Pestañas (Reservas, Cotizaciones, Pagos) -->
                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-reservas" data-bs-toggle="pill" data-bs-target="#panel-reservas" type="button" role="tab">
                            <i class="bi bi-bookmark-check me-1"></i> Reservas (<span id="count-reservas">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-cotizaciones" data-bs-toggle="pill" data-bs-target="#panel-cotizaciones" type="button" role="tab">
                            <i class="bi bi-receipt me-1"></i> Cotizaciones (<span id="count-cotizaciones">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tab-pagos" data-bs-toggle="pill" data-bs-target="#panel-pagos" type="button" role="tab">
                            <i class="bi bi-cash-stack me-1"></i> Pagos Registrados (<span id="count-pagos">0</span>)
                        </button>
                    </li>
                </ul>

                <div class="tab-content border rounded p-3 bg-white" id="pills-tabContent">
                    <!-- Pestaña 1: Reservas -->
                    <div class="tab-pane fade show active" id="panel-reservas" role="tabpanel">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Total Contratado</th>
                                    <th>Saldo Pendiente</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-reservas"></tbody>
                        </table>
                    </div>

                    <!-- Pestaña 2: Cotizaciones -->
                    <div class="tab-pane fade" id="panel-cotizaciones" role="tabpanel">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Counter Asesor</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-cotizaciones"></tbody>
                        </table>
                    </div>

                    <!-- Pestaña 3: Pagos -->
                    <div class="tab-pane fade" id="panel-pagos" role="tabpanel">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light small text-uppercase">
                                <tr>
                                    <th>Fecha Pago</th>
                                    <th>Cotización</th>
                                    <th>Método</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-pagos"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function prepararNuevoCliente() {
        document.getElementById('modalClienteTitulo').innerText = 'Registrar Nuevo Cliente';
        document.getElementById('formCliente').action = '{{ route('clientes.store') }}';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('formCliente').reset();
    }

    function editarCliente(cliente) {
        document.getElementById('modalClienteTitulo').innerText = 'Editar Cliente: ' + cliente.persona.nombres + ' ' + cliente.persona.apellidos;
        document.getElementById('formCliente').action = '/clientes/' + cliente.id_cliente;
        document.getElementById('formMethod').value = 'PUT';

        document.getElementById('tipo_documento').value = cliente.persona.tipo_documento;
        document.getElementById('numero_documento').value = cliente.persona.numero_documento;
        document.getElementById('nombres').value = cliente.persona.nombres;
        document.getElementById('apellidos').value = cliente.persona.apellidos;
        document.getElementById('fecha_nacimiento').value = cliente.persona.fecha_nacimiento ? cliente.persona.fecha_nacimiento.substring(0, 10) : '';
        document.getElementById('nacionalidad').value = cliente.persona.nacionalidad || 'PERUANA';
        document.getElementById('genero').value = cliente.persona.genero || '';
        document.getElementById('telefono').value = cliente.persona.telefono || '';
        document.getElementById('correo').value = cliente.persona.correo || '';
        document.getElementById('tipo_cliente').value = cliente.tipo_cliente;

        new bootstrap.Modal(document.getElementById('modalCliente')).show();
    }

    function verHistorial(idCliente) {
        const modal = new bootstrap.Modal(document.getElementById('modalHistorial'));
        modal.show();

        fetch('/clientes/' + idCliente + '/historial')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                document.getElementById('historialTitulo').innerText = 'Historial: ' + data.cliente.nombre;
                document.getElementById('historialSubtitulo').innerText = 'Documento: ' + data.cliente.documento + ' | Teléfono: ' + data.cliente.telefono;

                // 1. Reservas
                document.getElementById('count-reservas').innerText = data.reservas.length;
                const tbReservas = document.getElementById('tbody-reservas');
                tbReservas.innerHTML = data.reservas.length ? '' : '<tr><td colspan="5" class="text-center text-muted py-3">No hay reservas registradas</td></tr>';
                data.reservas.forEach(r => {
                    tbReservas.innerHTML += `
                        <tr>
                            <td class="fw-bold">${r.codigo}</td>
                            <td>${r.fecha_reserva.substring(0, 10)}</td>
                            <td>${r.moneda} ${parseFloat(r.total_contratado).toFixed(2)}</td>
                            <td class="text-danger fw-semibold">${r.moneda} ${parseFloat(r.saldo_pendiente).toFixed(2)}</td>
                            <td><span class="badge bg-primary">${r.estado}</span></td>
                        </tr>`;
                });

                // 2. Cotizaciones
                document.getElementById('count-cotizaciones').innerText = data.cotizaciones.length;
                const tbCot = document.getElementById('tbody-cotizaciones');
                tbCot.innerHTML = data.cotizaciones.length ? '' : '<tr><td colspan="5" class="text-center text-muted py-3">No hay cotizaciones registradas</td></tr>';
                data.cotizaciones.forEach(c => {
                    tbCot.innerHTML += `
                        <tr>
                            <td class="fw-bold">${c.codigo}</td>
                            <td>${c.fecha_cotizacion.substring(0, 10)}</td>
                            <td>${c.moneda} ${parseFloat(c.total).toFixed(2)}</td>
                            <td>${c.counter}</td>
                            <td><span class="badge bg-secondary">${c.estado}</span></td>
                        </tr>`;
                });

                // 3. Pagos
                document.getElementById('count-pagos').innerText = data.pagos.length;
                const tbPagos = document.getElementById('tbody-pagos');
                tbPagos.innerHTML = data.pagos.length ? '' : '<tr><td colspan="5" class="text-center text-muted py-3">No hay pagos registrados</td></tr>';
                data.pagos.forEach(p => {
                    tbPagos.innerHTML += `
                        <tr>
                            <td>${p.fecha_pago.substring(0, 10)}</td>
                            <td>${p.cotizacion_codigo}</td>
                            <td>${p.metodo_pago}</td>
                            <td class="fw-bold text-success">${p.moneda} ${parseFloat(p.monto_total_pagado).toFixed(2)}</td>
                            <td><span class="badge ${p.estado === 'VALIDADO' ? 'bg-success' : (p.estado === 'RECHAZADO' ? 'bg-danger' : 'bg-warning')}">${p.estado}</span></td>
                        </tr>`;
                });
            });
    }
</script>
@endsection
