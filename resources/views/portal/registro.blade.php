<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Turistas — 18 Grados Sur</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 2rem 1rem;
        }
        .register-card {
            max-width: 650px;
            width: 100%;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .register-header {
            background-color: #0056b3;
            color: #ffffff;
            padding: 2rem 2rem 1.5rem 2rem;
            text-align: center;
        }
        .register-header h3 {
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <i class="bi bi-person-plus-fill fs-1 text-warning mb-2 d-block"></i>
        <h3>REGISTRO DE CLIENTES Y TURISTAS</h3>
        <p class="mb-0 text-white-50 small">Crea tu cuenta para consultar cotizaciones, pagos y descargar tus boletos de viaje.</p>
    </div>

    <div class="p-4 p-md-5">
        @if (session('error'))
            <div class="alert alert-danger d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('registro.post') }}">
            @csrf

            <div class="row g-3 mb-3">
                <div class="col-md-5">
                    <label class="form-label small fw-bold text-muted">Tipo Documento <span class="text-danger">*</span></label>
                    <select name="tipo_documento" class="form-select" required>
                        <option value="DNI" {{ old('tipo_documento') == 'DNI' ? 'selected' : '' }}>DNI (8 dígitos)</option>
                        <option value="PASAPORTE" {{ old('tipo_documento') == 'PASAPORTE' ? 'selected' : '' }}>Pasaporte</option>
                        <option value="CARNET_EXTRANJERIA" {{ old('tipo_documento') == 'CARNET_EXTRANJERIA' ? 'selected' : '' }}>Carnet Ext.</option>
                    </select>
                </div>
                <div class="col-md-7">
                    <label class="form-label small fw-bold text-muted">N.º Documento <span class="text-danger">*</span></label>
                    <input type="text" name="numero_documento" class="form-control" placeholder="Ej: 71234567" value="{{ old('numero_documento') }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Nombres <span class="text-danger">*</span></label>
                    <input type="text" name="nombres" class="form-control" placeholder="Tus nombres" value="{{ old('nombres') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Apellidos <span class="text-danger">*</span></label>
                    <input type="text" name="apellidos" class="form-control" placeholder="Tus apellidos" value="{{ old('apellidos') }}" required>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Teléfono / WhatsApp <span class="text-danger">*</span></label>
                    <input type="text" name="telefono" class="form-control" placeholder="Ej: 952123456" value="{{ old('telefono') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Correo Electrónico <span class="text-danger">*</span></label>
                    <input type="email" name="correo" class="form-control" placeholder="correo@ejemplo.com" value="{{ old('correo') }}" required>
                </div>
            </div>

            <hr class="my-3">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Repite tu contraseña" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm mb-3">
                <i class="bi bi-check-circle me-1"></i> REGISTRARME Y ENTRAR AL PORTAL
            </button>

            <div class="text-center">
                <span class="text-muted small">¿Ya tienes una cuenta de cliente o eres personal de la agencia?</span>
                <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none small d-block mt-1">
                    <i class="bi bi-arrow-left me-1"></i> Iniciar Sesión
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
