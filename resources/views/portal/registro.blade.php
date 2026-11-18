@extends('layouts.guest')

@section('title', 'Crear cuenta de cliente')

@section('styles')
<style>
    .auth-card { max-width: 980px; }
    .auth-form .form-text { font-size: .75rem; }
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
        <div class="col-md-4 auth-cover d-none d-md-flex">
            <div>
                <img src="{{ asset('img/logo-18-grados-sur.png') }}" alt="18 Grados Sur" class="logo mb-4">
                <div><span class="auth-chip"><i class="bi bi-person-plus"></i> Portal del cliente</span></div>
                <h2 class="mt-3 mb-2 h3">Crea tu cuenta</h2>
                <p class="text-white-50 mb-0">En un minuto podrás cotizar, pagar y descargar tus boletos.</p>
            </div>
            <ol class="list-unstyled auth-list mt-5 mb-0">
                <li><i class="bi bi-1-circle-fill"></i> Elige un tour y solicita tu cotización</li>
                <li><i class="bi bi-2-circle-fill"></i> Acéptala y paga el anticipo en línea</li>
                <li><i class="bi bi-3-circle-fill"></i> Recibe tu boleto de viaje en PDF</li>
            </ol>
        </div>

        <!-- Formulario -->
        <div class="col-md-8 p-4 p-lg-5 auth-form">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Registro de clientes</h3>
                <p class="text-muted small mb-0">Los campos con <span class="text-danger">*</span> son obligatorios.</p>
            </div>

            @if (session('error'))
                <div class="alert alert-danger py-2 small d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Revisa los datos ingresados:</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('registro.post') }}">
                @csrf

                <h6 class="text-uppercase small fw-bold text-muted mb-3" style="letter-spacing:.06em;">Datos personales</h6>
                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label class="form-label" for="tipo_documento">Tipo de documento <span class="text-danger">*</span></label>
                        <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                            <option value="DNI" {{ old('tipo_documento') == 'DNI' ? 'selected' : '' }}>DNI</option>
                            <option value="PASAPORTE" {{ old('tipo_documento') == 'PASAPORTE' ? 'selected' : '' }}>Pasaporte</option>
                            <option value="CARNET_EXTRANJERIA" {{ old('tipo_documento') == 'CARNET_EXTRANJERIA' ? 'selected' : '' }}>Carné de extranjería</option>
                        </select>
                    </div>
                    <div class="col-sm-7">
                        <label class="form-label" for="numero_documento">N.º de documento <span class="text-danger">*</span></label>
                        <input type="text" name="numero_documento" id="numero_documento" class="form-control @error('numero_documento') is-invalid @enderror" placeholder="Ej: 71234567" value="{{ old('numero_documento') }}" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="nombres">Nombres <span class="text-danger">*</span></label>
                        <input type="text" name="nombres" id="nombres" class="form-control @error('nombres') is-invalid @enderror" value="{{ old('nombres') }}" autocomplete="given-name" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="apellidos">Apellidos <span class="text-danger">*</span></label>
                        <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos') }}" autocomplete="family-name" required>
                    </div>
                </div>

                <h6 class="text-uppercase small fw-bold text-muted mb-3 mt-4" style="letter-spacing:.06em;">Contacto y acceso</h6>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="telefono">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                        <input type="tel" name="telefono" id="telefono" class="form-control @error('telefono') is-invalid @enderror" placeholder="Ej: 952123456" value="{{ old('telefono') }}" autocomplete="tel" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="correo">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" name="correo" id="correo" class="form-control @error('correo') is-invalid @enderror" placeholder="correo@ejemplo.com" value="{{ old('correo') }}" autocomplete="email" required>
                        <div class="form-text">Será tu usuario para iniciar sesión.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password">Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" minlength="6" autocomplete="new-password" required>
                            <button type="button" class="btn btn-toggle-pass" data-toggle-password="password" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="form-text">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password_confirmation">Confirmar contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" minlength="6" autocomplete="new-password" required>
                            <button type="button" class="btn btn-toggle-pass" data-toggle-password="password_confirmation" aria-label="Mostrar contraseña"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold w-100 py-2 mt-2">
                    <i class="bi bi-check-circle me-1"></i> Crear cuenta y entrar al portal
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <span class="text-muted small">¿Ya tienes cuenta o eres parte del equipo?</span>
                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none small d-block mt-1">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
