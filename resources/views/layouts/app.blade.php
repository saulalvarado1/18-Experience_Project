<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Inicio') - 18 Experience</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --brand-yellow: #d4a017;
            --brand-dark: #1b263b;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #334155;
        }
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background-color: #212529;
            color: #e2e8f0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            background-color: #111827;
            border-bottom: 2px solid #ca8a04;
        }
        .sidebar-brand h4 {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 0;
            color: #fbbf24;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0.75rem 0;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-nav .nav-link {
            color: #cbd5e1;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        .sidebar-nav .nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
            border-left: 4px solid #ca8a04;
        }
        .sidebar-nav .nav-link.active {
            color: #ffffff;
            background-color: rgba(202, 138, 4, 0.25);
            border-left: 4px solid #f59e0b;
        }
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
        }
        .badge-gerente { background-color: #dc2626; color: white; }
        .badge-counter { background-color: #2563eb; color: white; }
        .badge-contabilidad { background-color: #059669; color: white; }
    </style>
    @yield('styles')
</head>
<body>

<!-- Barra Lateral (Sidebar) -->
<nav id="sidebar">
    <div>
        <div class="sidebar-brand d-flex align-items-center justify-content-between">
            <h4>18 EXPERIENCE</h4>
        </div>

        <ul class="sidebar-nav">
            <!-- Módulos Generales -->
            <li class="nav-item">
                <a class="nav-link {{ request()->is('/') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-house-door"></i> INICIO
                </a>
            </li>

            @auth
                <!-- Counter y Gerente -->
                @if (auth()->user()->tieneRol('GERENTE', 'COUNTER'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('clientes*') ? 'active' : '' }}" href="{{ url('/clientes') }}">
                            <i class="bi bi-people"></i> CLIENTES
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('destinos*') ? 'active' : '' }}" href="{{ url('/destinos') }}">
                            <i class="bi bi-compass"></i> DESTINOS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('tours*') ? 'active' : '' }}" href="{{ url('/tours') }}">
                            <i class="bi bi-map"></i> TOURS Y PAQUETES
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('salidas*') ? 'active' : '' }}" href="{{ url('/salidas') }}">
                            <i class="bi bi-calendar-event"></i> SALIDAS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('cotizaciones*') ? 'active' : '' }}" href="{{ url('/cotizaciones') }}">
                            <i class="bi bi-receipt"></i> COTIZACIONES
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('reservas*') ? 'active' : '' }}" href="{{ url('/reservas') }}">
                            <i class="bi bi-bookmark-check"></i> RESERVAS
                        </a>
                    </li>
                @endif

                <!-- Pagos: Counter, Gerente y Contabilidad -->
                @if (auth()->user()->tieneRol('GERENTE', 'COUNTER', 'CONTABILIDAD'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('pagos*') ? 'active' : '' }}" href="{{ url('/pagos') }}">
                            <i class="bi bi-cash-stack"></i> PAGOS
                        </a>
                    </li>
                @endif

                <!-- Reportes: Gerente y Contabilidad -->
                @if (auth()->user()->tieneRol('GERENTE', 'CONTABILIDAD'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('reportes*') ? 'active' : '' }}" href="{{ url('/reportes') }}">
                            <i class="bi bi-bar-chart-line"></i> REPORTES
                        </a>
                    </li>
                @endif

                <!-- Solo Gerente -->
                @if (auth()->user()->tieneRol('GERENTE'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('usuarios*') ? 'active' : '' }}" href="{{ url('/usuarios') }}">
                            <i class="bi bi-person-gear"></i> USUARIOS
                        </a>
                    </li>
                @endif
            @endauth
        </ul>
    </div>

    <!-- Botón Cerrar Sesión en Base del Menú -->
    <div class="p-3 border-top border-secondary">
        <form action="{{ route('logout') }}" method="POST" id="form-logout">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100 btn-sm fw-semibold">
                <i class="bi bi-box-arrow-left me-1"></i> CERRAR SESIÓN
            </button>
        </form>
    </div>
</nav>

<!-- Contenedor Principal -->
<div id="main-content">
    <!-- Barra Superior -->
    <header class="topbar d-flex justify-content-between align-items-center">
        <div>
            <h5 class="m-0 fw-bold text-dark">@yield('page_title', 'Panel de Control')</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            @auth
                <div class="text-end">
                    <span class="d-block fw-semibold text-dark small">{{ auth()->user()->persona?->nombre_completo ?? auth()->user()->nombre_usuario }}</span>
                    <span class="badge rounded-pill text-uppercase px-2 py-1 {{ auth()->user()->rol?->nombre === 'GERENTE' ? 'badge-gerente' : (auth()->user()->rol?->nombre === 'COUNTER' ? 'badge-counter' : 'badge-contabilidad') }}" style="font-size: 0.7rem;">
                        {{ auth()->user()->rol?->nombre }}
                    </span>
                </div>
            @endauth
        </div>
    </header>

    <!-- Contenido Dinámico de la Página -->
    <main class="p-4 flex-grow-1">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-white border-top py-3 text-center text-muted small">
        18 EXPERIENCE — 18 Grados Sur S.A.C. • Tacna, Perú © 2026
    </footer>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
