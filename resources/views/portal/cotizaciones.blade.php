@extends('layouts.app')

@section('title', 'Mis Cotizaciones — 18 Grados Sur')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>Mis Cotizaciones</h2>
        <p class="text-muted mb-0">Revisa tus presupuestos detallados, descárgalos en PDF y adjunta comprobantes de abono.</p>
    </div>
    <div>
        <a href="{{ route('portal.tours') }}" class="btn btn-outline-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Cotizar Nuevo Tour
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Emisión / Vigencia</th>
                    <th>Tours Incluidos</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($cotizaciones as $c)
                <tr>
                    <td class="fw-bold text-primary">{{ $c->codigo }}</td>
                    <td>
                        <div>{{ $c->fecha_cotizacion ? $c->fecha_cotizacion->format('d/m/Y') : '-' }}</div>
                        <small class="text-muted">Vence: {{ $c->vigencia_hasta ? $c->vigencia_hasta->format('d/m/Y') : '-' }}</small>
                    </td>
                    <td>
                        @foreach($c->detalles as $d)
                            <div class="small fw-semibold">{{ $d->producto->nombre ?? 'Tour' }} ({{ $d->cantidad_pasajeros }} pers.)</div>
                        @endforeach
                    </td>
                    <td class="fw-bold text-success">{{ $c->moneda }} {{ number_format($c->total, 2) }}</td>
                    <td>
                        @if($c->estado === 'ACEPTADA')
                            <span class="badge bg-success">ACEPTADA</span>
                        @elseif($c->estado === 'VIGENTE')
                            <span class="badge bg-info text-dark">VIGENTE</span>
                        @elseif($c->estado === 'SIN_DISPONIBILIDAD')
                            <span class="badge bg-danger">SIN CUPO</span>
                        @else
                            <span class="badge bg-secondary">{{ $c->estado }}</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group">
                            <a href="{{ route('cotizaciones.pdf', $c->id_cotizacion) }}" class="btn btn-sm btn-outline-danger" title="Descargar Cotización en PDF">
                                <i class="bi bi-file-earmark-pdf"></i> PDF
                            </a>

                            @if($c->estado === 'VIGENTE')
                            <form action="{{ route('portal.aceptar', $c->id_cotizacion) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success" title="Aceptar Presupuesto" onclick="return confirm('¿Deseas aceptar esta cotización?')">
                                    <i class="bi bi-check2"></i> Aceptar
                                </button>
                            </form>
                            @endif

                            @if($c->estado === 'ACEPTADA')
                            <button type="button" class="btn btn-sm btn-primary btn-abrir-pago"
                                data-id="{{ $c->id_cotizacion }}"
                                data-codigo="{{ $c->codigo }}"
                                data-total="{{ $c->total }}"
                                title="Subir Comprobante de Pago">
                                <i class="bi bi-cash-stack"></i> Pagar / Voucher
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        No tienes cotizaciones registradas actualmente.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cotizaciones->hasPages())
    <div class="card-footer bg-white border-0 py-3">
        {{ $cotizaciones->links() }}
    </div>
    @endif
</div>

<!-- Modal Subir Comprobante de Pago por el Cliente -->
<div class="modal fade" id="modalSubirPagoCliente" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('portal.pago') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id_cotizacion" id="inputModalCotizacionId">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-upload me-2"></i>Adjuntar Comprobante de Pago</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> Para confirmar tu reserva debes abonar como mínimo el <strong>50% del total</strong> (RN05). Nuestro equipo contable validará tu comprobante en breve.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cotización</label>
                        <input type="text" id="inputModalCotizacionCodigo" class="form-control bg-light fw-bold text-primary" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Método de Pago <span class="text-danger">*</span></label>
                        <select name="id_metodo_pago" class="form-select" required>
                            <option value="">-- Selecciona Método --</option>
                            @foreach($metodos as $m)
                            <option value="{{ $m->id_metodo_pago }}">
                                {{ $m->nombre }} {{ $m->aplica_recargo ? '(Recargo: ' . $m->porcentaje_recargo . '%)' : '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Monto Abonado (S/) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="monto_base" class="form-control" placeholder="Ej: 150.00" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">N.º Operación Bancaria / Yape</label>
                        <input type="text" name="numero_operacion" class="form-control" placeholder="Ej: 12345678">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Captura o PDF del Comprobante (Voucher) <span class="text-danger">*</span></label>
                        <input type="file" name="comprobante" class="form-control" accept="image/*,.pdf" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nota o Mensaje adicional</label>
                        <textarea name="observacion" rows="2" class="form-control" placeholder="Ej: Transferencia realizada desde cuenta BCP"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i> Enviar a Validación</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalPago = new bootstrap.Modal(document.getElementById('modalSubirPagoCliente'));
    const inputId = document.getElementById('inputModalCotizacionId');
    const inputCodigo = document.getElementById('inputModalCotizacionCodigo');

    document.querySelectorAll('.btn-abrir-pago').forEach(btn => {
        btn.addEventListener('click', function () {
            inputId.value = this.getAttribute('data-id');
            inputCodigo.value = `${this.getAttribute('data-codigo')} (Total: S/ ${parseFloat(this.getAttribute('data-total')).toFixed(2)})`;
            modalPago.show();
        });
    });
});
</script>
@endsection
