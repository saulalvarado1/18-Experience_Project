<?php

namespace App\Modules\Finanzas\Gateways;

use Illuminate\Http\Request;

/**
 * Contrato común de las pasarelas de pago online (patrón Adapter).
 *
 * Los montos viajan siempre en céntimos (int) para evitar errores de redondeo.
 * Ninguna implementación recibe ni almacena datos de tarjeta: solo tokens
 * generados en el navegador por el checkout de la pasarela (PCI-DSS SAQ-A).
 */
interface PaymentGatewayInterface
{
    /**
     * Cobra un token generado en el navegador.
     *
     * @param array{token:string, monto_centimos:int, moneda:string, email:string, descripcion:string, metadata:array} $data
     *
     * @throws GatewayException si la pasarela no responde (error técnico, reintentable).
     */
    public function createCharge(array $data): ChargeResult;

    /**
     * Consulta un cargo directamente en la API de la pasarela (fuente de verdad).
     *
     * @throws GatewayException si la pasarela no responde.
     */
    public function retrieveCharge(string $chargeId): ChargeResult;

    /**
     * Devuelve al cliente todo o parte de un cargo.
     *
     * @throws GatewayException si la pasarela no responde.
     */
    public function refundCharge(string $chargeId, int $montoCentimos): RefundResult;

    /**
     * Verifica que la notificación provenga de la pasarela (credenciales del webhook).
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Extrae el ID del cargo de un evento de "cargo exitoso".
     * Devuelve null si el evento no es de un cargo exitoso (se ignora).
     */
    public function extractSucceededChargeId(array $payload): ?string;
}
