@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('styles')
<style>
    .auth-card { max-width: 920px; }
    .captcha-box {
        background: repeating-linear-gradient(45deg, #eef2f9, #eef2f9 6px, #e3e9f4 6px, #e3e9f4 12px);
        border: 1px dashed #b6c2d9;
        font-family: 'Courier New', Courier, monospace;
        font-size: 1.45rem; font-weight: 700; letter-spacing: 6px;
        color: var(--brand-sidebar);
        padding: .3rem 1rem; border-radius: .5rem;
        user-select: none; flex: 1; text-align: center;
    }
</style>
@endsection

@section('content')
<div class="card auth-card">
    <div class="auth-mobile-brand">
        <img src="{{ asset('img/logo-18-grados-sur.png') }}" alt="18 Grados Sur">
        <span>18 Experience</span>
    </div>

    <div class="row g-0">
        <!-- Columna de marca (escritorio) -->
        <div class="col-md-5 auth-cover d-none d-md-flex">
            <div>
                <img src="{{ asset('img/logo-18-grados-sur.png') }}" alt="18 Grados Sur" class="logo mb-4">
                <div><span class="auth-chip"><i class="bi bi-geo-alt-fill"></i> Tacna · Perú</span></div>
                <h2 class="mt-3 mb-2">Bienvenido a 18 Experience</h2>
                <p class="text-white-50 mb-0">Agencia de viajes y operador turístico del sur del Perú.</p>
            </div>
            <ul class="list-unstyled auth-list mt-5 mb-0">
                <li><i class="bi bi-compass"></i> Tours y paquetes por Tacna y alrededores</li>
                <li><i class="bi bi-credit-card"></i> Paga en línea con tarjeta o Yape</li>
                <li><i class="bi bi-ticket-perforated"></i> Descarga tus boletos de viaje</li>
            </ul>
        </div>

        <!-- Formulario -->
        <div class="col-md-7 p-4 p-lg-5 auth-form">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Iniciar sesión</h3>
                <p class="text-muted small mb-0">Clientes y personal de la agencia.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small d-flex align-items-start" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                    <div>{{ $errors->first() }}</div>
                </div>
            @endif
            @if (session('success'))
                <div class="alert alert-success py-2 small" role="alert">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario o correo</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control @error('usuario') is-invalid @enderror" id="usuario" name="usuario" value="{{ old('usuario') }}" placeholder="tucorreo@ejemplo.com" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="btn btn-toggle-pass" data-toggle-password="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="captcha" class="form-label d-block">Código de verificación</label>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="captcha-box" id="captcha-display" aria-live="polite">{{ $captcha }}</span>
                        <button type="button" class="btn btn-outline-secondary" id="btn-refresh-captcha" title="Generar otro código" aria-label="Generar otro código">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                    </div>
                    <input type="text" class="form-control text-uppercase @error('captcha') is-invalid @enderror" id="captcha" name="captcha" placeholder="Escribe los 5 caracteres" maxlength="5" autocomplete="off" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                </button>
            </form>

            <div class="text-center mt-4 pt-4 border-top">
                <span class="text-muted small">¿Eres turista y aún no tienes cuenta?</span>
                <a href="{{ route('registro') }}" class="btn btn-gold w-100 mt-2">
                    <i class="bi bi-person-plus me-1"></i> Crear mi cuenta de cliente
                </a>
            </div>

            <p class="text-center text-muted small mt-4 mb-0">18 Grados Sur S.A.C. © {{ date('Y') }}</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('btn-refresh-captcha').addEventListener('click', function () {
        fetch('{{ route('captcha.refresh') }}')
            .then(response => response.json())
            .then(data => {
                document.getElementById('captcha-display').innerText = data.captcha;
                document.getElementById('captcha').value = '';
                document.getElementById('captcha').focus();
            });
    });
</script>
@endsection
