<?php

return [

    'pixelforge' => [
        'url' => env('PIXELFORGE_URL', 'https://pixelforge.promptedit.dev/v1'),
        'key' => env('PIXELFORGE_API_KEY'),
        'webhook_secret' => env('PIXELFORGE_WEBHOOK_SECRET'),
        'markup_percent' => 25,
    ],

    'payments' => [
        'webhook_secret' => env('PAYMENTS_WEBHOOK_SECRET'),
        'webhook_url' => env('PAYMENTS_WEBHOOK_URL', rtrim((string) env('APP_URL'), '/').'/webhooks/payments'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => '/auth/google/callback',
    ],

    'operator' => [
        'email' => env('OPERATOR_EMAIL', 'operator@emberly.test'),
        'password' => env('OPERATOR_PASSWORD'),
    ],

];
