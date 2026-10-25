<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Seguridad\Controllers\AuthController;
use App\Modules\Seguridad\Controllers\DashboardController;
use App\Modules\Clientes\Controllers\ClienteController;
use App\Modules\Catalogo\Controllers\DestinoController;
use App\Modules\Catalogo\Controllers\ProductoController;
use App\Modules\Catalogo\Controllers\SalidaController;

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
    });

    // --- Sub-módulos para GERENTE, COUNTER y CONTABILIDAD ---
    Route::middleware(['rol:GERENTE,COUNTER,CONTABILIDAD'])->group(function () {
        // Pagos
    });

    // --- Sub-módulos para GERENTE y CONTABILIDAD ---
    Route::middleware(['rol:GERENTE,CONTABILIDAD'])->group(function () {
        // Validación de Pagos y Reportes
    });

    // --- Sub-módulos exclusivos para GERENTE ---
    Route::middleware(['rol:GERENTE'])->group(function () {
        // Gestión de Usuarios y Auditoría
    });
});
