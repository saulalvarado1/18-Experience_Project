<?php

namespace App\Modules\Seguridad\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->check() && auth()->user()->tieneRol('CLIENTE')) {
            return redirect()->route('portal.dashboard');
        }

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

        $totalRecaudado = (float) DB::table('pago')
            ->where('estado', 'VALIDADO')
            ->sum('monto_base');

        // 2. Próximas salidas obtenidas de la vista vw_disponibilidad_salida (CU02)
        $proximasSalidas = DB::table('vw_disponibilidad_salida')
            ->whereDate('fecha', '>=', $hoy)
            ->whereIn('estado', ['PROGRAMADA', 'COMPLETA'])
            ->orderBy('fecha', 'asc')
            ->orderBy('hora', 'asc')
            ->limit(5)
            ->get();

        // 3. Gráficos Analíticos de Gestión (RF14)
        $ventasMes = DB::table('reserva')
            ->where('estado', '!=', 'CANCELADA')
            ->selectRaw("TO_CHAR(fecha_reserva, 'YYYY-MM') as mes, SUM(total_contratado) as total")
            ->groupBy(DB::raw("TO_CHAR(fecha_reserva, 'YYYY-MM')"))
            ->orderBy('mes', 'asc')
            ->limit(6)
            ->get();

        $labelsMes = $ventasMes->pluck('mes')->toArray();
        $dataVentas = $ventasMes->pluck('total')->map(fn($v) => (float)$v)->toArray();

        $demandaTours = DB::table('detalle_reserva as dr')
            ->join('producto_turistico as pt', 'pt.id_producto', '=', 'dr.id_producto')
            ->join('reserva as r', 'r.id_reserva', '=', 'dr.id_reserva')
            ->where('r.estado', '!=', 'CANCELADA')
            ->select('pt.nombre', DB::raw('SUM(dr.cantidad_pasajeros) as total_pasajeros'))
            ->groupBy('pt.nombre')
            ->get();

        $labelsTours = $demandaTours->pluck('nombre')->toArray();
        $dataTours = $demandaTours->pluck('total_pasajeros')->map(fn($v) => (int)$v)->toArray();

        return view('dashboard.index', compact(
            'reservasHoy',
            'salidasHoy',
            'pagosPendientes',
            'totalRecaudado',
            'proximasSalidas',
            'labelsMes',
            'dataVentas',
            'labelsTours',
            'dataTours'
        ));
    }
}
