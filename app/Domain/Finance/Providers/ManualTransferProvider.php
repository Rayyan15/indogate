<?php

namespace App\Domain\Finance\Providers;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Contracts\PaymentProviderInterface;
use App\Domain\Finance\Fx;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Models\Refund;
use App\Models\User;
use InvalidArgumentException;

/**
 * Manual bank transfer: money moves outside the system, an admin checks the
 * proof. Status rules and locking are enforced by PaymentService, which
 * calls this inside its transaction; a future gateway provider only has to
 * replace the external side.
 */
class ManualTransferProvider implements PaymentProviderInterface
{
    public function getName(): string
    {
        return 'manual_transfer';
    }

    public function createIntent(
        PackageBooking $booking,
        int $amountMinor,
        string $currency,
        string $channel = 'bank_transfer',
        array $options = []
    ): PaymentIntent {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Jumlah pembayaran harus lebih dari 0.');
        }

        $currency = strtoupper($currency);
        $fxRate = ($options['fx_rate'] ?? 0) > 0 && $currency !== 'IDR' ? (float) $options['fx_rate'] : Fx::rate($currency);

        return PaymentIntent::create([
            'branch_id' => $booking->branch_id,
            'booking_id' => $booking->id,
            'channel' => $channel,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'fx_rate' => $fxRate,
            'channel_fee_minor' => Fx::channelFeeMinor($channel, $amountMinor, $currency, $fxRate),
            'status' => PaymentIntent::STATUS_PENDING,
            'notes' => $options['notes'] ?? null,
            'expires_at' => $options['expires_at'] ?? now()->addDays(2),
        ]);
    }

    public function verifyPayment(Payment $payment, User $verifier): bool
    {
        $payment->update([
            'status' => Payment::STATUS_VERIFIED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        return true;
    }

    public function processRefund(
        Payment $payment,
        int $amountMinor,
        string $reason,
        User $processor
    ): Refund {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan refund wajib diisi.');
        }

        $previouslyRefunded = (int) $payment->refunds()
            ->where('status', Refund::STATUS_COMPLETED)
            ->sum('amount_minor');

        $refundableBalance = $payment->amount_minor - $previouslyRefunded;

        if ($amountMinor <= 0 || $amountMinor > $refundableBalance) {
            throw new InvalidArgumentException('Nominal refund tidak valid atau melebihi sisa dana yang dapat dikembalikan.');
        }

        return Refund::create([
            'branch_id' => $payment->branch_id,
            'booking_id' => $payment->booking_id,
            'payment_id' => $payment->id,
            'amount_minor' => $amountMinor,
            'currency' => $payment->currency,
            'fx_rate' => $payment->fx_rate,
            'idr_equivalent_minor' => Fx::toIdrMinor($amountMinor, $payment->currency, (float) $payment->fx_rate),
            'reason' => $reason,
            'processed_by' => $processor->id,
            'status' => Refund::STATUS_COMPLETED,
            'refunded_at' => now(),
        ]);
    }
}
