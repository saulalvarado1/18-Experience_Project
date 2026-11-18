<?php

namespace App\Modules\Finanzas\Gateways;

/**
 * Resultado normalizado de una devolución, independiente de la pasarela.
 */
final class RefundResult
{
    public function __construct(
        public readonly bool $exitoso,
        public readonly ?string $id,
        public readonly int $montoCentimos,
        public readonly string $mensaje,
        public readonly array $raw = [],
    ) {
    }
}
