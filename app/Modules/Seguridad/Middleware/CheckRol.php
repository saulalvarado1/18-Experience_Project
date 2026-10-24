<?php

namespace App\Modules\Seguridad\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRol
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $usuario = Auth::user();

        if (!$usuario->tieneRol(...$roles)) {
            abort(403, 'No tiene permisos suficientes para acceder a este módulo.');
        }

        return $next($request);
    }
}
