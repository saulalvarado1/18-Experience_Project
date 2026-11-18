<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Pasarela de pagos online (módulo Finanzas). Driver disponible: culqi.
    */
    'pasarela' => [
        'driver' => env('PAYMENT_GATEWAY', 'culqi'),
        'metodo_pago' => 'PASARELA_ONLINE',
    ],

    'culqi' => [
        'public_key' => env('CULQI_PUBLIC_KEY'),
        'secret_key' => env('CULQI_SECRET_KEY'),
        'api_url' => env('CULQI_API_URL', 'https://api.culqi.com/v2'),
        // Credenciales Basic Auth configuradas en CulqiPanel > Eventos (opcionales pero recomendadas).
        'webhook_user' => env('CULQI_WEBHOOK_USER'),
        'webhook_password' => env('CULQI_WEBHOOK_PASSWORD'),
        'timeout' => (int) env('CULQI_TIMEOUT', 30),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
