<?php

namespace App\Modules\Seguridad\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Indicadores del día (CU02)
        $hoy = now()->toDateString();

        $reservasHoy = DB::table('reserva')
            ->whereDate('fecha_reserva', $hoy)
            ->where('estado', '<>', 'CANCELADA')
            ->count();

        $salidasHoy = DB::table('salida')
            ->whereDate('fecha', $hoy)
            ->where('estado', '<>', 'CANCELADA')
            ->count();

        $pagosPendientes = DB::table('pago')
            ->where('estado', 'PENDIENTE_VALIDACION')
            ->count();

        // 2. Próximas salidas obtenidas de la vista vw_disponibilidad_salida (CU02)
        $proximasSalidas = DB::table('vw_disponibilidad_salida')
            ->whereDate('fecha', '>=', $hoy)
            ->whereIn('estado', ['PROGRAMADA', 'COMPLETA'])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact('reservasHoy', 'salidasHoy', 'pagosPendientes', 'proximasSalidas'));
    }
}
