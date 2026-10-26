@extends('layouts.app')

@section('title', 'Nueva Cotización — 18 Grados Sur')

@section('content')
<div class="mb-4">
    <a href="{{ route('cotizaciones.index') }}" class="text-decoration-none text-muted mb-2 d-inline-block">
        <i class="bi bi-arrow-left me-1"></i> Volver a Cotizaciones
    </a>
    <h2 class="fw-bold text-dark mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>Emitir Nueva Cotización (CU15)</h2>
    <p class="text-muted mb-0">Seleccione el cliente activo y añada tours con salidas programadas.</p>
</div>

<form method="POST" action="{{ route('cotizaciones.store') }}" id="formCotizacion">
    @csrf
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 fw-bold">1. Datos del Cliente y Vigencia</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
                        <select name="id_cliente" class="form-select" required>
                            <option value="">-- Seleccione un Cliente --</option>
                            @foreach($clientes as $cli)
                            <option value="{{ $cli->id_cliente }}" {{ old('id_cliente') == $cli->id_cliente ? 'selected' : '' }}>
                                {{ $cli->persona->numero_documento }} — {{ $cli->persona->nombreCompleto() }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vigente hasta</label>
                        <input type="date" name="vigencia_hasta" class="form-control" value="{{ old('vigencia_hasta', now()->addDays(7)->format('Y-m-d')) }}">
                        <small class="text-muted">Por defecto: 7 días de vigencia.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea name="observacion" rows="3" class="form-control" placeholder="Notas internas o solicitudes del cliente">{{ old('observacion') }}</textarea>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold text-dark">Total Estimado:</span>
                        <span class="fs-4 fw-bold text-primary" id="lblTotalCotizacion">S/ 0.00</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3 shadow-sm py-2">
                        <i class="bi bi-save me-1"></i> Emitir Cotización
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <span class="fw-bold">2. Tours y Paquetes a Cotizar</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAgregarItem">
                        <i class="bi bi-plus-lg me-1"></i> Añadir Tour
                    </button>
                </div>
                <div class="card-body">
                    <div id="itemsContainer">
                        <!-- Items dinámicos -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<template id="itemTemplate">
    <div class="border rounded p-3 mb-3 item-row bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-primary item-numero">Ítem #1</span>
            <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-item">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="row g-2">
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Producto Turístico</label>
                <select class="form-select form-select-sm select-producto" required>
                    <option value="" data-precio="0">-- Seleccione Tour --</option>
                    @foreach($productos as $p)
                    <option value="{{ $p->id_producto }}" data-precio="{{ $p->precio_base }}">
                        {{ $p->codigo }} - {{ $p->nombre }} (S/ {{ number_format($p->precio_base, 2) }})
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Salida Programada</label>
                <select class="form-select form-select-sm select-salida">
                    <option value="">Cualquiera / Por definir</option>
                    @foreach($productos as $p)
                        @foreach($p->salidas as $s)
                        <option value="{{ $s->id_salida }}" data-producto="{{ $p->id_producto }}">
                            {{ $s->fecha ? $s->fecha->format('d/m/Y') : '' }} {{ $s->hora }}
                        </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Pasajeros</label>
                <input type="number" class="form-control form-control-sm input-pasajeros" value="1" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Precio Unit. (S/)</label>
                <input type="number" step="0.01" class="form-control form-control-sm input-precio" value="0.00" required>
            </div>
        </div>
        <div class="text-end mt-2">
            <span class="small text-muted me-2">Subtotal:</span>
            <span class="fw-bold text-success span-subtotal">S/ 0.00</span>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('itemsContainer');
    const template = document.getElementById('itemTemplate');
    const btnAgregar = document.getElementById('btnAgregarItem');
    const lblTotal = document.getElementById('lblTotalCotizacion');

    let itemIndex = 0;

    function recalcularTotales() {
        let total = 0;
        const rows = container.querySelectorAll('.item-row');
        rows.forEach((row, idx) => {
            row.querySelector('.item-numero').textContent = `Ítem #${idx + 1}`;
            const cant = parseInt(row.querySelector('.input-pasajeros').value) || 0;
            const precio = parseFloat(row.querySelector('.input-precio').value) || 0;
            const subtotal = cant * precio;
            row.querySelector('.span-subtotal').textContent = `S/ ${subtotal.toFixed(2)}`;
            total += subtotal;
        });
        lblTotal.textContent = `S/ ${total.toFixed(2)}`;
    }

    function agregarFila() {
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.item-row');
        const idx = itemIndex++;

        const selectProd = row.querySelector('.select-producto');
        selectProd.name = `items[${idx}][id_producto]`;

        const selectSalida = row.querySelector('.select-salida');
        selectSalida.name = `items[${idx}][id_salida]`;

        const inputPasajeros = row.querySelector('.input-pasajeros');
        inputPasajeros.name = `items[${idx}][cantidad_pasajeros]`;

        const inputPrecio = row.querySelector('.input-precio');
        inputPrecio.name = `items[${idx}][precio_unitario]`;

        selectProd.addEventListener('change', function () {
            const opt = selectProd.options[selectProd.selectedIndex];
            const precio = opt.getAttribute('data-precio') || 0;
            inputPrecio.value = parseFloat(precio).toFixed(2);

            // Filtrar salidas correspondientes al producto
            const prodId = selectProd.value;
            selectSalida.querySelectorAll('option').forEach((o, i) => {
                if (i === 0) return;
                o.style.display = (o.getAttribute('data-producto') === prodId) ? 'block' : 'none';
            });
            selectSalida.value = '';
            recalcularTotales();
        });

        inputPasajeros.addEventListener('input', recalcularTotales);
        inputPrecio.addEventListener('input', recalcularTotales);

        row.querySelector('.btn-eliminar-item').addEventListener('click', function () {
            if (container.querySelectorAll('.item-row').length > 1) {
                row.remove();
                recalcularTotales();
            } else {
                alert('Debe conservar al menos un ítem en la cotización.');
            }
        });

        container.appendChild(row);
        recalcularTotales();
    }

    btnAgregar.addEventListener('click', agregarFila);
    agregarFila(); // inicializar con un ítem
});
</script>
@endsection
