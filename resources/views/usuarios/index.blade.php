@extends('layouts.app')

@section('title', 'Gestión de Usuarios — 18 Grados Sur')
@section('page_title', 'Usuarios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Usuarios del sistema</h2>
        <p class="text-muted mb-0">Administración de cuentas internas, asignación de roles (GERENTE, COUNTER, CONTABILIDAD) y estado.</p>
    </div>
    <div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario">
            <i class="bi bi-person-plus me-1"></i> Registrar Usuario
        </button>
    </div>
</div>

<!-- Búsqueda (CU04 flujo 2c) -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="buscar" class="form-control" value="{{ $filtros['buscar'] }}" placeholder="Usuario, nombres, apellidos o documento">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <select name="id_rol" class="form-select">
                    <option value="">Rol: todos</option>
                    @foreach($todosLosRoles as $r)
                        <option value="{{ $r->id_rol }}" {{ $filtros['id_rol'] == $r->id_rol ? 'selected' : '' }}>{{ ucfirst(strtolower($r->nombre)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="estado" class="form-select">
                    <option value="TODOS">Estado: todos</option>
                    <option value="ACTIVOS" {{ $filtros['estado'] === 'ACTIVOS' ? 'selected' : '' }}>Activos</option>
                    <option value="INACTIVOS" {{ $filtros['estado'] === 'INACTIVOS' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-filter me-1"></i> Filtrar</button>
            </div>
        </form>
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
                        @php
                            $claseRol = ['GERENTE' => 'bg-danger', 'CONTABILIDAD' => 'bg-info text-dark', 'COUNTER' => 'bg-primary', 'CLIENTE' => 'bg-secondary'][$u->rol->nombre] ?? 'bg-secondary';
                        @endphp
                        <span class="badge {{ $claseRol }}">{{ $u->rol->nombre }}</span>
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
                        @if($u->rol->nombre !== 'CLIENTE')
                        @php
                            $datosEdicion = [
                                'id' => $u->id_usuario,
                                'tipo_documento' => $u->persona->tipo_documento,
                                'numero_documento' => $u->persona->numero_documento,
                                'nombres' => $u->persona->nombres,
                                'apellidos' => $u->persona->apellidos,
                                'telefono' => $u->persona->telefono,
                                'correo' => $u->persona->correo,
                                'id_rol' => $u->id_rol,
                                'nombre_usuario' => $u->nombre_usuario,
                            ];
                        @endphp
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-editar-usuario" title="Editar usuario"
                            data-usuario='@json($datosEdicion)'>
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-primary btn-cambiar-password"
                            data-id="{{ $u->id_usuario }}" data-usuario="{{ $u->nombre_usuario }}" title="Cambiar contraseña">
                            <i class="bi bi-key"></i>
                        </button>
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
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Registrar Nuevo Usuario</h5>
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
                            <input type="password" name="password" class="form-control" required minlength="8" placeholder="Mínimo 8 caracteres">
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

<!-- Modal Editar Usuario (CU04 flujo 2a) -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formEditarUsuario" action="" class="w-100">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Editar usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label small fw-semibold">Tipo de documento</label>
                            <select name="tipo_documento" id="eu_tipo_documento" class="form-select" required>
                                <option value="DNI">DNI</option>
                                <option value="PASAPORTE">Pasaporte</option>
                                <option value="CARNET_EXTRANJERIA">Carné ext.</option>
                            </select>
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold">N.º de documento</label>
                            <input type="text" name="numero_documento" id="eu_numero_documento" class="form-control" maxlength="30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nombres</label>
                            <input type="text" name="nombres" id="eu_nombres" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Apellidos</label>
                            <input type="text" name="apellidos" id="eu_apellidos" class="form-control" maxlength="120" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" id="eu_telefono" class="form-control" maxlength="30">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Correo</label>
                            <input type="email" name="correo" id="eu_correo" class="form-control" maxlength="120">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Nombre de usuario</label>
                            <input type="text" name="nombre_usuario" id="eu_nombre_usuario" class="form-control" maxlength="50" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Rol</label>
                            <select name="id_rol" id="eu_id_rol" class="form-select" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id_rol }}">{{ ucfirst(strtolower($r->nombre)) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-text">La contraseña se cambia con el botón <i class="bi bi-key"></i>.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar cambios</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
    document.querySelectorAll('.btn-editar-usuario').forEach(btn => {
        btn.addEventListener('click', function () {
            const u = JSON.parse(this.dataset.usuario);
            ['tipo_documento', 'numero_documento', 'nombres', 'apellidos', 'telefono', 'correo', 'nombre_usuario', 'id_rol']
                .forEach(c => { document.getElementById('eu_' + c).value = u[c] ?? ''; });
            document.getElementById('formEditarUsuario').action = `/usuarios/${u.id}/editar`;
            modal.show();
        });
    });
});
</script>

<!-- Modal Cambiar Contraseña (CU05) -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="formPassword" action="" class="w-100">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-0"><i class="bi bi-key me-2 text-primary"></i>Cambiar contraseña</h5>
                        <small class="text-muted">Usuario: <strong id="passwordUsuario"></strong></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small" for="nuevaPassword">Nueva contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="nuevaPassword" class="form-control" minlength="8" required autocomplete="new-password">
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold small" for="confirmarPassword">Confirmar contraseña <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="confirmarPassword" class="form-control" minlength="8" required autocomplete="new-password">
                        <div class="invalid-feedback">Las contraseñas no coinciden.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('modalPassword'));
    const form = document.getElementById('formPassword');
    const p1 = document.getElementById('nuevaPassword');
    const p2 = document.getElementById('confirmarPassword');
    const comprobar = () => {
        const distinto = p2.value !== '' && p1.value !== p2.value;
        p2.classList.toggle('is-invalid', distinto);
        p2.setCustomValidity(distinto ? 'Las contraseñas no coinciden.' : '');
    };
    p1.addEventListener('input', comprobar);
    p2.addEventListener('input', comprobar);
    document.querySelectorAll('.btn-cambiar-password').forEach(btn => {
        btn.addEventListener('click', function () {
            form.action = `/usuarios/${this.dataset.id}/password`;
            document.getElementById('passwordUsuario').textContent = this.dataset.usuario;
            form.reset();
            p2.classList.remove('is-invalid');
            modal.show();
        });
    });
});
</script>
@endsection
