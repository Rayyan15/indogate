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
    'webhook_secret' => env('PAYMENT_WEBHOOK_SECRET', env('APP_KEY')),

    'down_payment_percent' => (int) env('PAYMENT_DP_PERCENT', 30),

    'intent_ttl_hours' => 24,

    // How long the payment link a CS sends to a customer stays valid.
    'link_ttl_days' => 7,
];
