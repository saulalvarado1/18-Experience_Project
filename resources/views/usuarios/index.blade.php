@extends('layouts.app')

@section('title', 'Gestión de Usuarios — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-person-badge text-primary me-2"></i>Gestión de Usuarios del Sistema (CU04–CU05)</h2>
        <p class="text-muted mb-0">Administración de cuentas internas, asignación de roles (GERENTE, COUNTER, CONTABILIDAD) y estado.</p>
    </div>
    <div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
            <i class="bi bi-person-plus me-1"></i> Registrar Usuario
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Usuario</th>
                    <th>Nombres y Apellidos</th>
                    <th>Documento</th>
                    <th>Rol</th>
                    <th>Último Acceso</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $u)
                <tr>
                    <td>
                        <div class="fw-bold text-dark">{{ $u->nombre_usuario }}</div>
                        <small class="text-muted">Creado: {{ $u->fecha_creacion ? $u->fecha_creacion->format('d/m/Y') : '-' }}</small>
                    </td>
                    <td>{{ $u->persona->nombreCompleto() }}</td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $u->persona->tipo_documento }}</span>
                        {{ $u->persona->numero_documento }}
                    </td>
                    <td>
                        @if($u->rol->nombre === 'GERENTE')
                            <span class="badge bg-danger">GERENTE</span>
                        @elseif($u->rol->nombre === 'CONTABILIDAD')
                            <span class="badge bg-info text-dark">CONTABILIDAD</span>
                        @else
                            <span class="badge bg-primary">COUNTER</span>
                        @endif
                    </td>
                    <td>{{ $u->ultimo_acceso ? $u->ultimo_acceso->format('d/m/Y H:i') : 'Sin ingresos' }}</td>
                    <td>
                        @if($u->activo)
                            <span class="badge bg-success">ACTIVO</span>
                        @else
                            <span class="badge bg-secondary">INACTIVO</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($u->id_usuario != Auth::id())
                        <form action="{{ route('usuarios.toggle', $u->id_usuario) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $u->activo ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $u->activo ? 'Desactivar cuenta' : 'Activar cuenta' }}">
                                <i class="bi {{ $u->activo ? 'bi-person-slash' : 'bi-person-check' }}"></i>
                            </button>
                        </form>
                        @else
                        <span class="badge bg-light text-muted border">En sesión</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">No hay usuarios registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($usuarios->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $usuarios->links() }}
    </div>
    @endif
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('usuarios.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Registrar Nuevo Usuario (CU04)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Tipo Doc. <span class="text-danger">*</span></label>
                            <select name="tipo_documento" class="form-select" required>
                                <option value="DNI">DNI (8 dígitos)</option>
                                <option value="PASAPORTE">Pasaporte</option>
                                <option value="CARNET_EXTRANJERIA">Carnet Ext.</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">N.º Documento <span class="text-danger">*</span></label>
                            <input type="text" name="numero_documento" class="form-control" required placeholder="Ej: 72345678">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label>
                            <input type="text" name="nombres" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" name="apellidos" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Correo</label>
                            <input type="email" name="correo" class="form-control">
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rol en el Sistema <span class="text-danger">*</span></label>
                        <select name="id_rol" class="form-select" required>
                            @foreach($roles as $r)
                            <option value="{{ $r->id_rol }}">{{ $r->nombre }} — {{ $r->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nombre de Usuario <span class="text-danger">*</span></label>
                            <input type="text" name="nombre_usuario" class="form-control" required placeholder="Ej: maria.counter">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contraseña Inicial <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required placeholder="Mínimo 6 caracteres">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Crear Usuario</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
