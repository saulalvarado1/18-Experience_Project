<?php

namespace App\Modules\Finanzas\Gateways;

use RuntimeException;

/**
 * Error técnico de comunicación con la pasarela (timeout, 5xx, credenciales).
 * A diferencia de un cargo rechazado, puede reintentarse.
 */
class GatewayException extends RuntimeException
{
}
