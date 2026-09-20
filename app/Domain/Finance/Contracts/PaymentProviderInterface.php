<?php

namespace App\Domain\Finance\Contracts;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Models\Refund;
use App\Models\User;

interface PaymentProviderInterface
{
    public function getName(): string;

    /**
     * Create a payment intent for a booking.
     */
    public function createIntent(
        PackageBooking $booking,
        int $amountMinor,
        string $currency,
        string $channel = 'manual_transfer',
        array $options = []
    ): PaymentIntent;

    /**
     * Verify an existing payment.
     */
    public function verifyPayment(Payment $payment, User $verifier): bool;

    /**
     * Process a refund for a payment.
     */
    public function processRefund(
        Payment $payment,
        int $amountMinor,
        string $reason,
        User $processor
    ): Refund;
}
