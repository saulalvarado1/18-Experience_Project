<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Seguridad\Controllers\AuthController;
use App\Modules\Seguridad\Controllers\DashboardController;
use App\Modules\Clientes\Controllers\ClienteController;

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
