<?php

namespace App\Modules\Finanzas\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Adaptador de Culqi (API v2).
 *
 * - Cargos:   POST {api}/charges  con source_id = token (tkn_...) generado por Culqi Checkout.
 * - Consulta: GET  {api}/charges/{id}
 * - Webhook:  evento "charge.creation.succeeded"; el cargo se vuelve a consultar en la API,
 *             nunca se confía en el contenido del evento.
 */
class CulqiPaymentGateway implements PaymentGatewayInterface
{
    private const EVENTO_CARGO_EXITOSO = 'charge.creation.succeeded';
    private const OUTCOME_EXITOSO = 'venta_exitosa';

    public function __construct(private readonly array $config)
    {
    }

    public function createCharge(array $data): ChargeResult
    {
        $payload = [
            'amount' => (int) $data['monto_centimos'],
            'currency_code' => $data['moneda'],
            'email' => $data['email'],
            'source_id' => $data['token'],
            // Culqi exige entre 5 y 80 caracteres.
            'description' => Str::limit(str_pad($data['descripcion'], 5, '.'), 77, '...'),
            // Culqi solo acepta valores string en metadata.
            'metadata' => array_map('strval', $data['metadata'] ?? []),
        ];

        $response = $this->send(fn (PendingRequest $http) => $http->post($this->url('charges'), $payload));

        return $this->toResult($response);
    }

    public function retrieveCharge(string $chargeId): ChargeResult
    {
        if (!preg_match('/^chr_[A-Za-z0-9_]+$/', $chargeId)) {
            return new ChargeResult(false, $chargeId, 0, '', [], 'Identificador de cargo inválido.');
        }

        $response = $this->send(fn (PendingRequest $http) => $http->get($this->url('charges/' . $chargeId)));

        return $this->toResult($response);
    }

    public function refundCharge(string $chargeId, int $montoCentimos): RefundResult
    {
        // POST {api}/refunds — reason admitido por Culqi: solicitud_comprador | duplicidad | fraudulento.
        $response = $this->send(fn (PendingRequest $http) => $http->post($this->url('refunds'), [
            'amount' => $montoCentimos,
            'charge_id' => $chargeId,
            'reason' => 'solicitud_comprador',
        ]));

        $body = $response->json() ?? [];
        $exitoso = $response->successful()
            && ($body['object'] ?? null) === 'refund'
            && !empty($body['id']);

        return new RefundResult(
            exitoso: $exitoso,
            id: $body['id'] ?? null,
            montoCentimos: (int) ($body['amount'] ?? 0),
            mensaje: (string) ($body['merchant_message'] ?? $body['user_message']
                ?? ($exitoso ? 'Devolución registrada en la pasarela.' : 'La pasarela rechazó la devolución.')),
            raw: $body,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $user = (string) ($this->config['webhook_user'] ?? '');
        $password = (string) ($this->config['webhook_password'] ?? '');

        if ($user === '' && $password === '') {
            // Sin credenciales configuradas solo se acepta fuera de producción.
            // Aun así el cargo se re-consulta en la API, por lo que un evento falso no registra pagos.
            return !app()->isProduction();
        }

        return hash_equals($user, (string) $request->getUser())
            && hash_equals($password, (string) $request->getPassword());
    }

    public function extractSucceededChargeId(array $payload): ?string
    {
        if (($payload['type'] ?? null) !== self::EVENTO_CARGO_EXITOSO) {
            return null;
        }

        // Culqi envía "data" como string JSON; se acepta también como objeto.
        $data = $payload['data'] ?? null;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $id = is_array($data) ? ($data['id'] ?? null) : null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    private function send(callable $call): Response
    {
        $secret = (string) ($this->config['secret_key'] ?? '');
        if ($secret === '') {
            throw new GatewayException('La pasarela Culqi no está configurada (CULQI_SECRET_KEY).');
        }

        $http = Http::withToken($secret)
            ->acceptJson()
            ->asJson()
            ->timeout((int) ($this->config['timeout'] ?? 30));

        try {
            $response = $call($http);
        } catch (ConnectionException $e) {
            throw new GatewayException('No se pudo conectar con Culqi: ' . $e->getMessage(), 0, $e);
        }

        // 401/403 (credenciales) y 5xx son errores técnicos; 4xx restantes son rechazos de negocio.
        if ($response->serverError() || in_array($response->status(), [401, 403], true)) {
            throw new GatewayException("Culqi respondió HTTP {$response->status()}.");
        }

        return $response;
    }

    private function toResult(Response $response): ChargeResult
    {
        $body = $response->json() ?? [];

        $exitoso = $response->successful()
            && ($body['object'] ?? null) === 'charge'
            && ($body['outcome']['type'] ?? null) === self::OUTCOME_EXITOSO;

        $mensaje = $body['outcome']['user_message']
            ?? $body['user_message']
            ?? ($exitoso ? 'Pago procesado correctamente.' : 'La pasarela rechazó el pago.');

        return new ChargeResult(
            exitoso: $exitoso,
            id: $body['id'] ?? null,
            montoCentimos: (int) ($body['amount'] ?? 0),
            moneda: (string) ($body['currency_code'] ?? ''),
            metadata: is_array($body['metadata'] ?? null) ? $body['metadata'] : [],
            mensajeUsuario: (string) $mensaje,
            raw: $body,
        );
    }

    private function url(string $path): string
    {
        return rtrim((string) $this->config['api_url'], '/') . '/' . $path;
    }
}
