<?php

namespace App\Domain\Finance\Providers;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Contracts\GatewayProvider;
use App\Domain\Finance\Models\PaymentIntent;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stand-in for a hosted-checkout gateway (Midtrans Snap, Xendit Invoice, ...)
 * for demos. It issues a checkout page on our own domain that fires a signed
 * webhook through the same pipeline a real gateway will use. Refunds reuse
 * the manual provider (recorded instantly). Never runs in production.
 */
class SimulatedGatewayProvider extends ManualTransferProvider implements GatewayProvider
{
    public function __construct()
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Payment simulator must not run in production.');
        }
    }

    public function getName(): string
    {
        return 'simulator';
    }

    public function createIntent(
        PackageBooking $booking,
        int $amountMinor,
        string $currency,
        string $channel = 'bank_transfer',
        array $options = []
    ): PaymentIntent {
        $intent = parent::createIntent($booking, $amountMinor, $currency, $channel, $options + [
            'expires_at' => now()->addHours(config('payments.intent_ttl_hours')),
        ]);

        $intent->update([
            'provider' => $this->getName(),
            'public_token' => Str::random(40),
            'payment_type' => $options['payment_type'] ?? 'down_payment',
        ]);

        return $intent;
    }

    public function checkoutUrl(PaymentIntent $intent): string
    {
        return route('payments.simulator.show', ['intent' => $intent->public_token]);
    }
}
