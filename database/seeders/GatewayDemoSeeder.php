<?php

namespace Database\Seeders;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Providers\SimulatedGatewayProvider;
use App\Domain\Finance\Services\GatewayCheckout;
use App\Enums\BookingStatus;
use Illuminate\Database\Seeder;

/**
 * Demo data for the simulated payment gateway, on demand
 * (`php artisan db:seed --class=GatewayDemoSeeder`, after OperationsDemoSeeder).
 * Settles intents through the real webhook pipeline so every state is genuine:
 * booking A gets pending / failed / expired attempts, booking B gets a paid
 * attempt plus a second, late payment that lands in the Finance review queue.
 */
class GatewayDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $bookings = PackageBooking::withoutGlobalScopes()
            ->where('status', BookingStatus::CONFIRMED)
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($bookings->count() < 2) {
            $this->command?->warn('Need two confirmed bookings (run OperationsDemoSeeder first).');

            return;
        }

        [$a, $b] = $bookings;
        $provider = new SimulatedGatewayProvider;
        $checkout = new GatewayCheckout;

        $open = fn (PackageBooking $booking, string $type, string $method): PaymentIntent => tap(
            $provider->createIntent($booking, $checkout->amountFor($booking, $type), $booking->currency, 'bank_transfer', ['payment_type' => $type, 'fx_rate' => $booking->lockedRate()]),
            fn (PaymentIntent $intent) => $intent->update(['method' => $method]),
        );
        $send = fn (PaymentIntent $intent, string $type, string $id) => $checkout->handleWebhook(
            'simulator',
            $body = json_encode(['event_id' => $id, 'type' => $type, 'intent' => $intent->public_token, 'reference' => 'SIM-'.$id, 'reason' => 'Declined by bank']),
            GatewayCheckout::sign($body),
        );

        $open($a, Payment::TYPE_DOWN_PAYMENT, 'va_bca');
        $send($open($a, Payment::TYPE_FULL_PAYMENT, 'card'), GatewayCheckout::EVENT_FAILED, "demo-{$a->id}-failed");
        $send($open($a, Payment::TYPE_FULL_PAYMENT, 'qris'), GatewayCheckout::EVENT_EXPIRED, "demo-{$a->id}-expired");

        $paid = $open($b, Payment::TYPE_FULL_PAYMENT, 'va_mandiri');
        $late = $open($b, Payment::TYPE_FULL_PAYMENT, 'qris');
        $send($paid, GatewayCheckout::EVENT_PAID, "demo-{$b->id}-paid");
        $send($late, GatewayCheckout::EVENT_PAID, "demo-{$b->id}-late");

        $this->command?->info("GatewayDemoSeeder done — {$a->code}: pending/failed/expired; {$b->code}: paid + needs review.");
    }
}
