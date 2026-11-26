<?php

namespace App\Modules\AsistenteIA\Services;

use App\Modules\AsistenteIA\Models\AsistenteConsulta;
use App\Modules\Catalogo\Models\Destino;
use App\Modules\Catalogo\Models\ProductoTuristico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecomendadorIaService
{
    /**
     * Obtiene el contexto enriquecido de la base de datos (RAG).
     */
    public function obtenerContextoTuristico(): array
    {
        $destinos = Destino::where('activo', true)
            ->select('id_destino', 'nombre', 'descripcion', 'ubicacion', 'altitud_msnm', 'temporada_recomendada', 'recomendaciones')
            ->get();

        $tours = ProductoTuristico::where('activo', true)
            ->with(['destinos' => function ($q) {
                $q->select('destino.id_destino', 'nombre', 'altitud_msnm');
            }])
            ->select('id_producto', 'codigo', 'nombre', 'tipo_producto', 'descripcion', 'duracion_dias', 'precio_base', 'minimo_pasajeros')
            ->get();

        $salidas = [];
        try {
            $salidas = DB::table('vw_disponibilidad_salida')
                ->where('estado', 'PROGRAMADA')
                ->where('cupos_disponibles', '>', 0)
                ->orderBy('fecha', 'asc')
                ->limit(6)
                ->get();
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar vw_disponibilidad_salida en AsistenteIA: ' . $e->getMessage());
        }

        return [
            'destinos' => $destinos,
            'tours' => $tours,
            'salidas' => $salidas,
        ];
    }

    /**
     * Procesa la consulta del usuario, devuelve la recomendación y registra la métrica.
     */
    public function recomendar(string $mensaje, array $historial = [], ?int $idUsuario = null): array
    {
        $mensajeLimpio = trim($mensaje);
        if ($mensajeLimpio === '') {
            return [
                'exito' => false,
                'respuesta' => 'Por favor, escribe una pregunta o cuéntame qué tipo de experiencia buscas en Tacna.',
                'tours' => [],
                'destinos' => [],
                'motor' => 'Validador',
            ];
        }

        $contexto = $this->obtenerContextoTuristico();
        $apiKey = env('GEMINI_API_KEY') ?: config('services.gemini.key');
        $categoria = $this->clasificarCategoria($mensajeLimpio);

        // 1. Si hay API key de Gemini configurada, intentamos consultar el LLM
        if (!empty($apiKey)) {
            try {
                $resultadoGemini = $this->consultarGemini($mensajeLimpio, $contexto, $historial, $apiKey);
                if ($resultadoGemini !== null) {
                    $this->registrarConsulta($mensajeLimpio, $categoria, 'GEMINI_ONLINE', $idUsuario);
                    return $resultadoGemini;
                }
            } catch (\Throwable $e) {
                Log::error('Fallo en llamada a Gemini API, activando motor heurístico offline: ' . $e->getMessage());
            }
        }

        // 2. Motor heurístico de respaldo (100% offline, confiable para sustentación)
        $resultadoHeuristico = $this->generarRespuestaHeuristica($mensajeLimpio, $contexto);
        $this->registrarConsulta($mensajeLimpio, $categoria, 'HEURISTICO_OFFLINE', $idUsuario);

        return $resultadoHeuristico;
    }

    /**
     * Registra la consulta para auditoría y métricas de analítica de demanda (BI).
     */
    public function registrarConsulta(string $pregunta, string $categoria, string $motor, ?int $idUsuario = null): void
    {
        try {
            AsistenteConsulta::create([
                'id_usuario' => $idUsuario,
                'pregunta' => mb_substr($pregunta, 0, 500, 'UTF-8'),
                'categoria_detectada' => $categoria,
                'motor_usado' => $motor,
                'creado_en' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar asistente_consulta: ' . $e->getMessage());
        }
    }

    /**
     * Clasifica semánticamente la intención para métricas de demanda turística.
     */
    public function clasificarCategoria(string $mensaje): string
    {
        $q = mb_strtolower($mensaje, 'UTF-8');
        if (str_contains($q, 'geiser') || str_contains($q, 'géiser') || str_contains($q, 'terma') || str_contains($q, 'candarave') || str_contains($q, 'altura') || str_contains($q, 'frio')) {
            return 'ALTURA_TERMAS';
        }
        if (str_contains($q, 'familia') || str_contains($q, 'niño') || str_contains($q, 'abuelo') || str_contains($q, 'tranquilo') || str_contains($q, 'miculla') || str_contains($q, 'pachia')) {
            return 'FAMILIA_MICULLA';
        }
        if (str_contains($q, 'pisco') || str_contains($q, 'vino') || str_contains($q, 'comida') || str_contains($q, 'pocollay') || str_contains($q, 'bodega')) {
            return 'GASTRONOMIA_PISCO';
        }
        if (str_contains($q, 'medio dia') || str_contains($q, 'medio día') || str_contains($q, 'corto') || str_contains($q, 'alianza') || str_contains($q, 'historia')) {
            return 'HISTORIA_CORTO';
        }
        if (str_contains($q, 'tarata') || str_contains($q, 'anden') || str_contains($q, 'paisaje') || str_contains($q, 'trekking')) {
            return 'NATURALEZA_TARATA';
        }
        return 'CONSULTA_GENERAL';
    }

    /**
     * Obtiene las preguntas más frecuentes almacenadas o valores predeterminados.
     */
    public function obtenerPreguntasFrecuentes(int $limite = 5): array
    {
        $frecuentes = [];
        try {
            $frecuentes = AsistenteConsulta::select('pregunta', DB::raw('count(*) as total'))
                ->groupBy('pregunta')
                ->orderByDesc('total')
                ->limit($limite)
                ->pluck('pregunta')
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Error consultando preguntas frecuentes: ' . $e->getMessage());
        }

        // Sugerencias por defecto si aún no hay suficiente historial
        $predeterminadas = [
            '¿Qué tour me recomiendas para hacer en familia con niños o abuelos?',
            'Quiero visitar aguas termales y naturaleza en Tacna, ¿qué opción tienen?',
            'Tengo solo medio día libre por la tarde, ¿qué circuito puedo realizar?',
            'Me interesa conocer la gastronomía y hacer cata de piscos en bodegas.',
            '¿Qué precauciones debo tomar para ir al Valle de los Géiseres por la altura?'
        ];

        return array_values(array_unique(array_merge($frecuentes, $predeterminadas)));
    }

    /**
     * Métricas de analítica de demanda turística para el panel del Gerente.
     */
    public function obtenerMetricasDemanda(): array
    {
        try {
            $total = AsistenteConsulta::count();
            $porCategoria = AsistenteConsulta::select('categoria_detectada', DB::raw('count(*) as total'))
                ->groupBy('categoria_detectada')
                ->orderByDesc('total')
                ->get();

            $porMotor = AsistenteConsulta::select('motor_usado', DB::raw('count(*) as total'))
                ->groupBy('motor_usado')
                ->get();

            $ultimas = AsistenteConsulta::with('usuario')
                ->orderByDesc('creado_en')
                ->limit(10)
                ->get();

            return [
                'total_consultas' => $total,
                'por_categoria' => $porCategoria,
                'por_motor' => $porMotor,
                'ultimas' => $ultimas,
            ];
        } catch (\Throwable $e) {
            return [
                'total_consultas' => 0,
                'por_categoria' => collect(),
                'por_motor' => collect(),
                'ultimas' => collect(),
            ];
        }
    }

    /**
     * Consulta el modelo Gemini vía REST API con inyección de contexto (RAG).
     */
    protected function consultarGemini(string $mensaje, array $contexto, array $historial, string $apiKey): ?array
    {
        $destinosTexto = $contexto['destinos']->map(function ($d) {
            return "- {$d->nombre}: {$d->descripcion} (Altitud: {$d->altitud_msnm} msnm, Ubicación: {$d->ubicacion}, Tips: {$d->recomendaciones})";
        })->implode("\n");

        $toursTexto = $contexto['tours']->map(function ($t) {
            $destinos = $t->destinos->pluck('nombre')->implode(', ');
            return "- [ID: {$t->id_producto}] {$t->nombre} ({$t->codigo}): S/ {$t->precio_base} PEN, Duración: {$t->duracion_dias} día(s). Destinos: {$destinos}. {$t->descripcion}";
        })->implode("\n");

        $salidasTexto = collect($contexto['salidas'])->map(function ($s) {
            return "- Salida del producto ID {$s->id_producto} el {$s->fecha} a las {$s->hora} ({$s->cupos_disponibles} cupos libres)";
        })->implode("\n");

        $systemPrompt = <<<PROMPT
Eres "Inti", el asistente virtual de recomendaciones turísticas de la agencia "18 Experience" (18 Grados Sur) en Tacna, Perú.
Tu misión es asesorar a clientes y counters de manera cálida, profesional y persuasiva, recomendando exclusivamente los destinos y tours que la agencia ofrece.

CATÁLOGO REAL DE DESTINOS EN TACNA:
{$destinosTexto}

TOURS Y PAQUETES DISPONIBLES:
{$toursTexto}

PRÓXIMAS SALIDAS PROGRAMADAS:
{$salidasTexto}

REGLAS OBLIGATORIAS:
1. Responde en español peruano formal y hospitalario.
2. Menciona precios exactos en Soles (PEN) según el catálogo.
3. Si el destino supera los 3,500 msnm (como Géiseres de Candarave a 4,300 msnm), advierte sobre aclimatación, ropa abrigadora y precauciones de salud.
4. Si viajan con niños o adultos mayores, sugiere tours de baja altitud y caminatas suaves (como Miculla o Ruta del Pisco).
5. Si el usuario te hace preguntas ajenas al turismo, la agencia o los viajes en Tacna (como resolver ecuaciones, política, fútbol o tareas escolares), declina educadamente indicando que tu única función es asesorar sobre turismo y los paquetes de 18 Experience.
6. Finaliza invitando cordialmente a solicitar una cotización o registrar su reserva.
7. Al final de tu respuesta, en una línea separada, incluye la etiqueta: [TOURS_SUGERIDOS: id1, id2] con los IDs numéricos de los tours recomendados.
PROMPT;

        $modelo = env('GEMINI_MODEL') ?: config('services.gemini.model', 'gemini-3.1-flash-lite');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$apiKey}";

        $response = Http::timeout(10)->post($url, [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemPrompt . "\n\nPregunta del usuario: " . $mensaje]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 700,
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $textoGenerado = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($textoGenerado) {
                $tourIds = [];
                if (preg_match('/\[TOURS_SUGERIDOS:\s*([0-9,\s]+)\]/i', $textoGenerado, $coincidencias)) {
                    $tourIds = array_map('intval', explode(',', $coincidencias[1]));
                    $textoGenerado = trim(str_replace($coincidencias[0], '', $textoGenerado));
                }

                $toursRecomendados = $contexto['tours']->whereIn('id_producto', $tourIds)->values()->all();

                if (empty($toursRecomendados)) {
                    $toursRecomendados = $this->extraerToursPorNombre($textoGenerado, $contexto['tours']);
                }

                return [
                    'exito' => true,
                    'respuesta' => $textoGenerado,
                    'tours' => $toursRecomendados,
                    'destinos' => $this->extraerDestinosMencionados($textoGenerado, $contexto['destinos']),
                    'motor' => 'Google Gemini 1.5 Flash (Online)',
                ];
            }
        }

        return null;
    }

    /**
     * Motor heurístico y semántico local (100% offline).
     */
    public function generarRespuestaHeuristica(string $mensaje, array $contexto): array
    {
        $q = mb_strtolower($mensaje, 'UTF-8');
        $tours = $contexto['tours'];
        $destinos = $contexto['destinos'];

        $toursElegidos = collect();
        $destinosElegidos = collect();
        $parrafos = [];

        // 1. Detección: Altura, termas, géiseres, Candarave
        if (str_contains($q, 'geiser') || str_contains($q, 'géiser') || str_contains($q, 'terma') || str_contains($q, 'candarave') || str_contains($q, 'altura') || str_contains($q, 'frio') || str_contains($q, 'montaña')) {
            $dest = $destinos->first(fn($d) => str_contains(mb_strtolower($d->nombre), 'geiser'));
            if ($dest) {
                $destinosElegidos->push($dest);
                $parrafos[] = "🌋 **Valle de los Géiseres y Candarave (4,300 msnm):** Es nuestra ruta estrella de aventura y naturaleza. Podrás disfrutar de fuentes termales naturales y paisajes volcánicos espectaculares.";
                $parrafos[] = "⚠️ **Recomendación médica y de seguridad:** Por ubicarse sobre los 4,000 metros de altitud, te sugerimos un día previo de aclimatación en Tacna ciudad, ropa térmica cortaviento y abundante hidratación.";
            }
            $tourMatch = $tours->first(fn($t) => str_contains(mb_strtolower($t->nombre), 'geiser') || str_contains(mb_strtolower($t->nombre), 'candarave'));
            if ($tourMatch) $toursElegidos->push($tourMatch);
        }

        // 2. Detección: Familias, niños, adultos mayores, arqueología
        if (str_contains($q, 'familia') || str_contains($q, 'niño') || str_contains($q, 'abuelo') || str_contains($q, 'anciano') || str_contains($q, 'tranquilo') || str_contains($q, 'miculla') || str_contains($q, 'pachia') || str_contains($q, 'arqueolog')) {
            $dest = $destinos->first(fn($d) => str_contains(mb_strtolower($d->nombre), 'miculla'));
            if ($dest) {
                $destinosElegidos->push($dest);
                $parrafos[] = "☀️ **Complejo Arqueológico de Miculla y Valle Viejo de Pachía:** Es la opción ideal para familias con niños y adultos mayores. Posee un clima cálido, baja altitud (~1,200 msnm), puente colgante y más de 1,500 petroglifos preincaicos de fácil caminata.";
            }
            $tourMatch = $tours->first(fn($t) => str_contains(mb_strtolower($t->nombre), 'miculla') || str_contains(mb_strtolower($t->nombre), 'valle viejo'));
            if ($tourMatch) $toursElegidos->push($tourMatch);
        }

        // 3. Detección: Gastronomía, vino, pisco, Pocollay
        if (str_contains($q, 'pisco') || str_contains($q, 'vino') || str_contains($q, 'comida') || str_contains($q, 'pocollay') || str_contains($q, 'bodega') || str_contains($q, 'almorzar') || str_contains($q, 'gastronom')) {
            $dest = $destinos->first(fn($d) => str_contains(mb_strtolower($d->nombre), 'pisco') || str_contains(mb_strtolower($d->nombre), 'pocollay'));
            if ($dest) {
                $destinosElegidos->push($dest);
                $parrafos[] = "🍇 **Ruta del Pisco y Bodegas Tradicionales de Pocollay:** Una experiencia relajante para degustar piscos premiados, macerados de damasco y la auténtica gastronomía tacneña (picante a la tacneña y cordero candaraveño).";
            }
            $tourMatch = $tours->first(fn($t) => str_contains(mb_strtolower($t->nombre), 'pisco') || str_contains(mb_strtolower($t->nombre), 'campiña'));
            if ($tourMatch) $toursElegidos->push($tourMatch);
        }

        // 4. Detección: Medio día, corto, rápido, historia
        if (str_contains($q, 'medio dia') || str_contains($q, 'medio día') || str_contains($q, 'corto') || str_contains($q, 'rapido') || str_contains($q, 'rápido') || str_contains($q, 'alianza') || str_contains($q, 'historia') || str_contains($q, 'guerra')) {
            $dest = $destinos->first(fn($d) => str_contains(mb_strtolower($d->nombre), 'alianza'));
            if ($dest) {
                $destinosElegidos->push($dest);
                $parrafos[] = "🏛️ **Monumento a los Héroes en el Alto de la Alianza:** Tour histórico esencial de 3 a 4 horas. Conocerás el museo de sitio y el campo de batalla de 1880 con vistas panorámicas de la ciudad.";
            }
            $tourMatch = $tours->first(fn($t) => str_contains(mb_strtolower($t->nombre), 'alianza') || str_contains(mb_strtolower($t->nombre), 'historico') || str_contains(mb_strtolower($t->nombre), 'city'));
            if ($tourMatch) $toursElegidos->push($tourMatch);
        }

        // 5. Detección: Tarata, andenes, naturaleza, caminatas
        if (str_contains($q, 'tarata') || str_contains($q, 'anden') || str_contains($q, 'paisaje') || str_contains($q, 'trekking') || str_contains($q, 'caminar')) {
            $dest = $destinos->first(fn($d) => str_contains(mb_strtolower($d->nombre), 'tarata'));
            if ($dest) {
                $destinosElegidos->push($dest);
                $parrafos[] = "🌾 **Mirador y Andenería Preincaica de Tarata (3,070 msnm):** Un circuito que combina paisajes andinos deslumbrantes, iglesias coloniales y cultura viva andina.";
            }
            $tourMatch = $tours->first(fn($t) => str_contains(mb_strtolower($t->nombre), 'tarata'));
            if ($tourMatch) $toursElegidos->push($tourMatch);
        }

        // 6. Si no hubo coincidencia específica, armar recomendación general personalizada
        if ($toursElegidos->isEmpty()) {
            $toursElegidos = $tours->take(2);
            $destinosElegidos = $destinos->take(2);
            $parrafos[] = "¡Hola! En **18 Experience** contamos con los mejores circuitos turísticos para descubrir la belleza y cultura de Tacna.";
            $parrafos[] = "Para una primera visita, te recomiendo especialmente combinar nuestra ruta arqueológica y familiar de **Miculla y Pachía** con el majestuoso circuito andino de los **Géiseres de Candarave**.";
        }

        $parrafos[] = "💡 **Detalles del servicio:** Todas nuestras salidas incluyen transporte turístico autorizado, guiado oficial y seguro para pasajeros.";

        if (!empty($contexto['salidas']) && count($contexto['salidas']) > 0) {
            $proxima = $contexto['salidas'][0];
            $parrafos[] = "📅 **Próxima salida disponible:** Programada para el {$proxima->fecha} a las {$proxima->hora} con {$proxima->cupos_disponibles} cupos libres.";
        }

        $parrafos[] = "¿Te gustaría que preparemos una cotización formal para las fechas de tu viaje?";

        return [
            'exito' => true,
            'respuesta' => implode("\n\n", $parrafos),
            'tours' => $toursElegidos->values()->all(),
            'destinos' => $destinosElegidos->pluck('nombre')->all(),
            'motor' => 'Motor Heurístico 18 Experience (Offline)',
        ];
    }

    protected function extraerToursPorNombre(string $texto, $tours): array
    {
        $coincidentes = [];
        foreach ($tours as $t) {
            if (stripos($texto, $t->nombre) !== false || stripos($texto, $t->codigo) !== false) {
                $coincidentes[] = $t;
            }
        }
        return $coincidentes;
    }

    protected function extraerDestinosMencionados(string $texto, $destinos): array
    {
        $mencionados = [];
        foreach ($destinos as $d) {
            if (stripos($texto, $d->nombre) !== false) {
                $mencionados[] = $d->nombre;
            }
        }
        return $mencionados;
    }
}
