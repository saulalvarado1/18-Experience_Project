@extends('layouts.app')

@section('title', 'Reservas Turísticas — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-calendar-check text-primary me-2"></i>Gestión de Reservas (CU20–CU23)</h2>
        <p class="text-muted mb-0">Confirmación de reservas con bloqueo pesimista de cupos (lockForUpdate), manifiesto y cierre.</p>
    </div>
    <div>
        <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalConfirmarReserva">
            <i class="bi bi-plus-circle me-1"></i> Confirmar Nueva Reserva
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reservas.index') }}" class="row g-2 align-items-center">
            <div class="col-md-6">
                <input type="text" name="termino" class="form-control" placeholder="Buscar por código RES, cliente o documento..." value="{{ $filtros['termino'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <select name="estado" class="form-select">
                    <option value="TODOS" {{ ($filtros['estado'] ?? '') === 'TODOS' ? 'selected' : '' }}>Todos los estados</option>
                    <option value="CONFIRMADA" {{ ($filtros['estado'] ?? '') === 'CONFIRMADA' ? 'selected' : '' }}>CONFIRMADA</option>
                    <option value="COMPLETADA" {{ ($filtros['estado'] ?? '') === 'COMPLETADA' ? 'selected' : '' }}>COMPLETADA</option>
                    <option value="CANCELADA" {{ ($filtros['estado'] ?? '') === 'CANCELADA' ? 'selected' : '' }}>CANCELADA</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Cotización / Cliente</th>
                    <th>Fecha Reserva</th>
                    <th>Counter</th>
                    <th>Total Contratado</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservas as $r)
                <tr>
                    <td class="fw-bold text-primary">{{ $r->codigo }}</td>
                    <td>
                        <div class="fw-semibold">{{ $r->cotizacion->cliente->persona->nombreCompleto() ?? 'N/A' }}</div>
                        <small class="text-muted">Cotización: {{ $r->cotizacion->codigo ?? 'N/A' }}</small>
                    </td>
                    <td>{{ $r->fecha_reserva ? $r->fecha_reserva->format('d/m/Y H:i') : '' }}</td>
                    <td>{{ $r->usuarioCounter->nombre_usuario ?? 'N/A' }}</td>
                    <td class="fw-bold text-success">{{ $r->moneda }} {{ number_format($r->total_contratado, 2) }}</td>
                    <td>
                        @if($r->estado === 'CONFIRMADA')
                            <span class="badge bg-success">CONFIRMADA</span>
                        @elseif($r->estado === 'COMPLETADA')
                            <span class="badge bg-primary">COMPLETADA</span>
                        @else
                            <span class="badge bg-secondary">CANCELADA</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-info btn-ver-reserva" data-id="{{ $r->id_reserva }}" title="Ver Manifiesto y Detalles">
                                <i class="bi bi-people"></i> Manifiesto
                            </button>
                            @if($r->estado === 'CONFIRMADA')
                            <form action="{{ route('reservas.completar', $r->id_reserva) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Marcar como Completada" onclick="return confirm('¿Completar la reserva?')">
                                    <i class="bi bi-check2-all"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-cancelar-reserva" data-id="{{ $r->id_reserva }}" title="Cancelar Reserva">
                                <i class="bi bi-x-circle"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No se encontraron reservas registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reservas->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $reservas->links() }}
    </div>
    @endif
</div>

<!-- Modal Confirmar Reserva desde Cotización -->
<div class="modal fade" id="modalConfirmarReserva" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('reservas.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i>Confirmar Reserva (CU20)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Se requiere que la cotización esté en estado <strong>ACEPTADA</strong> y cuente con al menos el <strong>50% de anticipo en pagos validados</strong> (RN05). Al confirmar se bloquearán los cupos en la salida mediante transacción concurrente.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cotización Aceptada <span class="text-danger">*</span></label>
                        <select name="id_cotizacion" class="form-select" required>
                            <option value="">-- Seleccione Cotización Lista --</option>
                            @foreach($cotizacionesListas as $cot)
                            <option value="{{ $cot->id_cotizacion }}">
                                {{ $cot->codigo }} — {{ $cot->cliente->persona->nombreCompleto() }} (Total: S/ {{ number_format($cot->total, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones / Notas de Reserva</label>
                        <textarea name="observacion" rows="3" class="form-control" placeholder="Punto de encuentro, hora especial de recojo, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Confirmar Reserva</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cancelar Reserva -->
<div class="modal fade" id="modalCancelarReserva" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formCancelarReserva" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Cancelar Reserva (CU22)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Al cancelar la reserva se liberarán automáticamente los cupos ocupados en las salidas programadas.</p>
                    <label class="form-label fw-semibold">Motivo de Cancelación <span class="text-danger">*</span></label>
                    <textarea name="motivo" rows="3" class="form-control" required placeholder="Ej: Solicitud formal de anulación del cliente por fuerza mayor."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ver Manifiesto -->
<div class="modal fade" id="modalManifiesto" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2 text-primary"></i>Manifiesto de Pasajeros y Salidas (CU21)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalManifiestoBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalCancelar = new bootstrap.Modal(document.getElementById('modalCancelarReserva'));
    const formCancelar = document.getElementById('formCancelarReserva');
    document.querySelectorAll('.btn-cancelar-reserva').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            formCancelar.action = `/reservas/${id}/cancelar`;
            modalCancelar.show();
        });
    });

    const modalManifiesto = new bootstrap.Modal(document.getElementById('modalManifiesto'));
    const modalBody = document.getElementById('modalManifiestoBody');
    document.querySelectorAll('.btn-ver-reserva').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
            modalManifiesto.show();

            fetch(`/reservas/${id}`)
                .then(res => res.json())
                .then(r => {
                    let html = `<div class="mb-3"><strong>Reserva:</strong> <span class="badge bg-primary fs-6">${r.codigo}</span> | <strong>Estado:</strong> ${r.estado}</div>`;
                    html += '<table class="table table-sm table-bordered"><thead><tr class="table-light"><th>Tour</th><th>Salida</th><th>Pasajero</th><th>Doc</th><th>Restricciones</th></tr></thead><tbody>';

                    if (r.detalles) {
                        r.detalles.forEach(d => {
                            const tour = d.producto ? d.producto.nombre : 'Tour';
                            const salida = d.salida ? `${d.salida.fecha} ${d.salida.hora}` : 'Sin asignar';
                            if (d.pasajeros && d.pasajeros.length > 0) {
                                d.pasajeros.forEach(p => {
                                    const nom = p.persona ? `${p.persona.nombres} ${p.persona.apellidos}` : 'Titular';
                                    const doc = p.persona ? `${p.persona.tipo_documento}: ${p.persona.numero_documento}` : '-';
                                    html += `<tr><td>${tour}</td><td>${salida}</td><td>${nom}</td><td>${doc}</td><td>${p.restriccion_alimentaria || 'Ninguna'}</td></tr>`;
                                });
                            } else {
                                html += `<tr><td>${tour}</td><td>${salida}</td><td colspan="3" class="text-muted">Sin pasajeros detallados</td></tr>`;
                            }
                        });
                    }
                    html += '</tbody></table>';
                    modalBody.innerHTML = html;
                })
                .catch(err => {
                    modalBody.innerHTML = '<div class="alert alert-danger">Error al cargar datos de la reserva.</div>';
                });
        });
    });
});
</script>
@endsection
