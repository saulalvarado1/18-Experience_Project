{{-- Paleta e identidad visual de 18 Grados Sur. La incluyen layouts/app y layouts/guest
     para que todas las pantallas usen exactamente los mismos colores. --}}
<link rel="icon" type="image/png" href="{{ asset('img/logo-18-grados-sur.png') }}">
<meta name="theme-color" content="#0f1b3d">
<style>
    :root {
        --brand-primary: #1e3a8a;          /* azul marino: acciones principales */
        --brand-primary-rgb: 30, 58, 138;
        --brand-primary-hover: #172e6e;
        --brand-gold: #e0a800;             /* dorado del logo: acentos */
        --brand-gold-soft: rgba(224, 168, 0, 0.18);
        --brand-sidebar: #0f1b3d;
        --brand-sidebar-dark: #0a1430;
        --brand-bg: #f4f6fb;

        --bs-primary: var(--brand-primary);
        --bs-primary-rgb: var(--brand-primary-rgb);
        --bs-link-color: var(--brand-primary);
        --bs-link-color-rgb: var(--brand-primary-rgb);
        --bs-link-hover-color: var(--brand-primary-hover);
        --bs-body-font-family: 'Segoe UI', system-ui, -apple-system, Roboto, sans-serif;
    }
    .btn-primary {
        --bs-btn-bg: var(--brand-primary);
        --bs-btn-border-color: var(--brand-primary);
        --bs-btn-hover-bg: var(--brand-primary-hover);
        --bs-btn-hover-border-color: var(--brand-primary-hover);
        --bs-btn-active-bg: var(--brand-primary-hover);
        --bs-btn-active-border-color: var(--brand-primary-hover);
        --bs-btn-disabled-bg: var(--brand-primary);
        --bs-btn-disabled-border-color: var(--brand-primary);
    }
    .btn-outline-primary {
        --bs-btn-color: var(--brand-primary);
        --bs-btn-border-color: var(--brand-primary);
        --bs-btn-hover-bg: var(--brand-primary);
        --bs-btn-hover-border-color: var(--brand-primary);
        --bs-btn-active-bg: var(--brand-primary);
        --bs-btn-active-border-color: var(--brand-primary);
    }
    .btn-gold {
        --bs-btn-color: #1a1a1a;
        --bs-btn-bg: var(--brand-gold);
        --bs-btn-border-color: var(--brand-gold);
        --bs-btn-hover-color: #1a1a1a;
        --bs-btn-hover-bg: #c99700;
        --bs-btn-hover-border-color: #c99700;
        --bs-btn-active-color: #1a1a1a;
        --bs-btn-active-bg: #b38600;
        --bs-btn-active-border-color: #b38600;
        font-weight: 600;
    }
    .text-gold { color: var(--brand-gold) !important; }
    .bg-brand { background-color: var(--brand-sidebar) !important; color: #fff; }
    .form-control:focus, .form-select:focus {
        border-color: rgba(var(--brand-primary-rgb), .5);
        box-shadow: 0 0 0 .2rem rgba(var(--brand-primary-rgb), .15);
    }
    .form-check-input:checked { background-color: var(--brand-primary); border-color: var(--brand-primary); }
    .page-link { color: var(--brand-primary); }
    .active > .page-link, .page-link.active { background-color: var(--brand-primary); border-color: var(--brand-primary); }
    .nav-pills .nav-link.active { background-color: var(--brand-primary); }

    /* Tarjetas y encabezados con el mismo estilo en todo el sistema */
    .card { border-radius: .9rem; }
    .card.shadow-sm { box-shadow: 0 1px 3px rgba(15, 23, 42, .08) !important; }
    .modal-content { border: 0; border-radius: .9rem; }
    .modal-header.bg-primary, .card-header.bg-primary { background-color: var(--brand-primary) !important; }
    .table > thead.table-light th, .table > thead.table-dark th {
        font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b;
        background-color: #f8fafc; border-bottom-width: 1px; font-weight: 700;
    }
</style>
