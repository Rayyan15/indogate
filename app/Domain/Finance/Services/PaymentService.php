<?php

namespace App\Domain\Finance\Services;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Contracts\PaymentProviderInterface;
use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Exceptions\SelfApprovalException;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Providers\ManualTransferProvider;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Domain\Pricing\Money;
use App\Enums\BookingStatus;
use App\Enums\PaymentChannel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function __construct(
        protected ?PaymentProviderInterface $provider = null
    ) {
        $this->provider = $this->provider ?? new ManualTransferProvider;
    }

    /**
     * Record a new payment attempt for a booking.
     */
    public function recordPayment(
        PackageBooking $booking,
        int $amountMinor,
        string $currency,
        string $type = Payment::TYPE_DOWN_PAYMENT,
        string $channel = 'manual_transfer',
        ?float $customFxRate = null,
        ?UploadedFile $proofFile = null,
        ?string $notes = null,
        ?User $creator = null
    ): Payment {
        if ($booking->status === BookingStatus::CANCELLED) {
            throw new InvalidArgumentException('Tidak dapat mencatat pembayaran untuk pemesanan yang dibatalkan.');
        }

        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Jumlah pembayaran harus lebih dari 0.');
        }

        $currency = strtoupper($currency);
        $fxRate = $this->resolveFxRate($currency, $customFxRate);
        $decimalPlaces = \App\Domain\Pricing\Models\Currency::where('code', $currency)->value('decimal_places') ?? ($currency === 'IDR' ? 0 : 2);
        $factor = 10 ** $decimalPlaces;
        $idrEquivalentMinor = $currency === 'IDR'
            ? $amountMinor
            : (int) round(($amountMinor * $fxRate) / $factor);

        // Calculate channel fee if applicable
        $channelFeeMinor = $this->calculateChannelFee($channel, $amountMinor);

        // Store proof file in private disk
        $proofPath = null;
        if ($proofFile) {
            $proofPath = $proofFile->store('payment-proofs', 'local');
        }

        return DB::transaction(function () use (
            $booking,
            $amountMinor,
            $currency,
            $type,
            $channel,
            $fxRate,
            $idrEquivalentMinor,
            $channelFeeMinor,
            $proofPath,
            $notes,
            $creator
        ) {
            $payment = Payment::create([
                'branch_id' => $booking->branch_id,
                'booking_id' => $booking->id,
                'type' => $type,
                'amount_minor' => $amountMinor,
                'currency' => $currency,
                'fx_rate' => $fxRate,
                'idr_equivalent_minor' => $idrEquivalentMinor,
                'channel_fee_minor' => $channelFeeMinor,
                'proof_file' => $proofPath,
                'channel' => $channel,
                'notes' => $notes,
                'status' => Payment::STATUS_PENDING,
                'created_by' => $creator?->id,
            ]);

            activity('finance')
                ->causedBy($creator)
                ->performedOn($payment)
                ->withProperties([
                    'booking_code' => $booking->code,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'type' => $type,
                ])
                ->log('Payment recorded and awaiting verification');

            return $payment;
        });
    }

    /**
     * Verify a payment by a Finance Admin with self-approval protection.
     */
    public function verifyPayment(Payment $payment, User $verifier): bool
    {
        // 1. Enforce RBAC permission
        if (! $verifier->can('payment.verify')) {
            throw new PaymentVerificationException('Pengguna tidak memiliki izin untuk memverifikasi pembayaran.');
        }

        // 2. Enforce Self-Approval Restriction (PRD M9: creator cannot verify own booking payment)
        $booking = $payment->booking;
        $creatorId = $booking->created_by
            ?? $booking->statusHistories()->where('from_status', BookingStatus::DRAFT)->first()?->user_id;

        if ($creatorId !== null && (int) $verifier->id === (int) $creatorId) {
            throw new SelfApprovalException('Pembuat pemesanan tidak diizinkan memverifikasi pembayarannya sendiri.');
        }

        if ($payment->status === Payment::STATUS_VERIFIED) {
            return true;
        }

        return DB::transaction(function () use ($payment, $verifier, $booking) {
            // Verify via provider
            $this->provider->verifyPayment($payment, $verifier);

            activity('finance')
                ->causedBy($verifier)
                ->performedOn($payment)
                ->withProperties([
                    'booking_code' => $booking->code,
                    'amount_minor' => $payment->amount_minor,
                    'currency' => $payment->currency,
                ])
                ->log('Payment verified');

            // Reconcile with booking state machine
            $this->reconcileBookingStatus($booking, $verifier);

            return true;
        });
    }

    /**
     * Reject a payment with a mandatory reason.
     */
    public function rejectPayment(Payment $payment, string $reason, User $rejector): bool
    {
        if (! $rejector->can('payment.verify')) {
            throw new PaymentVerificationException('Pengguna tidak memiliki izin untuk menolak pembayaran.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan penolakan pembayaran wajib diisi.');
        }

        $payment->update([
            'status' => Payment::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'verified_by' => $rejector->id,
            'verified_at' => now(),
        ]);

        activity('finance')
            ->causedBy($rejector)
            ->performedOn($payment)
            ->withProperties(['reason' => $reason])
            ->log('Payment rejected');

        return true;
    }

    /**
     * Process a refund with a mandatory reason.
     */
    public function refundPayment(Payment $payment, int $amountMinor, string $reason, User $processor): Refund
    {
        if (! $processor->can('payment.verify')) {
            throw new PaymentVerificationException('Pengguna tidak memiliki izin untuk memproses refund.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan refund wajib diisi.');
        }

        if ($payment->status !== Payment::STATUS_VERIFIED) {
            throw new InvalidArgumentException('Hanya pembayaran yang telah diverifikasi yang dapat di-refund.');
        }

        return DB::transaction(function () use ($payment, $amountMinor, $reason, $processor) {
            $refund = $this->provider->processRefund($payment, $amountMinor, $reason, $processor);

            activity('finance')
                ->causedBy($processor)
                ->performedOn($refund)
                ->withProperties([
                    'payment_id' => $payment->id,
                    'booking_code' => $payment->booking?->code,
                    'amount_minor' => $amountMinor,
                    'currency' => $refund->currency,
                    'reason' => $reason,
                ])
                ->log('Payment refund processed');

            return $refund;
        });
    }

    /**
     * Transition booking status based on verified payment total.
     */
    private function reconcileBookingStatus(PackageBooking $booking, User $actor): void
    {
        $stateMachine = new BookingStateMachine;
        $totalPaid = $booking->totalPaidMinor();
        $totalRequired = (int) $booking->total_minor;

        if ($booking->status === BookingStatus::CONFIRMED && $totalPaid > 0) {
            $stateMachine->transition(
                $booking,
                BookingStatus::PARTIALLY_PAID,
                'Pembayaran DP diverifikasi',
                $actor
            );
        }

        if ($booking->status === BookingStatus::PARTIALLY_PAID && $totalPaid >= $totalRequired) {
            $stateMachine->transition(
                $booking,
                BookingStatus::PAID,
                'Pelunasan diverifikasi',
                $actor
            );
        }
    }

    public function resolveFxRate(string $currency, ?float $customRate = null): float
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

    public function calculateChannelFee(string $channel, int $amountMinor): int
    {
        $paymentChannel = PaymentChannel::tryFrom($channel);
        if (! $paymentChannel) {
            return 0;
        }

        $cost = PaymentChannelCost::where('channel', $paymentChannel)->first();
        if (! $cost) {
            return 0;
        }

        $percentageFee = (int) round(($amountMinor * $cost->percent_fee) / 10000);
        $flatFee = $cost->flat_fee_minor instanceof Money
            ? $cost->flat_fee_minor->amountMinor
            : (int) $cost->flat_fee_minor;

        return $percentageFee + $flatFee;
    }
}
