<?php

use App\Domain\Finance\Providers\ManualTransferProvider;
use App\Domain\Finance\Providers\SimulatedGatewayProvider;

return [
    // manual | simulator. Real gateways (midtrans, xendit, ...) get added here later.
    'provider' => env('PAYMENT_PROVIDER', 'manual'),

    'providers' => [
        'manual' => ManualTransferProvider::class,
        'simulator' => SimulatedGatewayProvider::class,
    ],

    // Shared secret for webhook HMAC-SHA256 signatures (X-Signature header).
    // Required outside local/testing; without it webhooks are refused (APP_KEY fallback only in local/testing).
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET') ?: (in_array(env('APP_ENV', 'production'), ['local', 'testing'], true) ? env('APP_KEY') : null),

    'down_payment_percent' => (int) env('PAYMENT_DP_PERCENT', 30),

    'intent_ttl_hours' => 24,

    // How long the payment link a CS sends to a customer stays valid.
    'link_ttl_days' => 7,
];
