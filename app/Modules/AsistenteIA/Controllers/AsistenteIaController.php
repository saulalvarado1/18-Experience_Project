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
     * Muestra la interfaz gráfica del Asistente de IA (Chat y Recomendaciones).
     */
    public function index(): View
    {
        $contexto = $this->service->obtenerContextoTuristico();

        // Preguntas frecuentes predefinidas para facilitar la interacción con 1 clic
        $preguntasSugeridas = [
            '¿Qué tour me recomiendas para hacer en familia con niños o abuelos?',
            'Quiero visitar aguas termales y naturaleza en Tacna, ¿qué opción tienen?',
            'Tengo solo medio día libre por la tarde, ¿qué circuito puedo realizar?',
            'Me interesa conocer la gastronomía y hacer cata de piscos en bodegas.',
            '¿Qué precauciones debo tomar para ir al Valle de los Géiseres por la altura?'
        ];

        return view('asistente.chat', [
            'destinos' => $contexto['destinos'],
            'tours' => $contexto['tours'],
            'salidas' => $contexto['salidas'],
            'preguntasSugeridas' => $preguntasSugeridas,
        ]);
    }

    /**
     * Procesa la consulta vía AJAX / JSON y devuelve la recomendación generada.
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

        $resultado = $this->service->recomendar($mensaje, $historial);

        return response()->json($resultado);
    }
}
