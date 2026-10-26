<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Seguridad\Controllers\AuthController;
use App\Modules\Seguridad\Controllers\DashboardController;
use App\Modules\Seguridad\Controllers\UsuarioController;
use App\Modules\Clientes\Controllers\ClienteController;
use App\Modules\Catalogo\Controllers\DestinoController;
use App\Modules\Catalogo\Controllers\ProductoController;
use App\Modules\Catalogo\Controllers\SalidaController;
use App\Modules\Ventas\Controllers\CotizacionController;
use App\Modules\Ventas\Controllers\ReservaController;
use App\Modules\Finanzas\Controllers\PagoController;
use App\Modules\Reportes\Controllers\ReporteController;

// --- Rutas Públicas de Acceso (CU01) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/captcha/refresh', [AuthController::class, 'refreshCaptcha'])->name('captcha.refresh');

// --- Rutas Protegidas por Autenticación ---
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Inicio / Dashboard (CU02) - Accesible para todos los roles autenticados
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Sub-módulos para GERENTE y COUNTER ---
    Route::middleware(['rol:GERENTE,COUNTER'])->group(function () {
        // Clientes (CU06, CU07)
        Route::get('/clientes', [ClienteController::class, 'index'])->name('clientes.index');
        Route::post('/clientes', [ClienteController::class, 'store'])->name('clientes.store');
        Route::put('/clientes/{id}', [ClienteController::class, 'update'])->name('clientes.update');
        Route::post('/clientes/{id}/toggle', [ClienteController::class, 'toggleEstado'])->name('clientes.toggle');
        Route::get('/clientes/{id}/historial', [ClienteController::class, 'historial'])->name('clientes.historial');

        // Destinos (CU08)
        Route::get('/destinos', [DestinoController::class, 'index'])->name('destinos.index');
        Route::post('/destinos', [DestinoController::class, 'store'])->name('destinos.store');
        Route::put('/destinos/{id}', [DestinoController::class, 'update'])->name('destinos.update');
        Route::post('/destinos/{id}/toggle', [DestinoController::class, 'toggleEstado'])->name('destinos.toggle');

        // Tours y Paquetes (CU09, CU10, CU11)
        Route::get('/tours', [ProductoController::class, 'index'])->name('tours.index');
        Route::post('/tours', [ProductoController::class, 'store'])->name('tours.store');
        Route::post('/tours/{id}/precio', [ProductoController::class, 'cambiarPrecio'])->name('tours.precio');

        // Salidas y Cupos (CU12, CU13, CU14)
        Route::get('/salidas', [SalidaController::class, 'index'])->name('salidas.index');
        Route::post('/salidas', [SalidaController::class, 'store'])->name('salidas.store');
        Route::post('/salidas/{id}/capacidad', [SalidaController::class, 'cambiarCapacidad'])->name('salidas.capacidad');
        Route::post('/salidas/{id}/cancelar', [SalidaController::class, 'cancelar'])->name('salidas.cancelar');

        // Cotizaciones (CU15, CU16, CU17)
        Route::get('/cotizaciones', [CotizacionController::class, 'index'])->name('cotizaciones.index');
        Route::get('/cotizaciones/crear', [CotizacionController::class, 'create'])->name('cotizaciones.create');
        Route::post('/cotizaciones', [CotizacionController::class, 'store'])->name('cotizaciones.store');
        Route::get('/cotizaciones/{id}', [CotizacionController::class, 'show'])->name('cotizaciones.show');
        Route::post('/cotizaciones/{id}/estado', [CotizacionController::class, 'cambiarEstado'])->name('cotizaciones.estado');
        Route::get('/cotizaciones/{id}/pdf', [CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');

        // Reservas (CU20, CU21, CU22, CU23)
        Route::get('/reservas', [ReservaController::class, 'index'])->name('reservas.index');
        Route::post('/reservas', [ReservaController::class, 'store'])->name('reservas.store');
        Route::get('/reservas/{id}', [ReservaController::class, 'show'])->name('reservas.show');
        Route::post('/reservas/{id}/cancelar', [ReservaController::class, 'cancelar'])->name('reservas.cancelar');
        Route::post('/reservas/{id}/completar', [ReservaController::class, 'completar'])->name('reservas.completar');
    });

    // --- Sub-módulos para GERENTE, COUNTER y CONTABILIDAD ---
    Route::middleware(['rol:GERENTE,COUNTER,CONTABILIDAD'])->group(function () {
        // Pagos - Listado, Registro y Visualización de comprobante (CU18)
        Route::get('/pagos', [PagoController::class, 'index'])->name('pagos.index');
        Route::post('/pagos', [PagoController::class, 'store'])->name('pagos.store');
        Route::get('/pagos/{id}/comprobante', [PagoController::class, 'comprobante'])->name('pagos.comprobante');
    });

    // --- Sub-módulos para GERENTE y CONTABILIDAD ---
    Route::middleware(['rol:GERENTE,CONTABILIDAD'])->group(function () {
        // Validación / Rechazo de Pagos (CU19)
        Route::post('/pagos/{id}/validar', [PagoController::class, 'validar'])->name('pagos.validar');
        Route::post('/pagos/{id}/rechazar', [PagoController::class, 'rechazar'])->name('pagos.rechazar');

        // Reportes Gerenciales y de Operación (CU24)
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/pdf', [ReporteController::class, 'pdf'])->name('reportes.pdf');
    });

    // --- Sub-módulos exclusivos para GERENTE ---
    Route::middleware(['rol:GERENTE'])->group(function () {
        // Gestión de Usuarios del Sistema (CU04, CU05)
        Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
        Route::post('/usuarios/{id}/toggle', [UsuarioController::class, 'toggleEstado'])->name('usuarios.toggle');
    });
});
