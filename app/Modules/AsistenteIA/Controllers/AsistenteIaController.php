<?php

namespace App\Modules\AsistenteIA\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AsistenteIA\Services\RecomendadorIaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AsistenteIaController extends Controller
{
    protected RecomendadorIaService $service;

    public function __construct(RecomendadorIaService $service)
    {
        $this->service = $service;
    }

    /**
     * Muestra la interfaz gráfica del Asistente de IA (Chat, Sugerencias Dinámicas y Métricas BI).
     */
    public function index(): View
    {
        $contexto = $this->service->obtenerContextoTuristico();
        $preguntasSugeridas = $this->service->obtenerPreguntasFrecuentes(5);
        $metricas = $this->service->obtenerMetricasDemanda();

        return view('asistente.chat', [
            'destinos' => $contexto['destinos'],
            'tours' => $contexto['tours'],
            'salidas' => $contexto['salidas'],
            'preguntasSugeridas' => $preguntasSugeridas,
            'metricas' => $metricas,
        ]);
    }

    /**
     * Procesa la consulta vía AJAX / JSON y registra la analítica en la base de datos.
     */
    public function consultar(Request $request): JsonResponse
    {
        $request->validate([
            'mensaje' => ['required', 'string', 'min:2', 'max:1000'],
            'historial' => ['nullable', 'array'],
        ], [
            'mensaje.required' => 'Por favor escribe tu consulta.',
            'mensaje.min' => 'Tu mensaje debe tener al menos 2 caracteres.',
            'mensaje.max' => 'Tu consulta no puede exceder los 1000 caracteres.',
        ]);

        $mensaje = $request->input('mensaje');
        $historial = $request->input('historial', []);
        $idUsuario = auth()->check() ? auth()->user()->id_usuario : null;

        $resultado = $this->service->recomendar($mensaje, $historial, $idUsuario);

        return response()->json($resultado);
    }
}
