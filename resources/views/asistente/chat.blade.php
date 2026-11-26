@extends('layouts.app')

@section('title', 'Asistente IA de Recomendaciones')
@section('page_title', 'Asistente Turístico con Inteligencia Artificial')

@section('content')
<div class="container-fluid px-2 px-md-3 py-2">
    <div class="row g-3">
        <!-- Panel Lateral: Destinos, Sugerencias Rápidas y Métricas BI -->
        <div class="col-lg-4 col-xl-3">
            <!-- Tarjeta de bienvenida de Inti -->
            <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: linear-gradient(135deg, #0f766e 0%, #115e59 100%); color: white;">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="bg-white text-teal p-2 rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; color: #0f766e;">
                            <i class="bi bi-robot fs-3"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-white">Inti AI</h5>
                            <span class="badge bg-light text-dark fw-bold rounded-pill" style="font-size: 0.7rem;">
                                <i class="bi bi-circle-fill text-success" style="font-size: 8px;"></i> Activo • RAG Tacna
                            </span>
                        </div>
                    </div>
                    <p class="small text-white-50 mb-0">
                        Asistente turístico conectado en tiempo real con la base de datos de <strong>18 Experience</strong> para recomendar destinos, tours y cupos en Tacna.
                    </p>
                </div>
            </div>

            <!-- Preguntas Sugeridas / Más Frecuentes (Dinámicas con BI) -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark small"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Preguntas Más Frecuentes</span>
                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">Top Consultas</span>
                </div>
                <div class="card-body p-2 p-md-3 pt-0">
                    <div class="d-flex flex-column gap-2" id="quick-questions">
                        @foreach($preguntasSugeridas as $sugerencia)
                            <button type="button" class="btn btn-outline-secondary btn-sm text-start rounded-3 p-2 d-flex align-items-start gap-2 border-1 bg-light bg-opacity-50 hover-shadow transition" onclick="usarPreguntaSugerida(this)" style="font-size: 0.8rem;">
                                <i class="bi bi-chat-dots text-teal mt-0.5" style="color: #0d9488;"></i>
                                <span class="text-secondary fw-semibold">{{ $sugerencia }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Destinos Registrados en Tacna -->
            <div class="card border-0 shadow-sm rounded-4 mb-3">
                <div class="card-header bg-white border-0 pt-3 pb-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-bold text-dark small"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Destinos Activos ({{ count($destinos) }})</span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size: 0.7rem;">PostgreSQL</span>
                </div>
                <div class="card-body p-2 p-md-3 pt-0">
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($destinos as $dest)
                            <span class="badge bg-light text-dark border p-2 rounded-3 text-start mb-1 w-100 d-flex justify-content-between align-items-center">
                                <span>
                                    <strong>{{ $dest->nombre }}</strong>
                                    <small class="d-block text-muted" style="font-size: 0.7rem;">{{ $dest->ubicacion }}</small>
                                </span>
                                <span class="badge bg-teal bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.65rem;">
                                    {{ round($dest->altitud_msnm) }} msnm
                                </span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            @if(auth()->check() && auth()->user()->tieneRol('GERENTE', 'COUNTER'))
                <!-- Botón de Analítica de Demanda BI para Personal -->
                <button type="button" class="btn btn-outline-teal w-100 rounded-4 py-2.5 shadow-sm fw-bold d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalMetricasIa" style="color: #0f766e; border-color: #0f766e;">
                    <i class="bi bi-bar-chart-fill"></i> Analítica de Demanda IA (BI)
                </button>
            @endif
        </div>

        <!-- Panel Central: Chat de Conversación -->
        <div class="col-lg-8 col-xl-9">
            <div class="card border-0 shadow-sm rounded-4 d-flex flex-column" style="height: calc(100vh - 140px); min-height: 520px;">
                <!-- Header del Chat -->
                <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between rounded-top-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="p-2 rounded-circle" style="background-color: rgba(13, 148, 136, 0.1); color: #0d9488;">
                            <i class="bi bi-stars fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">Asistente Virtual de Rutas y Recomendaciones</h6>
                            <small class="text-muted" id="chat-engine-badge">Motor Híbrido: Gemini 1.5 Flash + Respaldo Heurístico</small>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="limpiarChat()" title="Reiniciar chat">
                            <i class="bi bi-trash3 me-1"></i> <span class="d-none d-sm-inline">Limpiar</span>
                        </button>
                    </div>
                </div>

                <!-- Cuerpo de Mensajes (Scrollable) -->
                <div class="card-body p-3 p-md-4 overflow-auto flex-grow-1" id="chat-messages-container" style="background-color: #f8fafc;">
                    <!-- Mensaje inicial del bot -->
                    <div class="d-flex gap-2 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #0f766e;">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div class="p-3 rounded-4 shadow-sm bg-white border" style="max-width: 85%;">
                            <div class="fw-bold text-dark small mb-1">Inti • 18 Experience</div>
                            <div class="text-secondary small" style="line-height: 1.6;">
                                ¡Hola! 👋 Soy <strong>Inti</strong>, tu asesor inteligente para viajes y experiencias turísticas en Tacna.
                                <br><br>
                                Conozco todos los circuitos de la agencia: <strong>Géiseres de Candarave</strong>, <strong>Petroglifos de Miculla</strong>, <strong>Ruta del Pisco en Pocollay</strong>, <strong>Alto de la Alianza</strong> y <strong>Tarata</strong>.
                                <br><br>
                                Cuéntame: ¿con quién viajas, cuántos días tienes o qué tipo de experiencia buscas? Con gusto te recomendaré el mejor itinerario.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Footer -->
                <div class="card-footer bg-white border-top p-2 p-md-3 rounded-bottom-4">
                    <form id="form-chat" onsubmit="enviarMensaje(event)" class="d-flex gap-2 align-items-center">
                        <input type="text" id="input-mensaje" class="form-control form-control-lg border-2 rounded-pill px-3 px-md-4 fs-6" placeholder="Escribe tu consulta turística aquí (ej. tour para niños, aguas termales...)" autocomplete="off" required>
                        <button type="submit" id="btn-enviar" class="btn btn-lg rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0 shadow-sm" style="width: 48px; height: 48px; background: #0d9488;">
                            <i class="bi bi-send-fill" id="btn-icon"></i>
                            <div class="spinner-border spinner-border-sm text-light d-none" id="btn-spinner" role="status"></div>
                        </button>
                    </form>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-2 text-muted" style="font-size: 0.72rem;">
                        <span><i class="bi bi-shield-check text-success me-1"></i> Precios y cupos sincronizados con 18 Experience</span>
                        <span class="d-none d-sm-inline">Presiona <kbd class="bg-light text-dark border">Enter</kbd> para enviar</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Analítica de Demanda Turística (BI) -->
@if(auth()->check() && auth()->user()->tieneRol('GERENTE', 'COUNTER'))
<div class="modal fade" id="modalMetricasIa" tabindex="-1" aria-labelledby="modalMetricasIaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3 bg-light rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up-arrow fs-4 text-teal" style="color: #0f766e;"></i>
                    <h5 class="modal-title fw-bold text-dark" id="modalMetricasIaLabel">Analítica de Consultas IA (Business Intelligence)</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Tarjetas KPI -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-md-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small fw-semibold">TOTAL CONSULTAS</span>
                            <h3 class="fw-bold text-teal mb-0" style="color: #0f766e;">{{ $metricas['total_consultas'] }}</h3>
                            <small class="text-muted">Preguntas registradas</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small fw-semibold">TOP CATEGORÍA</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">
                                {{ $metricas['por_categoria']->first()?->categoria_detectada ?? 'N/A' }}
                            </h5>
                            <small class="text-muted">{{ $metricas['por_categoria']->first()?->total ?? 0 }} consultas</small>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <span class="text-muted small fw-semibold">ESTADO DEL MOTOR</span>
                            <h6 class="fw-bold text-success mb-0 mt-1">
                                <i class="bi bi-shield-check"></i> Activo y Resiliente
                            </h6>
                            <small class="text-muted">Gemini RAG + Heurístico</small>
                        </div>
                    </div>
                </div>

                <!-- Desglose por Categoría de Interés -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-pie-chart-fill text-teal me-1" style="color: #0f766e;"></i> Demanda de Viajeros por Tipo de Experiencia:</h6>
                <div class="mb-4">
                    @forelse($metricas['por_categoria'] as $cat)
                        @php
                            $pct = $metricas['total_consultas'] > 0 ? round(($cat->total / $metricas['total_consultas']) * 100) : 0;
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small fw-semibold mb-1">
                                <span>{{ str_replace('_', ' ', $cat->categoria_detectada) }}</span>
                                <span class="text-muted">{{ $cat->total }} consultas ({{ $pct }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: {{ $pct }}%; background-color: #0d9488;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">No hay suficientes datos registrados aún.</p>
                    @endforelse
                </div>

                <!-- Últimas consultas realizadas -->
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-teal me-1" style="color: #0f766e;"></i> Historial Reciente de Consultas:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Usuario</th>
                                <th>Pregunta</th>
                                <th>Categoría</th>
                                <th>Motor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($metricas['ultimas'] as $u)
                                <tr>
                                    <td class="text-muted">{{ $u->creado_en ? $u->creado_en->format('d/m H:i') : '-' }}</td>
                                    <td>{{ $u->usuario?->nombre_usuario ?? 'Visitante' }}</td>
                                    <td class="text-truncate" style="max-width: 200px;">{{ $u->pregunta }}</td>
                                    <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $u->categoria_detectada }}</span></td>
                                    <td><span class="badge bg-info bg-opacity-10 text-info">{{ $u->motor_usado }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-2">Sin consultas recientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-2 bg-light rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const chatContainer = document.getElementById('chat-messages-container');
    const inputMensaje = document.getElementById('input-mensaje');
    const btnEnviar = document.getElementById('btn-enviar');
    const btnIcon = document.getElementById('btn-icon');
    const btnSpinner = document.getElementById('btn-spinner');
    let historialChat = [];

    function usarPreguntaSugerida(btn) {
        const texto = btn.querySelector('span').innerText.trim();
        inputMensaje.value = texto;
        inputMensaje.focus();
        document.getElementById('form-chat').dispatchEvent(new Event('submit'));
    }

    function scrollAlFondo() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function formatearTextoMarkdown(texto) {
        if (!texto) return '';
        let html = texto
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n\n/g, '<br><br>')
            .replace(/\n/g, '<br>');
        return html;
    }

    async function enviarMensaje(e) {
        e.preventDefault();
        const texto = inputMensaje.value.trim();
        if (!texto) return;

        agregarBurbujaUsuario(texto);
        inputMensaje.value = '';
        inputMensaje.disabled = true;
        btnEnviar.disabled = true;
        btnIcon.classList.add('d-none');
        btnSpinner.classList.remove('d-none');

        const indicadorId = agregarIndicadorEscribiendo();
        scrollAlFondo();

        try {
            const response = await fetch("{{ route('asistente.consultar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    mensaje: texto,
                    historial: historialChat
                })
            });

            const data = await response.json();
            removerIndicadorEscribiendo(indicadorId);

            if (data.exito) {
                agregarBurbujaBot(data.respuesta, data.tours || [], data.motor);
                historialChat.push({ role: 'user', content: texto });
                historialChat.push({ role: 'assistant', content: data.respuesta });
            } else {
                agregarBurbujaBot(data.respuesta || 'Ocurrió un inconveniente al procesar tu consulta. Intenta nuevamente.');
            }
        } catch (error) {
            console.error('Error al consultar Asistente IA:', error);
            removerIndicadorEscribiendo(indicadorId);
            agregarBurbujaBot('⚠️ No fue posible conectar con el servicio de IA en este momento. Por favor verifica tu conexión.');
        } finally {
            inputMensaje.disabled = false;
            btnEnviar.disabled = false;
            btnIcon.classList.remove('d-none');
            btnSpinner.classList.add('d-none');
            inputMensaje.focus();
            scrollAlFondo();
        }
    }

    function agregarBurbujaUsuario(texto) {
        const div = document.createElement('div');
        div.className = 'd-flex justify-content-end mb-3';
        div.innerHTML = `
            <div class="p-3 rounded-4 shadow-sm text-white" style="max-width: 80%; background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
                <div class="fw-semibold small">${formatearTextoMarkdown(texto)}</div>
            </div>
        `;
        chatContainer.appendChild(div);
    }

    function agregarBurbujaBot(texto, tours = [], motor = '') {
        const div = document.createElement('div');
        div.className = 'd-flex gap-2 mb-3';

        let toursHtml = '';
        if (tours.length > 0) {
            toursHtml = `
                <div class="mt-3 pt-2 border-top">
                    <div class="fw-bold text-dark small mb-2"><i class="bi bi-compass-fill text-teal me-1" style="color: #0d9488;"></i> Tours recomendados para esta experiencia:</div>
                    <div class="row g-2">
                        ${tours.map(t => `
                            <div class="col-sm-6">
                                <div class="card h-100 border rounded-3 p-2 bg-light bg-opacity-50">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="badge bg-secondary" style="font-size: 0.65rem;">${t.codigo || 'TOUR'}</span>
                                        <span class="fw-bold text-success" style="font-size: 0.85rem;">S/ ${parseFloat(t.precio_base).toFixed(2)}</span>
                                    </div>
                                    <div class="fw-bold text-dark small mt-1">${t.nombre}</div>
                                    <div class="text-muted" style="font-size: 0.72rem;">Duración: ${t.duracion_dias} día(s)</div>
                                    <div class="mt-2">
                                        <a href="{{ route('cotizaciones.create') }}?tour_id=${t.id_producto}" class="btn btn-sm btn-outline-teal w-100 rounded-pill py-1 text-center" style="font-size: 0.72rem; color: #0f766e; border-color: #0f766e;">
                                            <i class="bi bi-calculator me-1"></i> Cotizar este Tour
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        let motorBadge = '';
        if (motor) {
            motorBadge = `<span class="badge bg-light text-muted border float-end" style="font-size: 0.65rem;">${motor}</span>`;
        }

        div.innerHTML = `
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #0f766e;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="p-3 rounded-4 shadow-sm bg-white border" style="max-width: 85%;">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="fw-bold text-dark small">Inti • Asistente</span>
                    ${motorBadge}
                </div>
                <div class="text-secondary small" style="line-height: 1.6;">
                    ${formatearTextoMarkdown(texto)}
                </div>
                ${toursHtml}
            </div>
        `;
        chatContainer.appendChild(div);
        scrollAlFondo();
    }

    function agregarIndicadorEscribiendo() {
        const id = 'writing-indicator-' + Date.now();
        const div = document.createElement('div');
        div.id = id;
        div.className = 'd-flex gap-2 mb-3';
        div.innerHTML = `
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #0f766e;">
                <i class="bi bi-robot"></i>
            </div>
            <div class="p-3 rounded-4 shadow-sm bg-white border d-flex align-items-center gap-2" style="max-width: 85%;">
                <span class="spinner-grow spinner-grow-sm text-teal" style="color: #0d9488;" role="status"></span>
                <span class="text-muted small">Inti está buscando las mejores opciones en Tacna...</span>
            </div>
        `;
        chatContainer.appendChild(div);
        return id;
    }

    function removerIndicadorEscribiendo(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function limpiarChat() {
        if (!confirm('¿Deseas reiniciar la conversación?')) return;
        historialChat = [];
        chatContainer.innerHTML = `
            <div class="d-flex gap-2 mb-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 38px; height: 38px; background: #0f766e;">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="p-3 rounded-4 shadow-sm bg-white border" style="max-width: 85%;">
                    <div class="fw-bold text-dark small mb-1">Inti • 18 Experience</div>
                    <div class="text-secondary small" style="line-height: 1.6;">
                        ¡Conversación reiniciada! ¿En qué más puedo ayudarte a planificar en Tacna?
                    </div>
                </div>
            </div>
        `;
        scrollAlFondo();
    }
</script>
@endsection
