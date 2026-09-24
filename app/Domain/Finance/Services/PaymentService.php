<?php

namespace App\Domain\Finance\Services;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Contracts\PaymentProviderInterface;
use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Exceptions\SelfApprovalException;
use App\Domain\Finance\Fx;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Models\Refund;
use App\Enums\BookingStatus;
use App\Enums\PaymentChannel;
use App\Models\User;
use App\Notifications\ManualPaymentPending;
use App\Notifications\OnlinePaymentReceived;
use App\Support\Notify;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * All payment state changes go through here. The provider (manual transfer
 * today, a real multi-currency gateway later) only performs the external
 * side; status rules, locking and booking reconciliation live here so a
 * new provider can't skip them.
 */
class PaymentService
{
    public const TYPES = [Payment::TYPE_DOWN_PAYMENT, Payment::TYPE_FULL_PAYMENT, Payment::TYPE_INSTALLMENT];

    public function __construct(
        protected ?PaymentProviderInterface $provider = null
    ) {
        $this->provider = $this->provider ?? app(PaymentProviderInterface::class);
    }

    public static function channels(): array
    {
        return array_column(PaymentChannel::cases(), 'value');
    }

    /**
     * Record a new payment attempt for a booking.
     *
     * A custom FX rate is only honoured from a user holding payment.verify;
     * anyone else gets the current rate (bug-review BF-06).
     */
    public function recordPayment(
        PackageBooking $booking,
        int $amountMinor,
        string $currency,
        string $type = Payment::TYPE_DOWN_PAYMENT,
        string $channel = 'bank_transfer',
        ?float $customFxRate = null,
        ?UploadedFile $proofFile = null,
        ?string $notes = null,
        ?User $creator = null
    ): Payment {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Jumlah pembayaran harus lebih dari 0.');
        }

        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('Tipe pembayaran tidak valid.');
        }

        if (! in_array($channel, self::channels(), true)) {
            throw new InvalidArgumentException('Channel pembayaran tidak valid.');
        }

        $currency = strtoupper($currency);
        $fxRate = ($customFxRate > 0 && $currency !== 'IDR' && $creator?->can('payment.verify'))
            ? (float) $customFxRate
            : Fx::rate($currency);
        $idrEquivalentMinor = Fx::toIdrMinor($amountMinor, $currency, $fxRate);
        $channelFeeMinor = Fx::channelFeeMinor($channel, $amountMinor, $currency, $fxRate);

        return DB::transaction(function () use (
            $booking, $amountMinor, $currency, $type, $channel, $fxRate,
            $idrEquivalentMinor, $channelFeeMinor, $proofFile, $notes, $creator
        ) {
            $booking = PackageBooking::lockForUpdate()->findOrFail($booking->id);

            if ($booking->status === BookingStatus::CANCELLED) {
                throw new InvalidArgumentException('Tidak dapat mencatat pembayaran untuk pemesanan yang dibatalkan.');
            }

            // ponytail: blocks only payments on an already fully-paid booking.
            // Partial overpayment is allowed (FX rounding, customer tops up);
            // add a tolerance rule here if finance wants a hard cap.
            if ($booking->isFullyPaid()) {
                throw new InvalidArgumentException('Pemesanan sudah lunas; pembayaran tambahan tidak dapat dicatat.');
            }

            // Soft warning for Finance (BF-17): amount beyond remaining balance is flagged in the audit log, not blocked.
            $thisMinor = $booking->toBookingCurrencyMinor($currency, $amountMinor, $idrEquivalentMinor);
            $overpays = $type !== 'refund' && $thisMinor > $booking->remainingBalanceMinor();

            $proofPath = $proofFile?->store('payment-proofs', 'local');

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
                    'fx_rate' => $fxRate,
                    'overpayment_warning' => $overpays,
                ])
                ->log('Payment recorded and awaiting verification');

            Notify::send(new ManualPaymentPending(['code' => $booking->code], Notify::url('admin.package-bookings.show', ['packageBooking' => $booking->id]), $booking->branch_id), $payment->id, 'payment.verify');

            return $payment;
        });
    }

    /**
     * Verify a pending payment. Neither the booking creator nor the person
     * who recorded the payment may verify it (PRD M9 self-approval).
     */
    public function verifyPayment(Payment $payment, User $verifier): bool
    {
        if (! $verifier->can('payment.verify')) {
            throw new PaymentVerificationException('Pengguna tidak memiliki izin untuk memverifikasi pembayaran.');
        }

        return DB::transaction(function () use ($payment, $verifier) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $booking = PackageBooking::lockForUpdate()->findOrFail($payment->booking_id);

            $this->assertNotSelfApproval($booking, $payment, $verifier);

            if ($payment->status === Payment::STATUS_VERIFIED) {
                return true; // double click: idempotent no-op
            }

            if ($payment->status !== Payment::STATUS_PENDING) {
                throw new PaymentVerificationException('Pembayaran yang sudah ditolak tidak dapat diverifikasi.');
            }

            if ($booking->status === BookingStatus::CANCELLED) {
                throw new PaymentVerificationException('Pemesanan sudah dibatalkan; tolak pembayaran ini atau proses refund.');
            }

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

            $this->reconcileBookingStatus($booking, $verifier);

            return true;
        });
    }

    /**
     * Reject a pending payment with a mandatory reason. A verified payment
     * is never rejected afterwards — that is what a refund is for.
     */
    public function rejectPayment(Payment $payment, string $reason, User $rejector): bool
    {
        if (! $rejector->can('payment.verify')) {
            throw new PaymentVerificationException('Pengguna tidak memiliki izin untuk menolak pembayaran.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan penolakan pembayaran wajib diisi.');
        }

        return DB::transaction(function () use ($payment, $reason, $rejector) {
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_PENDING) {
                throw new PaymentVerificationException('Hanya pembayaran berstatus pending yang dapat ditolak.');
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
        });
    }

    /**
     * Process a refund with a mandatory reason, then move a fully-paid
     * booking back to partially_paid when money went out (BF-07).
     */
    public function refundPayment(Payment $payment, int $amountMinor, string $reason, User $processor): Refund
    {
        if (! $processor->can('payment.verify')) {
            throw new PaymentVerificationException('Pengguna tidak memiliki izin untuk memproses refund.');
        }

        if (trim($reason) === '') {
            throw new InvalidArgumentException('Alasan refund wajib diisi.');
        }

        return DB::transaction(function () use ($payment, $amountMinor, $reason, $processor) {
            // Row lock serialises concurrent refunds of the same payment (BF-03).
            $payment = Payment::lockForUpdate()->findOrFail($payment->id);
            $booking = PackageBooking::lockForUpdate()->findOrFail($payment->booking_id);

            if ($payment->status !== Payment::STATUS_VERIFIED) {
                throw new InvalidArgumentException('Hanya pembayaran yang telah diverifikasi yang dapat di-refund.');
            }

            $refund = $this->provider->processRefund($payment, $amountMinor, $reason, $processor);

            activity('finance')
                ->causedBy($processor)
                ->performedOn($refund)
                ->withProperties([
                    'payment_id' => $payment->id,
                    'booking_code' => $booking->code,
                    'amount_minor' => $amountMinor,
                    'currency' => $refund->currency,
                    'reason' => $reason,
                ])
                ->log('Payment refund processed');

            if ($booking->status === BookingStatus::PAID && ! $booking->fresh()->isFullyPaid()) {
                (new BookingStateMachine)->transition($booking, BookingStatus::PARTIALLY_PAID, 'Refund: '.$reason, $processor);
            }

            return $refund;
        });
    }

    /**
     * A gateway confirmed the money arrived: record it as verified by the
     * system (no human approver, so self-approval rules don't apply) and move
     * the booking along. Caller holds the intent row lock.
     */
    public function recordGatewayPayment(PaymentIntent $intent, string $providerReference): ?Payment
    {
        return DB::transaction(function () use ($intent, $providerReference) {
            $booking = PackageBooking::withoutGlobalScopes()->lockForUpdate()->findOrFail($intent->booking_id);

            // Overpayment guard: several pending intents may exist per booking.
            $booking->unsetRelation('payments')->unsetRelation('refunds');
            if ($booking->status === BookingStatus::CANCELLED || $intent->amount_minor > $booking->remainingBalanceMinor()) {
                $this->flagForReview($intent, $providerReference);

                return null;
            }

            $payment = Payment::withoutGlobalScopes()->create([
                'branch_id' => $intent->branch_id,
                'booking_id' => $booking->id,
                'payment_intent_id' => $intent->id,
                'type' => $intent->payment_type,
                'amount_minor' => $intent->amount_minor,
                'currency' => $intent->currency,
                'fx_rate' => $intent->fx_rate,
                'idr_equivalent_minor' => Fx::toIdrMinor($intent->amount_minor, $intent->currency, (float) $intent->fx_rate),
                'channel_fee_minor' => $intent->channel_fee_minor,
                'channel' => $intent->channel,
                'source' => Payment::SOURCE_GATEWAY,
                'provider_reference' => $providerReference,
                'notes' => trim($intent->provider.' '.$intent->method),
                'status' => Payment::STATUS_VERIFIED,
                'verified_at' => now(),
            ]);

            $intent->update([
                'status' => PaymentIntent::STATUS_COMPLETED,
                'provider_reference' => $providerReference,
                'paid_at' => now(),
            ]);

            PaymentIntent::withoutGlobalScopes()
                ->where('booking_id', $booking->id)
                ->where('id', '!=', $intent->id)
                ->where('status', PaymentIntent::STATUS_PENDING)
                ->lockForUpdate()
                ->update(['status' => PaymentIntent::STATUS_CANCELLED, 'failure_reason' => 'Superseded by another payment']);

            activity('finance')
                ->performedOn($payment)
                ->withProperties([
                    'booking_code' => $booking->code,
                    'amount_minor' => $payment->amount_minor,
                    'currency' => $payment->currency,
                    'provider' => $intent->provider,
                    'reference' => $providerReference,
                ])
                ->log('Gateway payment received');

            if ($booking->status !== BookingStatus::CANCELLED) {
                $this->reconcileBookingStatus($booking, null);
            }

            Notify::send(new OnlinePaymentReceived(['code' => $booking->code], Notify::url('admin.package-bookings.show', ['packageBooking' => $booking->id]), $booking->branch_id), $intent->id, 'payment.verify', [$booking->created_by]);

            return $payment;
        });
    }

    /**
     * Money arrived but cannot be applied: log it and queue it for a manual refund.
     * A still-pending intent becomes completed (money was taken) so the expiry job
     * and isPayable() can no longer touch it; an expired/cancelled one keeps its status.
     */
    public function flagForReview(PaymentIntent $intent, string $providerReference): void
    {
        activity('finance')->performedOn($intent)
            ->withProperties(['reference' => $providerReference, 'amount_minor' => $intent->amount_minor, 'intent_status' => $intent->status])
            ->log('Gateway payment needs manual refund review');

        $update = ['needs_review_at' => now()];
        if ($intent->status === PaymentIntent::STATUS_PENDING) {
            $update += ['status' => PaymentIntent::STATUS_COMPLETED, 'provider_reference' => $providerReference, 'paid_at' => now()];
        }
        $intent->update($update);
    }

    /** Finance confirmed the flagged gateway payment was handled (refunded or accepted). */
    public function resolveReview(PaymentIntent $intent, User $reviewer): void
    {
        $intent->update(['needs_review_at' => null]);

        activity('finance')->performedOn($intent)->causedBy($reviewer)->log('Gateway payment review resolved');
    }

    private function assertNotSelfApproval(PackageBooking $booking, Payment $payment, User $verifier): void
    {
        $bookingCreator = $booking->created_by
            ?? $booking->statusHistories()->where('from_status', BookingStatus::DRAFT)->first()?->user_id;

        if ($bookingCreator !== null && (int) $bookingCreator === (int) $verifier->id) {
            throw new SelfApprovalException('Pembuat pemesanan tidak diizinkan memverifikasi pembayarannya sendiri.');
        }

        if ($payment->created_by !== null && (int) $payment->created_by === (int) $verifier->id) {
            throw new SelfApprovalException('Pencatat pembayaran tidak diizinkan memverifikasi pembayaran yang ia catat sendiri.');
        }
    }

    /**
     * Transition booking status based on verified payment total.
     */
    private function reconcileBookingStatus(PackageBooking $booking, ?User $actor): void
    {
        $stateMachine = new BookingStateMachine;
        $booking->unsetRelation('payments')->unsetRelation('refunds');
        $totalPaid = $booking->totalPaidMinor();

        if ($booking->status === BookingStatus::CONFIRMED && $totalPaid > 0) {
            $stateMachine->transition($booking, BookingStatus::PARTIALLY_PAID, 'Pembayaran DP diverifikasi', $actor);
        }

        if ($booking->status === BookingStatus::PARTIALLY_PAID && $totalPaid >= (int) $booking->total_minor) {
            $stateMachine->transition($booking, BookingStatus::PAID, 'Pelunasan diverifikasi', $actor);
        }
    }

    public function resolveFxRate(string $currency, ?float $customRate = null): float
    {
        return ($customRate > 0 && strtoupper($currency) !== 'IDR') ? (float) $customRate : Fx::rate($currency);
    }

    public function calculateChannelFee(string $channel, int $amountMinor, string $currency = 'IDR'): int
    {
        return Fx::channelFeeMinor($channel, $amountMinor, $currency, Fx::rate($currency));
    }
}
