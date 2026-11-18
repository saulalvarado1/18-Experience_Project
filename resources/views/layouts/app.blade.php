<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Inicio') - 18 Experience</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @include('partials.marca')
    <style>
        :root { --sidebar-width: 250px; }
        body {
            background-color: var(--brand-bg);
            color: #334155;
        }

        /* ================= Barra lateral ================= */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            height: 100dvh;
            background-color: var(--brand-sidebar);
            color: #e2e8f0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1045;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            transition: transform .25s ease;
        }
        .sidebar-brand {
            padding: 1rem 1.25rem;
            background-color: var(--brand-sidebar-dark);
            border-bottom: 2px solid var(--brand-gold);
        }
        .sidebar-brand img { height: 44px; width: auto; }
        .sidebar-brand small {
            display: block;
            color: #94a3b8;
            font-size: .7rem;
            letter-spacing: .08em;
            text-transform: uppercase;
            margin-top: .25rem;
        }
        .sidebar-section {
            color: #64748b;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 1rem 1.5rem .35rem;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0 0 .75rem;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-nav .nav-link {
            color: #cbd5e1;
            padding: .65rem 1.5rem;
            font-weight: 500;
            font-size: .92rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 4px solid transparent;
            transition: background-color .15s ease, color .15s ease;
        }
        .sidebar-nav .nav-link i { font-size: 1.05rem; }
        .sidebar-nav .nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.06);
        }
        .sidebar-nav .nav-link.active {
            color: #ffffff;
            background-color: var(--brand-gold-soft);
            border-left-color: var(--brand-gold);
        }
        .sidebar-nav .nav-link.active i { color: var(--brand-gold); }

        /* ================= Contenido ================= */
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .btn-menu {
            border: 1px solid #e2e8f0;
            background: #fff;
            border-radius: .5rem;
            width: 40px;
            height: 40px;
            font-size: 1.35rem;
            line-height: 1;
            color: var(--brand-primary);
        }
        #sidebar-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 27, 61, .5);
            z-index: 1040;
        }
        .min-w-0 { min-width: 0; }
        .flash-stack .alert { box-shadow: 0 2px 8px rgba(15, 23, 42, .06); }

        .badge-gerente { background-color: #dc2626; color: white; }
        .badge-counter { background-color: #2563eb; color: white; }
        .badge-contabilidad { background-color: #059669; color: white; }
        .badge-cliente { background-color: #0284c7; color: white; }

        /* ================= Celular y tablet (< 992 px) ================= */
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); box-shadow: none; }
            body.sidebar-open #sidebar { transform: translateX(0); box-shadow: 0 0 40px rgba(0, 0, 0, .35); }
            body.sidebar-open #sidebar-backdrop { display: block; }
            body.sidebar-open { overflow: hidden; }
            #main-content { margin-left: 0; }
            .topbar { padding: .6rem 1rem; }
            main.p-4 { padding: 1rem !important; }
        }

        /* ===== Tablas → tarjetas en celular (se aplica sola a las tablas dentro de .table-responsive) ===== */
        @media (max-width: 767.98px) {
            .tabla-movil thead { display: none; }
            .tabla-movil, .tabla-movil tbody, .tabla-movil tr, .tabla-movil td { display: block; width: 100%; }
            .table-responsive:has(> .tabla-movil) { overflow: visible; }
            .tabla-movil { background: transparent; margin: 0; }
            .tabla-movil > tbody > tr {
                background: #fff; border: 1px solid #e5e9f2; border-radius: .75rem;
                margin: .75rem 0; padding: .4rem .85rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .05);
            }
            .card .tabla-movil > tbody > tr { margin: .75rem; width: auto; }
            /* Cada celda: etiqueta a la izquierda, valores apilados a la derecha */
            .tabla-movil > tbody > tr > td {
                display: grid; grid-template-columns: minmax(84px, 38%) 1fr; column-gap: 1rem; align-items: start;
                justify-items: end; border: 0; padding: .45rem 0 !important; text-align: right;
                background: transparent !important; box-shadow: none !important;
            }
            .tabla-movil > tbody > tr > td > * { grid-column: 2; min-width: 0; max-width: 100%; }
            .tabla-movil > tbody > tr > td + td { border-top: 1px dashed #eef1f6; }
            .tabla-movil > tbody > tr > td,
            .tabla-movil > tbody > tr > td * { white-space: normal !important; overflow-wrap: break-word; word-break: break-word; }
            .tabla-movil > tbody > tr > td::before {
                content: attr(data-label); grid-column: 1; grid-row: 1 / span 20; justify-self: start; text-align: left;
                font-size: .7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em;
                padding-top: .15rem;
            }
            .tabla-movil > tbody > tr > td:not([data-label])::before,
            .tabla-movil > tbody > tr > td[data-label=""]::before { display: none; }
            .tabla-movil > tbody > tr > td:not([data-label]),
            .tabla-movil > tbody > tr > td[data-label=""] { display: block; text-align: center; }
            .tabla-movil > tbody > tr > td.td-acciones { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .25rem; }
            .tabla-movil > tbody > tr > td.td-acciones::before { display: none; }
            .tabla-movil > tbody > tr > td > .btn-group { flex-wrap: wrap; justify-content: flex-end; }
        }

        @media (max-width: 575.98px) {
            .topbar-user-name { display: none !important; }
            /* Modales a pantalla completa en celular */
            .modal-dialog:not(.modal-keep-size) {
                margin: 0;
                max-width: none;
                min-height: 100%;
            }
            .modal-dialog:not(.modal-keep-size) .modal-content {
                min-height: 100vh;
                min-height: 100dvh;
                border: 0;
                border-radius: 0;
            }
            h2 { font-size: 1.4rem; }
        }

        /* ===== Paginación limpia y control de tamaño SVG ===== */
        nav svg, .pagination svg {
            max-height: 1rem !important;
            max-width: 1rem !important;
            width: 1rem !important;
            height: 1rem !important;
            vertical-align: middle;
        }
        .pagination {
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .pagination .page-item .page-link {
            border-radius: 0.375rem !important;
            padding: 0.35rem 0.65rem;
            font-size: 0.85rem;
        }
    </style>
    @yield('styles')
</head>
<body>

<!-- Barra Lateral (Sidebar) -->
<nav id="sidebar" aria-label="Menú principal">
    <div>
        <div class="sidebar-brand d-flex align-items-center justify-content-between">
            <a href="{{ auth()->check() && auth()->user()->tieneRol('CLIENTE') ? route('portal.dashboard') : route('dashboard') }}" class="text-decoration-none">
                <img src="{{ asset('img/logo-18-grados-sur.png') }}" alt="18 Grados Sur">
                <small>18 Experience</small>
            </a>
            <button type="button" class="btn btn-sm text-white-50 d-lg-none" id="btn-cerrar-menu" aria-label="Cerrar menú">
                <i class="bi bi-x-lg fs-5"></i>
            </button>
        </div>

        <ul class="sidebar-nav">
            @auth
                <!-- Menú Exclusivo para Clientes / Turistas -->
                @if (auth()->user()->tieneRol('CLIENTE'))
                    <li class="sidebar-section">Mi cuenta</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('mi-portal') ? 'active' : '' }}" href="{{ route('portal.dashboard') }}">
                            <i class="bi bi-house-door"></i> Mi portal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('mi-portal/cotizaciones*') ? 'active' : '' }}" href="{{ route('portal.cotizaciones') }}">
                            <i class="bi bi-receipt"></i> Mis cotizaciones
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('mi-portal/reservas*') ? 'active' : '' }}" href="{{ route('portal.reservas') }}">
                            <i class="bi bi-bookmark-check"></i> Mis reservas
                        </a>
                    </li>
                    <li class="sidebar-section">Explorar</li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('mi-portal/tours*') ? 'active' : '' }}" href="{{ route('portal.tours') }}">
                            <i class="bi bi-compass"></i> Catálogo de tours
                        </a>
                    </li>
                @else
                    <!-- Módulos Generales para Personal de la Agencia -->
                    <li class="nav-item mt-2">
                        <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="bi bi-house-door"></i> Inicio
                        </a>
                    </li>

                    <!-- Counter y Gerente -->
                    @if (auth()->user()->tieneRol('GERENTE', 'COUNTER'))
                        <li class="sidebar-section">Catálogo</li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}" href="{{ url('/clientes') }}">
                                <i class="bi bi-people"></i> Clientes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('destinos*') ? 'active' : '' }}" href="{{ url('/destinos') }}">
                                <i class="bi bi-compass"></i> Destinos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('tours*') ? 'active' : '' }}" href="{{ url('/tours') }}">
                                <i class="bi bi-map"></i> Tours y paquetes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('salidas*') ? 'active' : '' }}" href="{{ url('/salidas') }}">
                                <i class="bi bi-calendar-event"></i> Salidas
                            </a>
                        </li>
                        <li class="sidebar-section">Ventas</li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('cotizaciones*') ? 'active' : '' }}" href="{{ url('/cotizaciones') }}">
                                <i class="bi bi-receipt"></i> Cotizaciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('reservas*') ? 'active' : '' }}" href="{{ url('/reservas') }}">
                                <i class="bi bi-bookmark-check"></i> Reservas
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->tieneRol('GERENTE', 'COUNTER', 'CONTABILIDAD'))
                        <li class="sidebar-section">Finanzas</li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('pagos*') ? 'active' : '' }}" href="{{ url('/pagos') }}">
                                <i class="bi bi-cash-stack"></i> Pagos
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->tieneRol('GERENTE', 'CONTABILIDAD'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}" href="{{ url('/reportes') }}">
                                <i class="bi bi-bar-chart-line"></i> Reportes
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->tieneRol('GERENTE'))
                        <li class="sidebar-section">Administración</li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}" href="{{ url('/usuarios') }}">
                                <i class="bi bi-person-gear"></i> Usuarios
                            </a>
                        </li>
                    @endif
                @endif
            @endauth
        </ul>
    </div>

    <!-- Botón Cerrar Sesión en Base del Menú -->
    <div class="p-3 border-top" style="border-color: rgba(255,255,255,.08) !important;">
        <form action="{{ route('logout') }}" method="POST" id="form-logout">
            @csrf
            <button type="submit" class="btn btn-outline-light w-100 btn-sm fw-semibold">
                <i class="bi bi-box-arrow-left me-1"></i> Cerrar sesión
            </button>
        </form>
    </div>
</nav>
<div id="sidebar-backdrop"></div>

<!-- Contenedor Principal -->
<div id="main-content">
    <!-- Barra Superior -->
    <header class="topbar d-flex justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <button type="button" class="btn-menu d-lg-none" id="btn-abrir-menu" aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>
            <h5 class="m-0 fw-bold text-dark text-truncate">@yield('page_title', 'Panel de Control')</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            @auth
                <div class="text-end">
                    <span class="d-block fw-semibold text-dark small topbar-user-name">{{ auth()->user()->persona?->nombre_completo ?? auth()->user()->nombre_usuario }}</span>
                    <span class="badge rounded-pill text-uppercase px-2 py-1 {{ auth()->user()->rol?->nombre === 'GERENTE' ? 'badge-gerente' : (auth()->user()->rol?->nombre === 'COUNTER' ? 'badge-counter' : (auth()->user()->rol?->nombre === 'CLIENTE' ? 'badge-cliente' : 'badge-contabilidad')) }}" style="font-size: 0.7rem;">
                        {{ auth()->user()->rol?->nombre }}
                    </span>
                </div>
            @endauth
        </div>
    </header>

    <!-- Contenido Dinámico de la Página -->
    <main class="p-4 flex-grow-1">
        <div class="flash-stack">
            @foreach ([
                'success' => ['alert-success', 'bi-check-circle-fill'],
                'warning' => ['alert-warning', 'bi-exclamation-circle-fill'],
                'info'    => ['alert-info', 'bi-info-circle-fill'],
                'error'   => ['alert-danger', 'bi-exclamation-triangle-fill'],
            ] as $tipo => [$clase, $icono])
                @if (session($tipo))
                    <div class="alert {{ $clase }} alert-dismissible fade show d-flex align-items-start" role="alert">
                        <i class="bi {{ $icono }} me-2 mt-1"></i>
                        <div>{{ session($tipo) }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                @endif
            @endforeach

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                        <div>
                            <strong>Revisa los datos ingresados:</strong>
                            <ul class="mb-0 mt-1 ps-3">
                                @foreach ($errors->all() as $mensaje)
                                    <li>{{ $mensaje }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif
        </div>

        @yield('content')
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small">
        18 EXPERIENCE — 18 Grados Sur S.A.C. • Tacna, Perú © {{ date('Y') }}
    </footer>
</div>

<!-- Modal de confirmación reutilizable (reemplaza confirm() del navegador).
     Uso: <button data-confirm="¿Mensaje?" data-confirm-title="Título" data-confirm-ok="Sí, aceptar" data-confirm-variant="danger"> -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-keep-size">
        <div class="modal-content border-0 shadow">
            <div class="modal-body p-4 text-center">
                <div id="confirmarIcono" class="mb-3 fs-1"></div>
                <h5 class="fw-bold mb-2" id="confirmarTitulo">¿Confirmar acción?</h5>
                <p class="text-muted mb-0" id="confirmarMensaje"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="confirmarAceptar">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    // ---------- Menú lateral en celular ----------
    const body = document.body;
    const btnAbrir = document.getElementById('btn-abrir-menu');
    const abrir = () => { body.classList.add('sidebar-open'); btnAbrir?.setAttribute('aria-expanded', 'true'); };
    const cerrar = () => { body.classList.remove('sidebar-open'); btnAbrir?.setAttribute('aria-expanded', 'false'); };
    btnAbrir?.addEventListener('click', abrir);
    document.getElementById('btn-cerrar-menu')?.addEventListener('click', cerrar);
    document.getElementById('sidebar-backdrop')?.addEventListener('click', cerrar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrar(); });
    document.querySelectorAll('#sidebar .nav-link').forEach(a => a.addEventListener('click', cerrar));

    // ---------- Tablas → tarjetas en celular ----------
    // Copia el texto de cada encabezado como data-label de sus celdas (lo usa el CSS de .tabla-movil).
    document.querySelectorAll('main .table-responsive > table.table:not(.no-tabla-movil)').forEach(tabla => {
        const encabezados = [...tabla.querySelectorAll(':scope > thead th')].map(th => th.textContent.trim());
        if (!encabezados.length) return;
        tabla.classList.add('tabla-movil');
        tabla.querySelectorAll(':scope > tbody > tr').forEach(tr => {
            let col = 0;
            [...tr.children].forEach(td => {
                const span = parseInt(td.getAttribute('colspan') || '1', 10);
                if (span === 1 && !td.hasAttribute('data-label')) {
                    const etiqueta = encabezados[col] || '';
                    if (/^acci/i.test(etiqueta) || (td.classList.contains('text-end') && td.querySelector('.btn, form'))) {
                        td.classList.add('td-acciones');
                    } else {
                        td.setAttribute('data-label', etiqueta);
                    }
                }
                col += span;
            });
        });
    });

    // ---------- Confirmación con modal ----------
    const variantes = {
        primary: { btn: 'btn-primary', icono: 'bi-question-circle text-primary' },
        success: { btn: 'btn-success', icono: 'bi-check-circle text-success' },
        danger:  { btn: 'btn-danger',  icono: 'bi-exclamation-triangle text-danger' },
        warning: { btn: 'btn-warning', icono: 'bi-exclamation-circle text-warning' },
    };
    let pendiente = null;   // { form, submitter }
    let modal = null;

    function pedirConfirmacion(origen, form, submitter) {
        const v = variantes[origen.dataset.confirmVariant] || variantes.primary;
        modal ??= new bootstrap.Modal(document.getElementById('modalConfirmar'));
        document.getElementById('confirmarTitulo').textContent = origen.dataset.confirmTitle || '¿Confirmar acción?';
        document.getElementById('confirmarMensaje').textContent = origen.dataset.confirm;
        document.getElementById('confirmarIcono').innerHTML = `<i class="bi ${v.icono}"></i>`;
        const ok = document.getElementById('confirmarAceptar');
        ok.className = `btn ${v.btn} px-4`;
        ok.textContent = origen.dataset.confirmOk || 'Aceptar';
        pendiente = { form, submitter };
        modal.show();
    }

    // Botones de envío con data-confirm
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-confirm], input[type=submit][data-confirm]');
        if (!btn || !btn.form || btn.dataset.confirmado) return;
        if (btn.form.querySelector(':invalid')) return; // deja que el navegador muestre la validación
        e.preventDefault();
        pedirConfirmacion(btn, btn.form, btn);
    });

    // Formularios con data-confirm (cualquier botón que los envíe)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form.dataset.confirm || form.dataset.confirmado) return;
        e.preventDefault();
        pedirConfirmacion(form, form, e.submitter);
    });

    document.getElementById('confirmarAceptar').addEventListener('click', function () {
        if (!pendiente) return;
        const { form, submitter } = pendiente;
        pendiente = null;
        form.dataset.confirmado = '1';
        if (submitter) submitter.dataset.confirmado = '1';
        modal.hide();
        submitter ? form.requestSubmit(submitter) : form.requestSubmit();
    });
})();
</script>
@yield('scripts')
</body>
</html>
