<?php

namespace App\Domain\Finance\Services;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Contracts\GatewayProvider;
use App\Domain\Finance\Contracts\PaymentProviderInterface;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Enums\BookingStatus;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Online payment flow shared by every hosted-checkout gateway:
 * startCheckout() opens an intent, handleWebhook() settles it.
 * A real gateway only needs its own provider + payload mapping.
 */
class GatewayCheckout
{
    public const EVENT_PAID = 'payment.paid';

    public const EVENT_FAILED = 'payment.failed';

    public const EVENT_EXPIRED = 'payment.expired';

    public function provider(): ?GatewayProvider
    {
        $provider = app(PaymentProviderInterface::class);

        return $provider instanceof GatewayProvider ? $provider : null;
    }

    public function isEnabled(): bool
    {
        return $this->provider() !== null;
    }

    public function isPayable(PackageBooking $booking): bool
    {
        return in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID], true)
            && $booking->remainingBalanceMinor() > 0;
    }

    /** DP is offered only before any money came in. */
    public function amountFor(PackageBooking $booking, string $type): int
    {
        $remaining = $booking->remainingBalanceMinor();

        if ($type === Payment::TYPE_DOWN_PAYMENT) {
            $dp = (int) ceil($booking->total_minor * config('payments.down_payment_percent') / 100);

            return min($remaining, $dp);
        }

        return $remaining;
    }

    public function startCheckout(PackageBooking $booking, string $type, string $channel, string $method): PaymentIntent
    {
        $provider = $this->provider() ?? throw new InvalidArgumentException('Pembayaran online belum aktif.');

        if (! in_array($type, [Payment::TYPE_DOWN_PAYMENT, Payment::TYPE_FULL_PAYMENT], true)) {
            throw new InvalidArgumentException('Tipe pembayaran tidak valid.');
        }

        if ($type === Payment::TYPE_DOWN_PAYMENT && $booking->status !== BookingStatus::CONFIRMED) {
            throw new InvalidArgumentException('DP sudah dibayar; lanjutkan dengan pelunasan.');
        }

        if (! $this->isPayable($booking)) {
            throw new InvalidArgumentException('Pemesanan ini tidak memiliki tagihan yang dapat dibayar.');
        }

        $intent = $provider->createIntent($booking, $this->amountFor($booking, $type), $booking->currency, $channel, [
            'payment_type' => $type,
            'fx_rate' => $booking->lockedRate(),
        ]);
        $intent->update(['method' => $method]);

        return $intent;
    }

    public static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, (string) config('payments.webhook_secret'));
    }

    /**
     * @return string outcome: processed | duplicate | ignored
     *
     * @throws InvalidArgumentException on a bad signature or payload
     */
    public function handleWebhook(string $provider, string $body, ?string $signature): string
    {
        if (! $signature || ! hash_equals(self::sign($body), $signature)) {
            throw new InvalidArgumentException('Invalid webhook signature.');
        }

        $event = json_decode($body, true);

        if (! is_array($event) || empty($event['event_id']) || empty($event['type']) || empty($event['intent'])) {
            throw new InvalidArgumentException('Malformed webhook payload.');
        }

        return DB::transaction(function () use ($provider, $event) {
            try {
                $log = DB::table('payment_webhook_events')->insertGetId([
                    'provider' => $provider,
                    'event_id' => (string) $event['event_id'],
                    'payload' => json_encode($event),
                    'signature_valid' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                return 'duplicate';
            }

            $intent = PaymentIntent::withoutGlobalScopes()
                ->where('provider', $provider)
                ->where('public_token', $event['intent'])
                ->lockForUpdate()
                ->firstOrFail();

            $outcome = 'ignored';
            $pending = $intent->status === PaymentIntent::STATUS_PENDING;

            if ($event['type'] === self::EVENT_PAID && $intent->status !== PaymentIntent::STATUS_COMPLETED) {
                // Real money arrived. Take it while the booking still has a balance
                // (even if we had given up on the intent); if the booking meanwhile got
                // paid or cancelled, record nothing and flag it for a manual refund.
                $reference = (string) ($event['reference'] ?? $event['event_id']);

                if ($this->isPayable(PackageBooking::withoutGlobalScopes()->findOrFail($intent->booking_id))) {
                    if (! $pending) {
                        $intent->update(['notes' => trim($intent->notes.' late')]);
                    }
                    app(PaymentService::class)->recordGatewayPayment($intent, $reference);
                    $outcome = $pending ? 'processed' : 'processed_late';
                } else {
                    activity('finance')->performedOn($intent)
                        ->withProperties(['reference' => $reference, 'intent_status' => $intent->status])
                        ->log('Gateway payment needs manual refund review');
                    $outcome = 'needs_review';
                }
            } elseif ($pending) {
                match ($event['type']) {
                    self::EVENT_FAILED => $intent->update(['status' => PaymentIntent::STATUS_CANCELLED, 'failure_reason' => $event['reason'] ?? null]),
                    self::EVENT_EXPIRED => $intent->update(['status' => PaymentIntent::STATUS_EXPIRED]),
                    default => throw new InvalidArgumentException('Unknown event type.'),
                };
                $outcome = 'processed';
            }

            DB::table('payment_webhook_events')->where('id', $log)->update(['processed_at' => now()]);

            return $outcome;
        });
    }
}
