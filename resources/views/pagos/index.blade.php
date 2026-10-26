@extends('layouts.app')

@section('title', 'Pagos y Finanzas — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-cash-coin text-success me-2"></i>Gestión de Pagos y Validación (CU18–CU19)</h2>
        <p class="text-muted mb-0">Recepción de pagos, verificación de comprobantes y validación contable para reservas.</p>
    </div>
    <div>
        <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRegistrarPago">
            <i class="bi bi-plus-circle me-1"></i> Registrar Pago
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('pagos.index') }}" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="estado" class="form-select">
                    <option value="TODOS" {{ ($filtros['estado'] ?? '') === 'TODOS' ? 'selected' : '' }}>Todos los estados</option>
                    <option value="PENDIENTE_VALIDACION" {{ ($filtros['estado'] ?? '') === 'PENDIENTE_VALIDACION' ? 'selected' : '' }}>PENDIENTE DE VALIDACIÓN</option>
                    <option value="VALIDADO" {{ ($filtros['estado'] ?? '') === 'VALIDADO' ? 'selected' : '' }}>VALIDADO</option>
                    <option value="RECHAZADO" {{ ($filtros['estado'] ?? '') === 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha_desde" class="form-control" placeholder="Desde" value="{{ $filtros['fecha_desde'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <input type="date" name="fecha_hasta" class="form-control" placeholder="Hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}">
            </div>
            <div class="col-md-3">
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
                    <th>ID / Fecha</th>
                    <th>Cotización / Cliente</th>
                    <th>Método</th>
                    <th>Monto Base</th>
                    <th>Recargo</th>
                    <th>Total Pagado</th>
                    <th>Estado</th>
                    <th>Comprobante</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pagos as $p)
                <tr>
                    <td>
                        <span class="fw-bold">#{{ $p->id_pago }}</span>
                        <div class="small text-muted">{{ $p->fecha_pago ? $p->fecha_pago->format('d/m/Y H:i') : '' }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-primary">{{ $p->cotizacion->codigo ?? 'N/A' }}</div>
                        <small class="text-muted">{{ $p->cotizacion->cliente->persona->nombreCompleto() ?? '' }}</small>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $p->metodoPago->nombre ?? 'N/A' }}</span>
                        @if($p->numero_operacion)
                        <div class="small text-muted">Op: {{ $p->numero_operacion }}</div>
                        @endif
                    </td>
                    <td class="fw-semibold">{{ $p->moneda }} {{ number_format($p->monto_base, 2) }}</td>
                    <td class="text-danger">{{ $p->recargo > 0 ? '+ S/ ' . number_format($p->recargo, 2) : '-' }}</td>
                    <td class="fw-bold text-success">{{ $p->moneda }} {{ number_format($p->monto_total_pagado, 2) }}</td>
                    <td>
                        @if($p->estado === 'VALIDADO')
                            <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>VALIDADO</span>
                            <div class="small text-muted" style="font-size: 10px;">Por: {{ $p->usuarioValidacion->nombre_usuario ?? 'Sistema' }}</div>
                        @elseif($p->estado === 'PENDIENTE_VALIDACION')
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>POR VALIDAR</span>
                        @else
                            <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>RECHAZADO</span>
                            @if($p->observacion)
                            <div class="small text-danger text-truncate" style="max-width: 140px;" title="{{ $p->observacion }}">{{ $p->observacion }}</div>
                            @endif
                        @endif
                    </td>
                    <td>
                        @if($p->ruta_comprobante)
                            <a href="{{ route('pagos.comprobante', $p->id_pago) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-file-earmark-image"></i> Ver
                            </a>
                        @else
                            <span class="text-muted small">Sin adjunto</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($p->estado === 'PENDIENTE_VALIDACION' && in_array(Auth::user()->rol->nombre ?? '', ['GERENTE', 'CONTABILIDAD']))
                        <div class="btn-group">
                            <form action="{{ route('pagos.validar', $p->id_pago) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Aprobar y Validar Pago" onclick="return confirm('¿Aprobar y validar este pago?')">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-rechazar-pago" data-id="{{ $p->id_pago }}" title="Rechazar Pago">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No se encontraron registros de pagos.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($pagos->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $pagos->links() }}
    </div>
    @endif
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('pagos.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2 text-success"></i>Registrar Pago (CU18)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cotización Asociada <span class="text-danger">*</span></label>
                        <select name="id_cotizacion" class="form-select" required>
                            <option value="">-- Seleccione Cotización --</option>
                            @foreach($cotizaciones as $cot)
                            <option value="{{ $cot->id_cotizacion }}">
                                {{ $cot->codigo }} — {{ $cot->cliente->persona->nombreCompleto() }} (Total: S/ {{ number_format($cot->total, 2) }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                        <select name="id_metodo_pago" id="selectMetodoPago" class="form-select" required>
                            <option value="">-- Seleccione Método --</option>
                            @foreach($metodos as $m)
                            <option value="{{ $m->id_metodo_pago }}" data-recargo="{{ $m->aplica_recargo ? $m->porcentaje_recargo : 0 }}">
                                {{ $m->nombre }} {{ $m->aplica_recargo ? '(Recargo: ' . $m->porcentaje_recargo . '%)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Monto Base (S/) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="monto_base" id="inputMontoBase" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Total con Recargo</label>
                            <input type="text" id="inputMontoTotal" class="form-control bg-light fw-bold text-success" readonly value="S/ 0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">N.º Operación / Referencia</label>
                        <input type="text" name="numero_operacion" class="form-control" placeholder="Ej: OP-987654321">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comprobante / Voucher (Máx. 5MB)</label>
                        <input type="file" name="comprobante" class="form-control" accept="image/*,.pdf">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="observacion" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i> Guardar Pago</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Rechazar Pago -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="formRechazar" action="">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Rechazar Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Indique el motivo por el cual se rechaza el comprobante o depósito:</p>
                    <textarea name="motivo" rows="3" class="form-control" required placeholder="Ej: Depósito no se refleja en la cuenta bancaria de la empresa."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectMetodo = document.getElementById('selectMetodoPago');
    const inputMonto = document.getElementById('inputMontoBase');
    const inputTotal = document.getElementById('inputMontoTotal');

    function actualizarTotal() {
        const monto = parseFloat(inputMonto.value) || 0;
        const opt = selectMetodo.options[selectMetodo.selectedIndex];
        const recargoPct = opt ? (parseFloat(opt.getAttribute('data-recargo')) || 0) : 0;
        const recargo = monto * (recargoPct / 100);
        const total = monto + recargo;
        inputTotal.value = `S/ ${total.toFixed(2)}`;
    }

    if (selectMetodo && inputMonto) {
        selectMetodo.addEventListener('change', actualizarTotal);
        inputMonto.addEventListener('input', actualizarTotal);
    }

    // Modal Rechazar
    const modalRechazar = new bootstrap.Modal(document.getElementById('modalRechazar'));
    const formRechazar = document.getElementById('formRechazar');
    document.querySelectorAll('.btn-rechazar-pago').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            formRechazar.action = `/pagos/${id}/rechazar`;
            modalRechazar.show();
        });
    });
});
</script>
@endsection
