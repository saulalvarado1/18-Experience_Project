<?php

namespace App\Modules\Finanzas\Gateways;

/**
 * Resultado normalizado de un cargo, independiente de la pasarela.
 */
final class ChargeResult
{
    public function __construct(
        public readonly bool $exitoso,
        public readonly ?string $id,
        public readonly int $montoCentimos,
        public readonly string $moneda,
        public readonly array $metadata,
        public readonly string $mensajeUsuario,
        public readonly array $raw = [],
    ) {
    }
}
