<?php

namespace App\Modules\Finanzas\Gateways;

use Illuminate\Http\Request;

/**
 * Simulador local de Pasarela de Pagos (Sandbox integrado).
 * Permite realizar pruebas y demostraciones de checkout y devoluciones
 * sin depender de conexión a servidores externos ni problemas de resolución DNS.
 */
class DemoPaymentGateway implements PaymentGatewayInterface
{
    public function createCharge(array $data): ChargeResult
    {
        $id = 'chr_test_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 14);

        return new ChargeResult(
            exitoso: true,
            id: $id,
            montoCentimos: (int) $data['monto_centimos'],
            moneda: $data['moneda'] ?? 'PEN',
            metadata: $data['metadata'] ?? [],
            mensajeUsuario: 'Pago simulado aprobado exitosamente por Culqi Sandbox.',
            raw: ['status' => 'successful', 'id' => $id, 'source' => 'demo_gateway']
        );
    }

    public function retrieveCharge(string $chargeId): ChargeResult
    {
        return new ChargeResult(
            exitoso: true,
            id: $chargeId,
            montoCentimos: 10000,
            moneda: 'PEN',
            metadata: [],
            mensajeUsuario: 'Cargo simulado recuperado.',
            raw: ['status' => 'successful', 'id' => $chargeId]
        );
    }

    public function refundCharge(string $chargeId, int $montoCentimos): RefundResult
    {
        $id = 'ref_test_' . substr(md5(uniqid((string) mt_rand(), true)), 0, 14);

        return new RefundResult(
            exitoso: true,
            id: $id,
            montoCentimos: $montoCentimos,
            mensaje: 'Devolución simulada procesada con éxito en Culqi Sandbox.',
            raw: ['status' => 'successful', 'id' => $id]
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return true;
    }

    public function extractSucceededChargeId(array $payload): ?string
    {
        return $payload['data']['id'] ?? $payload['id'] ?? null;
    }
}
