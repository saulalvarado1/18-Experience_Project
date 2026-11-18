<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', '18 Experience') — 18 Grados Sur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @include('partials.marca')
    <style>
        body {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            color: #334155;
            background:
                radial-gradient(circle at 15% 20%, rgba(224, 168, 0, .18), transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(30, 58, 138, .55), transparent 45%),
                linear-gradient(160deg, var(--brand-sidebar-dark) 0%, var(--brand-sidebar) 55%, #1e3a8a 100%);
        }
        .auth-card {
            width: 100%;
            border: 0;
            border-radius: 1.1rem;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .45);
            background: #fff;
        }
        /* Columna de marca */
        .auth-cover {
            background:
                linear-gradient(180deg, rgba(10, 20, 48, .2), rgba(10, 20, 48, .85)),
                linear-gradient(135deg, var(--brand-primary), var(--brand-sidebar-dark));
            color: #fff;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        .auth-cover::after {           /* silueta de montañas, en eco con el logo */
            content: '';
            position: absolute; left: 0; right: 0; bottom: 0; height: 38%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 400 120' preserveAspectRatio='none'%3E%3Cpath d='M0 120 L60 60 L95 85 L160 25 L215 75 L250 50 L320 100 L360 70 L400 95 L400 120Z' fill='%23e0a800' fill-opacity='.14'/%3E%3Cpath d='M0 120 L45 90 L110 105 L175 65 L240 100 L300 80 L400 115 L400 120Z' fill='%23ffffff' fill-opacity='.06'/%3E%3C/svg%3E") bottom/100% 100% no-repeat;
            pointer-events: none;
        }
        .auth-cover > * { position: relative; z-index: 1; }
        .auth-cover .logo { height: 72px; width: auto; }
        .auth-cover h2 { font-weight: 800; letter-spacing: -.5px; }
        .auth-chip {
            display: inline-flex; align-items: center; gap: .35rem;
            background: rgba(224, 168, 0, .18); color: var(--brand-gold);
            border: 1px solid rgba(224, 168, 0, .35);
            font-weight: 700; font-size: .75rem; letter-spacing: .06em; text-transform: uppercase;
            padding: .3rem .75rem; border-radius: 2rem;
        }
        .auth-list li { display: flex; gap: .6rem; align-items: flex-start; margin-bottom: .6rem; font-size: .92rem; }
        .auth-list i { color: var(--brand-gold); }
        .auth-mobile-brand { display: none; }
        .auth-form .form-label { font-size: .82rem; font-weight: 600; color: #475569; }
        .auth-form .input-group-text { background: #f8fafc; color: #64748b; }
        .btn-toggle-pass { border-color: var(--bs-border-color); background: #f8fafc; color: #64748b; }
        @media (max-width: 767.98px) {
            .auth-mobile-brand {
                display: flex; align-items: center; justify-content: center; gap: .75rem;
                background: var(--brand-sidebar); padding: 1rem; border-bottom: 3px solid var(--brand-gold);
            }
            .auth-mobile-brand img { height: 46px; }
            .auth-mobile-brand span { color: #cbd5e1; font-size: .75rem; letter-spacing: .08em; text-transform: uppercase; }
        }
    </style>
    @yield('styles')
</head>
<body>
    @yield('content')

    <script>
        // Mostrar / ocultar contraseña
        document.querySelectorAll('[data-toggle-password]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.togglePassword);
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                btn.querySelector('i').className = visible ? 'bi bi-eye' : 'bi bi-eye-slash';
                btn.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
            });
        });
    </script>
    @yield('scripts')
</body>
</html>
