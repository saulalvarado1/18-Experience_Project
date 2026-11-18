{{--
    Sección de servicios y pasajeros compartida por CU20 (generar) y CU21 (modificar reserva).
    Variables:
      $items   array de ['clave' => int, 'nombre' => string, 'salida' => ?Salida, 'cantidad' => int,
                         'punto_recojo' => string, 'pasajeros' => array<int, array>] (valores iniciales)
      $titular Persona titular de la cotización (botón "El cliente es pasajero")
    Los nombres de campo son servicios[clave][punto_recojo] y servicios[clave][pasajeros][i][campo];
    old() tiene prioridad sobre los valores iniciales.
--}}
<style>
    .servicio-card { border: 0; border-radius: .9rem; box-shadow: 0 1px 3px rgba(15, 23, 42, .08); }
    .servicio-card .card-header { background: #fff; border-bottom: 1px solid #eef1f6; border-radius: .9rem .9rem 0 0; }
    .pasajero-fila { border: 1px solid #e5e9f2; border-radius: .75rem; padding: .85rem; background: #fbfcfe; }
    .pasajero-fila.completa { border-color: rgba(5, 150, 105, .45); background: #f6fdf9; }
    .pasajero-num {
        width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
        background: rgba(var(--brand-primary-rgb), .1); color: var(--brand-primary); font-weight: 700; font-size: .85rem;
    }
    .pasajero-fila.completa .pasajero-num { background: #059669; color: #fff; }
    .servicio-card .form-label { font-size: .78rem; font-weight: 600; color: #475569; margin-bottom: .2rem; }
</style>

@php
    $titularJs = [
        'tipo_documento' => $titular->tipo_documento,
        'numero_documento' => $titular->numero_documento,
        'nombres' => $titular->nombres,
        'apellidos' => $titular->apellidos,
        'fecha_nacimiento' => $titular->fecha_nacimiento?->format('Y-m-d'),
    ];
    $tiposDoc = ['DNI' => 'DNI', 'PASAPORTE' => 'Pasaporte', 'CARNET_EXTRANJERIA' => 'Carné ext.'];
@endphp

@foreach($items as $item)
    @php
        $k = $item['clave'];
        $base = "servicios.$k";
        $salida = $item['salida'];
    @endphp
    <div class="card servicio-card mb-4" data-servicio="{{ $k }}" data-requeridos="{{ $item['cantidad'] }}">
        <div class="card-header py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="fw-bold text-dark"><i class="bi bi-geo-alt-fill text-gold me-1"></i>{{ $item['nombre'] }}</div>
                <small class="text-muted">
                    @if($salida && $salida->fecha)
                        Salida {{ $salida->fecha->format('d/m/Y') }}{{ $salida->hora ? ' · ' . substr($salida->hora, 0, 5) . ' h' : '' }}
                    @else
                        Fecha por coordinar
                    @endif
                    · {{ $item['cantidad'] }} {{ $item['cantidad'] == 1 ? 'pasajero' : 'pasajeros' }}
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border contador-pasajeros">0 / {{ $item['cantidad'] }}</span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-cliente-pasajero">
                    <i class="bi bi-person-check me-1"></i> El cliente es pasajero
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3" style="max-width: 520px;">
                <label class="form-label" for="recojo-{{ $k }}">Punto de recojo <span class="text-danger">*</span></label>
                <input type="text" id="recojo-{{ $k }}" name="servicios[{{ $k }}][punto_recojo]" class="form-control"
                    value="{{ old("$base.punto_recojo", $item['punto_recojo']) }}" maxlength="255" required>
            </div>

            <div class="d-flex flex-column gap-3">
                @for($i = 0; $i < $item['cantidad']; $i++)
                    @php
                        $p = "$base.pasajeros.$i";
                        $n = "servicios[$k][pasajeros][$i]";
                        $ini = $item['pasajeros'][$i] ?? [];
                        $v = fn ($campo, $defecto = null) => old("$p.$campo", $ini[$campo] ?? $defecto);
                    @endphp
                    <div class="pasajero-fila">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="pasajero-num">{{ $i + 1 }}</span>
                            <span class="badge estado-persona d-none"></span>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-4 col-lg-2">
                                <label class="form-label">Documento</label>
                                <select name="{{ $n }}[tipo_documento]" class="form-select form-select-sm campo-tipo">
                                    @foreach($tiposDoc as $val => $txt)
                                        <option value="{{ $val }}" {{ $v('tipo_documento', 'DNI') === $val ? 'selected' : '' }}>{{ $txt }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-8 col-lg-3">
                                <label class="form-label">Número <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="{{ $n }}[numero_documento]" class="form-control campo-numero" value="{{ $v('numero_documento') }}" maxlength="30" autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary btn-buscar-persona" title="Buscar persona" aria-label="Buscar persona"><i class="bi bi-search"></i></button>
                                </div>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label class="form-label">Nombres <span class="text-danger">*</span></label>
                                <input type="text" name="{{ $n }}[nombres]" class="form-control form-control-sm campo-nombres" value="{{ $v('nombres') }}" maxlength="100">
                            </div>
                            <div class="col-sm-6 col-lg-4">
                                <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" name="{{ $n }}[apellidos]" class="form-control form-control-sm campo-apellidos" value="{{ $v('apellidos') }}" maxlength="120">
                            </div>
                            <div class="col-sm-4 col-lg-2">
                                <label class="form-label">Fecha de nacimiento</label>
                                <input type="date" name="{{ $n }}[fecha_nacimiento]" class="form-control form-control-sm campo-nacimiento" value="{{ $v('fecha_nacimiento') }}" max="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-sm-8 col-lg-5">
                                <label class="form-label"><i class="bi bi-egg-fried me-1"></i>Restricción alimentaria</label>
                                <input type="text" name="{{ $n }}[restriccion_alimentaria]" class="form-control form-control-sm" value="{{ $v('restriccion_alimentaria') }}" maxlength="500" placeholder="Ej: vegetariano, alergia al maní">
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label"><i class="bi bi-heart-pulse me-1"></i>Condición particular</label>
                                <input type="text" name="{{ $n }}[condicion_particular]" class="form-control form-control-sm" value="{{ $v('condicion_particular') }}" maxlength="500" placeholder="Ej: asma, movilidad reducida">
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const urlBuscar = @json(route('reservas.persona'));
    const titular = @json($titularJs);

    const filaCompleta = fila => ['.campo-numero', '.campo-nombres', '.campo-apellidos']
        .every(sel => fila.querySelector(sel).value.trim() !== '');

    function actualizarContador(card) {
        const filas = [...card.querySelectorAll('.pasajero-fila')];
        const llenas = filas.filter(f => { const ok = filaCompleta(f); f.classList.toggle('completa', ok); return ok; }).length;
        const badge = card.querySelector('.contador-pasajeros');
        badge.textContent = `${llenas} / ${card.dataset.requeridos}`;
        badge.className = 'badge contador-pasajeros ' + (llenas == card.dataset.requeridos ? 'bg-success' : 'bg-light text-dark border');
    }

    function marcar(fila, texto, clase) {
        const b = fila.querySelector('.estado-persona');
        b.textContent = texto;
        b.className = 'badge estado-persona ' + clase;
    }

    function bloquearNombres(fila, bloquear) {
        ['.campo-nombres', '.campo-apellidos'].forEach(sel => {
            const el = fila.querySelector(sel);
            el.readOnly = bloquear;
            el.classList.toggle('bg-light', bloquear);
        });
    }

    async function buscar(fila) {
        const tipo = fila.querySelector('.campo-tipo').value;
        const numero = fila.querySelector('.campo-numero').value.trim();
        if (!numero) return;
        marcar(fila, 'Buscando…', 'bg-light text-dark border');
        try {
            const resp = await fetch(`${urlBuscar}?tipo_documento=${encodeURIComponent(tipo)}&numero_documento=${encodeURIComponent(numero)}`, { headers: { Accept: 'application/json' } });
            if (!resp.ok) { marcar(fila, 'Documento inválido', 'bg-danger'); return; }
            const data = await resp.json();
            if (data.encontrado) {
                fila.querySelector('.campo-nombres').value = data.nombres;
                fila.querySelector('.campo-apellidos').value = data.apellidos;
                if (data.fecha_nacimiento) fila.querySelector('.campo-nacimiento').value = data.fecha_nacimiento;
                bloquearNombres(fila, true);
                marcar(fila, 'Persona registrada', 'bg-success');
            } else {
                bloquearNombres(fila, false);
                marcar(fila, 'Nueva: completa sus datos', 'bg-warning text-dark');
                fila.querySelector('.campo-nombres').focus();
            }
        } catch (e) {
            marcar(fila, 'No se pudo buscar', 'bg-danger');
        }
        actualizarContador(fila.closest('[data-servicio]'));
    }

    document.querySelectorAll('[data-servicio]').forEach(card => {
        card.querySelectorAll('.pasajero-fila').forEach(fila => {
            fila.querySelector('.btn-buscar-persona').addEventListener('click', () => buscar(fila));
            const num = fila.querySelector('.campo-numero');
            num.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); buscar(fila); } });
            num.addEventListener('change', () => buscar(fila));
            num.addEventListener('input', () => { bloquearNombres(fila, false); marcar(fila, '', 'd-none'); });
            fila.querySelectorAll('input').forEach(i => i.addEventListener('input', () => actualizarContador(card)));
        });

        card.querySelector('.btn-cliente-pasajero').addEventListener('click', () => {
            const filas = [...card.querySelectorAll('.pasajero-fila')];
            const yaEsta = filas.some(f => f.querySelector('.campo-numero').value.trim().toUpperCase() === String(titular.numero_documento).toUpperCase());
            if (yaEsta) return;
            const libre = filas.find(f => f.querySelector('.campo-numero').value.trim() === '');
            if (!libre) return;
            libre.querySelector('.campo-tipo').value = titular.tipo_documento;
            libre.querySelector('.campo-numero').value = titular.numero_documento;
            libre.querySelector('.campo-nombres').value = titular.nombres;
            libre.querySelector('.campo-apellidos').value = titular.apellidos;
            if (titular.fecha_nacimiento) libre.querySelector('.campo-nacimiento').value = titular.fecha_nacimiento;
            bloquearNombres(libre, true);
            marcar(libre, 'Cliente titular', 'bg-primary');
            actualizarContador(card);
        });

        actualizarContador(card);
    });
});
</script>
