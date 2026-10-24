<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - 18 Experience</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-cover {
            background: linear-gradient(135deg, rgba(22, 101, 52, 0.85), rgba(15, 23, 42, 0.9)), url('https://images.unsplash.com/photo-1526778548025-fa2f459cd5c1?auto=format&fit=crop&w=800&q=80') center/cover;
            color: #fff;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .login-cover h2 {
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .brand-badge {
            display: inline-block;
            background: #eab308;
            color: #000;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }
        .captcha-box {
            background: #e2e8f0;
            font-family: 'Courier New', Courier, monospace;
            font-size: 1.5rem;
            font-weight: bold;
            letter-spacing: 6px;
            color: #0f172a;
            padding: 6px 14px;
            border-radius: 6px;
            user-select: none;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="card login-card mx-auto">
        <div class="row g-0">
            <!-- Columna Izquierda: Imagen y Marca -->
            <div class="col-md-5 login-cover d-none d-md-flex">
                <div>
                    <span class="brand-badge"><i class="bi bi-geo-alt-fill"></i> TACNA - PERÚ</span>
                    <h2>18 EXPERIENCE</h2>
                    <p class="text-light opacity-75">18 Grados Sur — Agencia de Viajes & Tour Operador</p>
                </div>
                <div class="mt-4">
                    <p class="small mb-1"><i class="bi bi-shield-check"></i> Sistema Interno de Gestión Comercial</p>
                    <p class="small opacity-75">Reservas • Salidas • Cotizaciones • Pagos</p>
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Acceso -->
            <div class="col-md-7 p-4 p-lg-5 bg-white">
                <div class="text-center mb-4">
                    <h3 class="fw-bold text-dark mb-1">Iniciar Sesión</h3>
                    <p class="text-muted small">Ingrese sus credenciales y código de verificación</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="usuario" class="form-label small fw-semibold text-secondary">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="usuario" name="usuario" value="{{ old('usuario') }}" placeholder="Ej: admin o counter1" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold text-secondary">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary d-block">Código CAPTCHA</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="captcha-box" id="captcha-display">{{ $captcha }}</span>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-refresh-captcha" title="Generar nuevo código">
                                <i class="bi bi-arrow-clockwise"></i> NUEVO
                            </button>
                        </div>
                        <input type="text" class="form-control text-uppercase" id="captcha" name="captcha" placeholder="Ingrese el código de 5 caracteres" maxlength="5" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="background-color: #1e3a8a; border: none;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> INICIAR SESIÓN
                    </button>
                </form>

                <div class="text-center mt-4">
                    <small class="text-muted">18 Grados Sur © 2026 — Acceso Restringido</small>
                </div>
            </div>
        </div>
    </div>
</div>

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

</body>
</html>
