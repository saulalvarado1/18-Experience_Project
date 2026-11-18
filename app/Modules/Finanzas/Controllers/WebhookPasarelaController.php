<?php

namespace App\Modules\Finanzas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finanzas\Gateways\GatewayException;
use App\Modules\Finanzas\Gateways\PaymentGatewayInterface;
use App\Modules\Finanzas\Services\PagoOnlineService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * POST /webhooks/pasarela — notificaciones de la pasarela (sin sesión ni CSRF).
 *
 * Códigos de respuesta:
 *  - 401: credenciales del webhook inválidas.
 *  - 503: la API de la pasarela no respondió; la pasarela reintentará.
 *  - 200: evento procesado, duplicado, ignorado o inválido (reintentar no lo arreglaría).
 */
class WebhookPasarelaController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly PagoOnlineService $pagoOnlineService,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->gateway->verifyWebhook($request)) {
            Log::warning('Webhook de pasarela con credenciales inválidas', ['ip' => $request->ip()]);
            return response()->json(['status' => 'no_autorizado'], 401);
        }

        $payload = $request->json()->all();

        try {
            $pago = $this->pagoOnlineService->registrarDesdeWebhook($payload);
        } catch (GatewayException $e) {
            Log::error('Webhook de pasarela: la API no respondió', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'reintentar'], 503);
        } catch (InvalidArgumentException|ModelNotFoundException $e) {
            Log::critical('Webhook de pasarela: cargo exitoso que no se pudo registrar', [
                'evento' => $payload['id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'mensaje' => $e->getMessage()]);
        }

        if (!$pago) {
            return response()->json(['status' => 'ignorado']);
        }

        return response()->json([
            'status' => $pago->wasRecentlyCreated ? 'registrado' : 'duplicado',
            'id_pago' => $pago->id_pago,
        ]);
    }
}
