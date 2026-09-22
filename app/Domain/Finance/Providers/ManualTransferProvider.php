<?php

namespace App\Domain\Finance\Providers;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Contracts\PaymentProviderInterface;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Models\Refund;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Domain\Pricing\Money;
use App\Enums\PaymentChannel;
use App\Models\User;
use InvalidArgumentException;

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
        string $channel = 'manual_transfer',
        array $options = []
    ): PaymentIntent {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Jumlah pembayaran harus lebih dari 0.');
        }

        // Determine fx_rate
        $fxRate = $this->resolveFxRate($currency, $options['fx_rate'] ?? null);

        // Calculate channel fee if applicable
        $channelFeeMinor = $this->calculateChannelFee($channel, $amountMinor, $currency);

        return PaymentIntent::create([
            'branch_id' => $booking->branch_id,
            'booking_id' => $booking->id,
            'channel' => $channel,
            'amount_minor' => $amountMinor,
            'currency' => strtoupper($currency),
            'fx_rate' => $fxRate,
            'channel_fee_minor' => $channelFeeMinor,
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

        $curr = strtoupper($payment->currency);
        $decimalPlaces = \App\Domain\Pricing\Models\Currency::where('code', $curr)->value('decimal_places') ?? ($curr === 'IDR' ? 0 : 2);
        $factor = 10 ** $decimalPlaces;
        $idrEquivalentMinor = $curr === 'IDR'
            ? $amountMinor
            : (int) round(($amountMinor * (float) $payment->fx_rate) / $factor);

        return Refund::create([
            'branch_id' => $payment->branch_id,
            'booking_id' => $payment->booking_id,
            'payment_id' => $payment->id,
            'amount_minor' => $amountMinor,
            'currency' => $payment->currency,
            'fx_rate' => $payment->fx_rate,
            'idr_equivalent_minor' => $idrEquivalentMinor,
            'reason' => $reason,
            'processed_by' => $processor->id,
            'status' => Refund::STATUS_COMPLETED,
            'refunded_at' => now(),
        ]);
    }

    private function resolveFxRate(string $currency, ?float $customRate = null): float
    {
        $currency = strtoupper($currency);
        if ($currency === 'IDR') {
            return 1.00000000;
        }

        if ($customRate !== null && $customRate > 0) {
            return (float) $customRate;
        }

        $rateRecord = ExchangeRate::currentFor($currency);
        if ($rateRecord) {
            return (float) $rateRecord->rate;
        }

        return 1.00000000;
    }

    private function calculateChannelFee(string $channel, int $amountMinor, string $currency): int
    {
        $paymentChannel = PaymentChannel::tryFrom($channel);
        if (! $paymentChannel) {
            return 0;
        }

        $cost = PaymentChannelCost::where('channel', $paymentChannel)->first();
        if (! $cost) {
            return 0;
        }

        // percent_fee is basis points (e.g. 550 for 5.5%, or percentage * 100)
        $percentageFee = (int) round(($amountMinor * $cost->percent_fee) / 10000);
        $flatFee = $cost->flat_fee_minor instanceof Money
            ? $cost->flat_fee_minor->amountMinor
            : (int) $cost->flat_fee_minor;

        return $percentageFee + $flatFee;
    }
}
