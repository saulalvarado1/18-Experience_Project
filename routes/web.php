<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Seguridad\Controllers\AuthController;
use App\Modules\Seguridad\Controllers\DashboardController;

// --- Rutas Públicas de Acceso (CU01) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/captcha/refresh', [AuthController::class, 'refreshCaptcha'])->name('captcha.refresh');

// --- Rutas Protegidas por Autenticación ---
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Inicio / Dashboard (CU02) - Accesible para todos los roles autenticados
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Sub-módulos protegidos por Rol ---
    Route::middleware(['rol:GERENTE,COUNTER'])->group(function () {
        // Clientes, Destinos, Tours, Salidas, Cotizaciones, Reservas
    });

    Route::middleware(['rol:GERENTE,COUNTER,CONTABILIDAD'])->group(function () {
        // Pagos
    });

    Route::middleware(['rol:GERENTE,CONTABILIDAD'])->group(function () {
        // Validación de Pagos y Reportes
    });

    Route::middleware(['rol:GERENTE'])->group(function () {
        // Gestión de Usuarios y Auditoría
    });
});
