@extends('layouts.app')

@section('title', 'Mis Cotizaciones — 18 Grados Sur')
@section('page_title', 'Mis cotizaciones')

@section('styles')
<style>
    .cot-card { border: 0; border-radius: .9rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
    .cot-card .card-header { background: #fff; border-bottom: 1px solid #eef1f6; border-radius: .9rem .9rem 0 0; }
    .cot-codigo { font-weight: 700; color: var(--brand-primary); letter-spacing: .02em; }

    /* Pasos del proceso */
    .pasos { display: flex; list-style: none; padding: 0; margin: 0; counter-reset: paso; }
    .pasos li { flex: 1; text-align: center; position: relative; font-size: .75rem; color: #94a3b8; }
    .pasos li::before {
        content: counter(paso); counter-increment: paso;
        display: flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; margin: 0 auto .3rem; border-radius: 50%;
        background: #e2e8f0; color: #64748b; font-weight: 700; position: relative; z-index: 1;
    }
    .pasos li + li::after {
        content: ''; position: absolute; top: 14px; left: -50%; width: 100%; height: 3px; background: #e2e8f0;
    }
    .pasos li.hecho { color: #059669; }
    .pasos li.hecho::before { content: '\2713'; background: #059669; color: #fff; }
    .pasos li.hecho::after, .pasos li.actual::after { background: #059669; }
    .pasos li.actual { color: var(--brand-primary); font-weight: 700; }
    .pasos li.actual::before { background: var(--brand-primary); color: #fff; box-shadow: 0 0 0 4px rgba(var(--brand-primary-rgb), .15); }

    /* Barra de pago con marca de anticipo */
    .barra-pago { position: relative; padding-top: 1.25rem; }
    .barra-pago .progress { height: 12px; border-radius: 6px; background: #e9eef5; }
    .marca-anticipo { position: absolute; top: 0; bottom: -4px; border-left: 2px dashed #b45309; }
    .marca-anticipo span {
        position: absolute; top: 0; left: 4px; white-space: nowrap;
        font-size: .68rem; font-weight: 700; color: #b45309;
    }
    .marca-anticipo.fin span { left: auto; right: 4px; }
    .total-grande { font-size: 1.6rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
    .leyenda i { font-size: .6rem; vertical-align: middle; }

    .pago-item { border-left: 3px solid #e2e8f0; padding-left: .75rem; }
    .pago-item.VALIDADO { border-left-color: #059669; }
    .pago-item.PENDIENTE_VALIDACION { border-left-color: #f59e0b; }
    .pago-item.RECHAZADO { border-left-color: #dc2626; }
    .pago-item.DEVUELTO { border-left-color: #64748b; }

    #modalPagar .nav-tabs .nav-link { font-weight: 600; color: #64748b; }
    #modalPagar .nav-tabs .nav-link.active { color: var(--brand-primary); }
    .btn-monto-rapido.active { background: var(--brand-primary); color: #fff; border-color: var(--brand-primary); }

    @media (max-width: 575.98px) {
        .pasos li { font-size: .62rem; }
        .cot-acciones .btn { flex: 1 1 auto; }
    }
</style>
@endsection

@section('content')
@php
    $estados = [
        'VIGENTE' => ['Por aceptar', 'bg-info text-dark'],
        'ACEPTADA' => ['Aceptada', 'bg-success'],
        'NO_ACEPTADA' => ['No aceptada', 'bg-secondary'],
        'CANCELADA' => ['Cancelada', 'bg-secondary'],
        'SIN_DISPONIBILIDAD' => ['Sin cupos', 'bg-danger'],
    ];
    $estadosPago = [
        'VALIDADO' => ['Validado', 'bg-success'],
        'PENDIENTE_VALIDACION' => ['En validación', 'bg-warning text-dark'],
        'RECHAZADO' => ['Rechazado', 'bg-danger'],
        'DEVUELTO' => ['Devuelto', 'bg-secondary'],
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold text-dark mb-1">Mis cotizaciones</h2>
        <p class="text-muted mb-0">Acepta tu presupuesto, paga el anticipo y nosotros confirmamos tu reserva.</p>
    </div>
    <a href="{{ route('portal.tours') }}" class="btn btn-outline-primary">
        <i class="bi bi-plus-circle me-1"></i> Cotizar nuevo tour
    </a>
</div>

@forelse($cotizaciones as $c)
    @php
        $r = $resumen[$c->id_cotizacion];
        [$estadoTexto, $estadoClase] = $estados[$c->estado] ?? [$c->estado, 'bg-secondary'];
        $activa = in_array($c->estado, ['VIGENTE', 'ACEPTADA'], true);
        $puedePagar = $c->estado === 'ACEPTADA' && $r['pagable'] > 0;
        $anticipoRestante = max(0, min($r['pagable'], round($r['anticipo'] - $r['validado'] - $r['pendiente'], 2)));
        $pctAnticipo = $r['total'] > 0 ? min(100, $r['anticipo'] * 100 / $r['total']) : 0;
        $pagos = $c->pagos->sortByDesc('id_pago');
    @endphp
    <div class="card cot-card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div>
                <span class="cot-codigo">{{ $c->codigo }}</span>
                <span class="badge {{ $estadoClase }} ms-2">{{ $estadoTexto }}</span>
                @if($r['tiene_reserva'])
                    <span class="badge bg-primary ms-1"><i class="bi bi-bookmark-check me-1"></i>Reservada</span>
                @endif
            </div>
            <small class="text-muted">
                Emitida {{ $c->fecha_cotizacion ? $c->fecha_cotizacion->format('d/m/Y') : '-' }}
                @if($c->estado === 'VIGENTE' && $c->vigencia_hasta)
                    · <span class="{{ $c->vigencia_hasta->isPast() ? 'text-danger' : '' }}">vence {{ $c->vigencia_hasta->format('d/m/Y') }}</span>
                @endif
            </small>
        </div>

        <div class="card-body">
            <div class="row g-4">
                <!-- Tours y pasos -->
                <div class="col-lg-7">
                    @foreach($c->detalles as $d)
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="bi bi-geo-alt-fill text-gold mt-1"></i>
                            <div>
                                <div class="fw-semibold text-dark">{{ $d->producto->nombre ?? 'Tour' }}</div>
                                <small class="text-muted">
                                    {{ $d->cantidad_pasajeros }} {{ $d->cantidad_pasajeros == 1 ? 'persona' : 'personas' }}
                                    @if($d->salida && $d->salida->fecha)
                                        · salida {{ $d->salida->fecha->format('d/m/Y') }} {{ $d->salida->hora ? substr($d->salida->hora, 0, 5) : '' }}
                                    @endif
                                </small>
                            </div>
                        </div>
                    @endforeach

                    @if($activa || $r['tiene_reserva'])
                    <ol class="pasos mt-4" aria-label="Progreso de la cotización">
                        @foreach(['Cotización', 'Aceptada', 'Anticipo pagado', 'Reserva confirmada'] as $i => $nombre)
                            @php $n = $i + 1; @endphp
                            <li class="{{ $n < $r['paso'] || ($n === 4 && $r['paso'] === 4) ? 'hecho' : ($n === $r['paso'] ? 'actual' : '') }}">{{ $nombre }}</li>
                        @endforeach
                    </ol>
                    @endif
                </div>

                <!-- Montos y progreso -->
                <div class="col-lg-5">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <small class="text-muted d-block">Total</small>
                            <span class="total-grande">{{ $c->moneda === 'USD' ? 'US$' : 'S/' }} {{ number_format($r['total'], 2) }}</span>
                        </div>
                        @if($c->estado === 'ACEPTADA' || $r['validado'] > 0)
                        <div class="text-end">
                            <small class="text-muted d-block">Por pagar</small>
                            <span class="fw-bold {{ $r['pagable'] > 0 ? 'text-dark' : 'text-success' }}">S/ {{ number_format($r['pagable'], 2) }}</span>
                        </div>
                        @endif
                    </div>

                    @if($c->estado === 'ACEPTADA' || $r['validado'] > 0 || $r['pendiente'] > 0)
                    <div class="barra-pago mt-2">
                        <div class="marca-anticipo {{ $pctAnticipo > 70 ? 'fin' : '' }}" style="left: {{ $pctAnticipo }}%">
                            <span>Anticipo {{ rtrim(rtrim(number_format($r['porcentaje_anticipo'], 2), '0'), '.') }}%</span>
                        </div>
                        <div class="progress" role="progressbar" aria-label="Pagado" aria-valuenow="{{ $r['pct_validado'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: {{ $r['pct_validado'] }}%"></div>
                            <div class="progress-bar bg-warning progress-bar-striped" style="width: {{ $r['pct_pendiente'] }}%"></div>
                        </div>
                    </div>
                    <div class="leyenda d-flex flex-wrap gap-3 small text-muted mt-2">
                        <span><i class="bi bi-circle-fill text-success"></i> Pagado S/ {{ number_format($r['validado'], 2) }}</span>
                        @if($r['pendiente'] > 0)
                            <span><i class="bi bi-circle-fill text-warning"></i> En validación S/ {{ number_format($r['pendiente'], 2) }}</span>
                        @endif
                    </div>
                    @endif

                    <!-- Siguiente paso -->
                    <div class="mt-3 small">
                        @if($r['tiene_reserva'])
                            <div class="alert alert-primary py-2 mb-0">
                                <i class="bi bi-bookmark-check me-1"></i> Tu reserva está confirmada.
                                <a href="{{ route('portal.reservas') }}" class="alert-link">Ver mis reservas</a>
                            </div>
                        @elseif($c->estado === 'VIGENTE')
                            <div class="alert alert-info py-2 mb-0">
                                <i class="bi bi-hand-index me-1"></i> Revisa el presupuesto y <strong>acéptalo</strong> para poder pagar.
                            </div>
                        @elseif($c->estado === 'ACEPTADA' && $r['anticipo_cubierto'])
                            <div class="alert alert-success py-2 mb-0">
                                <i class="bi bi-check-circle me-1"></i> <strong>¡Anticipo cubierto!</strong> Nuestro equipo confirmará tu reserva.
                            </div>
                        @elseif($c->estado === 'ACEPTADA')
                            <div class="alert alert-warning py-2 mb-0">
                                <i class="bi bi-wallet2 me-1"></i>
                                @if($anticipoRestante > 0)
                                    Paga al menos <strong>S/ {{ number_format($anticipoRestante, 2) }}</strong> para asegurar tus cupos.
                                @else
                                    Tu pago está en validación. Te avisaremos cuando se confirme.
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Historial de pagos -->
            @if($pagos->isNotEmpty())
            <div class="mt-3 pt-3 border-top">
                <a class="small fw-semibold text-decoration-none" data-bs-toggle="collapse" href="#pagos-{{ $c->id_cotizacion }}" role="button" aria-expanded="false">
                    <i class="bi bi-clock-history me-1"></i> Historial de pagos ({{ $pagos->count() }})
                </a>
                <div class="collapse" id="pagos-{{ $c->id_cotizacion }}">
                    <div class="d-flex flex-column gap-2 mt-3">
                        @foreach($pagos as $p)
                            @php [$pTexto, $pClase] = $estadosPago[$p->estado] ?? [$p->estado, 'bg-secondary']; @endphp
                            <div class="pago-item {{ $p->estado }} d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="fw-semibold">S/ {{ number_format($p->monto_total_pagado, 2) }}</span>
                                    <span class="text-muted small">· {{ str_replace('_', ' ', $p->metodoPago->nombre ?? '') }}</span>
                                    <div class="small text-muted">
                                        {{ $p->fecha_pago ? $p->fecha_pago->format('d/m/Y H:i') : '' }}
                                        @if($p->numero_operacion) · Op. {{ $p->numero_operacion }} @endif
                                        @if($p->monto_devuelto > 0) · <span class="text-danger">devuelto S/ {{ number_format($p->monto_devuelto, 2) }}</span> @endif
                                    </div>
                                </div>
                                <span class="badge {{ $pClase }}">{{ $pTexto }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="card-footer bg-white border-0 pb-3 pt-0">
            <div class="cot-acciones d-flex flex-wrap gap-2 justify-content-end">
                <a href="{{ route('cotizaciones.pdf', $c->id_cotizacion) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
                </a>

                @if($c->estado === 'VIGENTE')
                <form action="{{ route('portal.aceptar', $c->id_cotizacion) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success px-3" data-confirm="Después podrás pagar el anticipo para asegurar tus cupos." data-confirm-title="¿Aceptar esta cotización?" data-confirm-ok="Sí, aceptar" data-confirm-variant="success">
                        <i class="bi bi-check2"></i> Aceptar cotización
                    </button>
                </form>
                @endif

                @if($puedePagar)
                <button type="button" class="btn btn-sm btn-gold px-3 btn-pagar"
                    data-id="{{ $c->id_cotizacion }}"
                    data-codigo="{{ $c->codigo }}"
                    data-total="{{ number_format($r['total'], 2, '.', '') }}"
                    data-pagado="{{ number_format($r['validado'], 2, '.', '') }}"
                    data-pagable="{{ number_format($r['pagable'], 2, '.', '') }}"
                    data-anticipo="{{ number_format($anticipoRestante, 2, '.', '') }}">
                    <i class="bi bi-credit-card"></i> Pagar
                </button>
                @endif
            </div>
        </div>
    </div>
@empty
    <div class="card cot-card">
        <div class="card-body text-center py-5">
            <i class="bi bi-compass fs-1 text-gold d-block mb-3"></i>
            <h5 class="fw-bold">Aún no tienes cotizaciones</h5>
            <p class="text-muted mb-4">Explora nuestros tours y solicita tu presupuesto en segundos.</p>
            <a href="{{ route('portal.tours') }}" class="btn btn-primary"><i class="bi bi-search me-1"></i> Ver tours</a>
        </div>
    </div>
@endforelse

@if($cotizaciones->hasPages())
    <div class="mt-3">{{ $cotizaciones->links() }}</div>
@endif

<!-- ================= Modal único de pago ================= -->
<div class="modal fade" id="modalPagar" tabindex="-1" aria-labelledby="modalPagarTitulo">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="modalPagarTitulo"><i class="bi bi-wallet2 me-2 text-primary"></i>Pagar cotización</h5>
                    <small class="text-muted" id="pagarCodigo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row text-center g-2 mb-3">
                    <div class="col-4"><small class="text-muted d-block">Total</small><span class="fw-bold" id="pagarTotal"></span></div>
                    <div class="col-4"><small class="text-muted d-block">Pagado</small><span class="fw-bold text-success" id="pagarPagado"></span></div>
                    <div class="col-4"><small class="text-muted d-block">Por pagar</small><span class="fw-bold" id="pagarSaldo"></span></div>
                </div>

                <ul class="nav nav-tabs nav-fill mb-3" role="tablist">
                    @if($pagoOnline)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tab-pago-online" data-bs-toggle="tab" data-bs-target="#panel-online" type="button" role="tab">
                            <i class="bi bi-credit-card me-1"></i> Tarjeta o Yape
                        </button>
                    </li>
                    @endif
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $pagoOnline ? '' : 'active' }}" id="tab-pago-voucher" data-bs-toggle="tab" data-bs-target="#panel-voucher" type="button" role="tab">
                            <i class="bi bi-upload me-1"></i> Ya pagué (voucher)
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    @if($pagoOnline)
                    <!-- Pago en línea (Culqi). Los datos de tarjeta/Yape se ingresan en Culqi, nunca en este servidor. -->
                    <div class="tab-pane fade show active" id="panel-online" role="tabpanel">
                        <p class="small text-muted mb-3"><i class="bi bi-shield-lock text-success me-1"></i> Pago seguro procesado por Culqi. Se confirma al instante.</p>

                        <label class="form-label fw-semibold small mb-1">¿Cuánto deseas pagar?</label>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill btn-monto-rapido" data-tipo="anticipo">Anticipo<br><strong data-monto-anticipo></strong></button>
                            <button type="button" class="btn btn-sm btn-outline-primary flex-fill btn-monto-rapido" data-tipo="total">Todo el saldo<br><strong data-monto-total></strong></button>
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" min="0.01" id="pagoOnlineMonto" class="form-control" aria-label="Monto a pagar">
                        </div>

                        <div class="bg-light rounded p-3 small">
                            <div class="d-flex justify-content-between"><span>Monto</span><span>S/ <span id="pagoOnlineBase">0.00</span></span></div>
                            <div class="d-flex justify-content-between text-muted"><span>Recargo ({{ rtrim(rtrim(number_format($pagoOnline['porcentaje_recargo'], 2), '0'), '.') }}%)</span><span>S/ <span id="pagoOnlineRecargo">0.00</span></span></div>
                            <div class="d-flex justify-content-between fw-bold fs-5 mt-1 pt-1 border-top"><span>Total a cobrar</span><span class="text-success">S/ <span id="pagoOnlineTotal">0.00</span></span></div>
                        </div>

                        <div id="pagoOnlineError" class="alert alert-danger py-2 small mt-3 d-none" role="alert"></div>

                        <button type="button" id="pagoOnlineContinuar" class="btn btn-gold w-100 mt-3 py-2">
                            <i class="bi bi-lock me-1"></i> Continuar al pago seguro
                        </button>
                    </div>
                    @endif

                    <!-- Voucher manual: lo valida Contabilidad -->
                    <div class="tab-pane fade {{ $pagoOnline ? '' : 'show active' }}" id="panel-voucher" role="tabpanel">
                        <form method="POST" action="{{ route('portal.pago') }}" enctype="multipart/form-data" id="formVoucher">
                            @csrf
                            <input type="hidden" name="id_cotizacion" id="voucherCotizacionId">
                            <p class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i> Si ya pagaste por transferencia, depósito, Yape o Plin a nuestras cuentas, adjunta el comprobante. Contabilidad lo validará.</p>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Método de pago <span class="text-danger">*</span></label>
                                <select name="id_metodo_pago" class="form-select" required>
                                    <option value="">-- Selecciona --</option>
                                    @foreach($metodos as $m)
                                    <option value="{{ $m->id_metodo_pago }}">{{ ucfirst(strtolower(str_replace('_', ' ', $m->nombre))) }}{{ $m->aplica_recargo ? ' (recargo ' . $m->porcentaje_recargo . '%)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label fw-semibold small">Monto pagado (S/) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" name="monto_base" id="voucherMonto" class="form-control" required>
                                </div>
                                <div class="col-sm-6 mb-3">
                                    <label class="form-label fw-semibold small">N.º de operación <span class="text-danger">*</span></label>
                                    <input type="text" name="numero_operacion" class="form-control" placeholder="Ej: 12345678" maxlength="100" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Comprobante (imagen o PDF, máx. 5 MB) <span class="text-danger">*</span></label>
                                <input type="file" name="comprobante" class="form-control" accept="image/jpeg,image/png,.pdf" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Nota (opcional)</label>
                                <textarea name="observacion" rows="2" class="form-control" maxlength="500" placeholder="Ej: Transferencia desde cuenta BCP"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-send me-1"></i> Enviar a validación</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($pagoOnline)
<!-- Modal Simulador Culqi (Sandbox Integrado para Demostración) -->
<div class="modal fade" id="modalCulqiSimulador" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="p-3 text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0b1e3a 0%, #1e3a8a 100%);">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-white text-primary px-2 py-1 fw-bold fs-6">Culqi</span>
                    <div>
                        <div class="fw-bold" style="font-size: 14px;">Pago Seguro con Tarjeta</div>
                        <div class="badge bg-warning text-dark" style="font-size: 10px;">Modo Sandbox / Pruebas</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="bg-white p-3 rounded border mb-3 text-center shadow-sm">
                    <div class="text-muted small">Total a cobrar</div>
                    <div class="fs-2 fw-bold text-success" id="culqiSimuladorTotal">S/ 0.00</div>
                    <div class="text-muted small mt-1" id="culqiSimuladorConcepto">18 Experience - Cotización</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Número de tarjeta de prueba</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-credit-card-2-front text-primary"></i></span>
                        <input type="text" class="form-control bg-white font-monospace" id="culqiSimuladorTarjeta" value="4111 1111 1111 1111" readonly>
                    </div>
                    <div class="form-text text-muted" style="font-size: 11px;">Tarjeta Visa de prueba oficial de Culqi Sandbox.</div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">Vencimiento</label>
                        <input type="text" class="form-control text-center" id="culqiSimuladorVence" value="12/28" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold text-secondary mb-1">CVV</label>
                        <input type="password" class="form-control text-center font-monospace" id="culqiSimuladorCvv" value="123" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Correo del titular</label>
                    <input type="email" class="form-control" id="culqiSimuladorEmail" value="{{ $pagoOnline['email'] ?? '' }}" placeholder="turista@ejemplo.com">
                </div>

                <div id="culqiSimuladorError" class="alert alert-danger py-2 small d-none" role="alert"></div>

                <button type="button" id="btnCulqiSimuladorPagar" class="btn btn-gold w-100 py-2 fw-bold shadow-sm">
                    <i class="bi bi-shield-lock-fill me-1"></i> Pagar con Culqi ahora
                </button>
            </div>
            <div class="p-2 text-center text-muted small border-top bg-white" style="font-size: 11px;">
                <i class="bi bi-lock-fill text-success"></i> Transacción cifrada con tokenización Culqi (PCI-DSS)
            </div>
        </div>
    </div>
</div>
<script src="https://js.culqi.com/checkout-js"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('modalPagar');
    const modal = new bootstrap.Modal(modalEl);
    const fmt = n => (Math.round(n * 100) / 100).toFixed(2);
    let actual = null; // { id, codigo, pagable, anticipo }

    // ---------- Abrir el modal desde cualquier tarjeta ----------
    document.querySelectorAll('.btn-pagar').forEach(btn => {
        btn.addEventListener('click', function () {
            const d = this.dataset;
            actual = { id: d.id, codigo: d.codigo, pagable: parseFloat(d.pagable), anticipo: parseFloat(d.anticipo) };
            document.getElementById('pagarCodigo').textContent = d.codigo;
            document.getElementById('pagarTotal').textContent = 'S/ ' + fmt(parseFloat(d.total));
            document.getElementById('pagarPagado').textContent = 'S/ ' + fmt(parseFloat(d.pagado));
            document.getElementById('pagarSaldo').textContent = 'S/ ' + fmt(actual.pagable);

            // Voucher
            document.getElementById('voucherCotizacionId').value = d.id;
            const vm = document.getElementById('voucherMonto');
            vm.max = fmt(actual.pagable);
            vm.value = fmt(actual.anticipo > 0 ? actual.anticipo : actual.pagable);

            if (window.pagoOnline) window.pagoOnline.preparar(actual);
            modal.show();
        });
    });

    @if($pagoOnline)
    // ---------- Pago en línea con Culqi Checkout Custom ----------
    const cfg = {
        publicKey: @json($pagoOnline['public_key']),
        recargoPct: @json($pagoOnline['porcentaje_recargo']),
        email: @json($pagoOnline['email']),
        url: @json(route('portal.pago.online')),
        csrf: @json(csrf_token()),
    };
    const inputMonto = document.getElementById('pagoOnlineMonto');
    const btnContinuar = document.getElementById('pagoOnlineContinuar');
    const cajaError = document.getElementById('pagoOnlineError');
    const rapidos = document.querySelectorAll('.btn-monto-rapido');
    let culqi = null;

    const calcular = (base) => {
        const montoBase = Math.round((parseFloat(base) || 0) * 100) / 100;
        const recargo = Math.round(montoBase * cfg.recargoPct) / 100;
        const total = Math.round((montoBase + recargo) * 100) / 100;
        return { montoBase, recargo, total, centimos: Math.round(total * 100) };
    };
    const ocultarError = () => cajaError.classList.add('d-none');
    const mostrarError = (msg) => {
        cajaError.textContent = msg;
        cajaError.classList.remove('d-none');
        bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-pago-online')).show();
        modal.show();
    };
    const refrescar = () => {
        const c = calcular(inputMonto.value);
        document.getElementById('pagoOnlineBase').textContent = fmt(c.montoBase);
        document.getElementById('pagoOnlineRecargo').textContent = fmt(c.recargo);
        document.getElementById('pagoOnlineTotal').textContent = fmt(c.total);
        rapidos.forEach(b => {
            const objetivo = b.dataset.tipo === 'anticipo' ? actual?.anticipo : actual?.pagable;
            b.classList.toggle('active', Math.abs(c.montoBase - (objetivo || 0)) < 0.001);
        });
    };
    inputMonto.addEventListener('input', refrescar);
    rapidos.forEach(b => b.addEventListener('click', function () {
        inputMonto.value = fmt(this.dataset.tipo === 'anticipo' ? actual.anticipo : actual.pagable);
        refrescar();
    }));

    window.pagoOnline = {
        preparar(a) {
            ocultarError();
            const btnAnticipo = document.querySelector('.btn-monto-rapido[data-tipo="anticipo"]');
            btnAnticipo.classList.toggle('d-none', !(a.anticipo > 0 && a.anticipo < a.pagable));
            document.querySelector('[data-monto-anticipo]').textContent = 'S/ ' + fmt(a.anticipo);
            document.querySelector('[data-monto-total]').textContent = 'S/ ' + fmt(a.pagable);
            inputMonto.max = fmt(a.pagable);
            inputMonto.value = fmt(a.anticipo > 0 ? a.anticipo : a.pagable);
            refrescar();
            bootstrap.Tab.getOrCreateInstance(document.getElementById('tab-pago-online')).show();
        },
    };

    btnContinuar.addEventListener('click', function () {
        ocultarError();
        const c = calcular(inputMonto.value);
        if (c.montoBase <= 0 || c.montoBase > actual.pagable + 0.001) {
            return mostrarError(`Ingresa un monto entre S/ 0.01 y S/ ${fmt(actual.pagable)}.`);
        }

        // Si la librería de Culqi no cargó por DNS o se usa la llave del simulador, usar el modal integrado
        if (typeof CulqiCheckout === 'undefined' || cfg.publicKey.includes('simulador')) {
            modal.hide();
            document.getElementById('culqiSimuladorTotal').textContent = 'S/ ' + fmt(c.total);
            document.getElementById('culqiSimuladorConcepto').textContent = '18 Experience - Cotización ' + actual.codigo;
            document.getElementById('culqiSimuladorEmail').value = cfg.email || '';
            const errBox = document.getElementById('culqiSimuladorError');
            if (errBox) errBox.classList.add('d-none');
            const modalSimulador = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCulqiSimulador'));
            modalSimulador.show();
            return;
        }

        culqi = new CulqiCheckout(cfg.publicKey, {
            settings: { title: '18 Grados Sur', currency: 'PEN', amount: c.centimos },
            client: { email: cfg.email || '' },
            options: {
                lang: 'auto',
                installments: false,
                modal: true,
                paymentMethods: { tarjeta: true, yape: true, billetera: false, bancaMovil: false, agente: false, cuotealo: false },
                paymentMethodsSort: ['tarjeta', 'yape'],
            },
            appearance: { theme: 'default', menuType: 'sidebar', defaultStyle: { buttonBackground: '#1e3a8a' } },
        });

        culqi.culqi = async function () {
            if (!culqi.token) {
                culqi.close();
                return mostrarError(culqi.error?.user_message || 'No se pudo procesar el medio de pago.');
            }
            const token = culqi.token.id;
            culqi.close();
            modal.show();
            btnContinuar.disabled = true;
            btnContinuar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando pago...';
            try {
                const resp = await fetch(cfg.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                    body: JSON.stringify({ id_cotizacion: actual.id, token: token, monto_base: c.montoBase }),
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.ok) {
                    window.location.href = data.redirect;
                    return;
                }
                mostrarError(data.mensaje || (data.errors ? Object.values(data.errors).flat().join(' ') : 'No se pudo completar el pago.'));
            } catch (e) {
                mostrarError('Error de conexión. Antes de reintentar, revisa en el historial si el pago ya figura.');
            } finally {
                btnContinuar.disabled = false;
                btnContinuar.innerHTML = '<i class="bi bi-lock me-1"></i> Continuar al pago seguro';
            }
        };

        modal.hide();
        culqi.open();
    });

    const btnSimuladorPagar = document.getElementById('btnCulqiSimuladorPagar');
    if (btnSimuladorPagar) {
        btnSimuladorPagar.addEventListener('click', async function () {
            const c = calcular(inputMonto.value);
            const token = 'tkn_test_' + Math.random().toString(36).substring(2, 12);
            btnSimuladorPagar.disabled = true;
            btnSimuladorPagar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando pago con Culqi...';

            try {
                const resp = await fetch(cfg.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                    body: JSON.stringify({ id_cotizacion: actual.id, token: token, monto_base: c.montoBase }),
                });
                const data = await resp.json().catch(() => ({}));
                if (resp.ok && data.ok) {
                    window.location.href = data.redirect;
                    return;
                }
                const errBox = document.getElementById('culqiSimuladorError');
                if (errBox) {
                    errBox.textContent = data.mensaje || (data.errors ? Object.values(data.errors).flat().join(' ') : 'No se pudo completar el pago.');
                    errBox.classList.remove('d-none');
                }
            } catch (e) {
                const errBox = document.getElementById('culqiSimuladorError');
                if (errBox) {
                    errBox.textContent = 'Error de conexión con el servidor.';
                    errBox.classList.remove('d-none');
                }
            } finally {
                btnSimuladorPagar.disabled = false;
                btnSimuladorPagar.innerHTML = '<i class="bi bi-shield-lock-fill me-1"></i> Pagar con Culqi ahora';
            }
        });
    }
    @endif
});
</script>
@endsection
