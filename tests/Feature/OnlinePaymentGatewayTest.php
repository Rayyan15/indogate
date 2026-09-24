<?php

namespace Tests\Feature;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Providers\SimulatedGatewayProvider;
use App\Domain\Finance\Services\GatewayCheckout;
use App\Enums\BookingStatus;
use App\Http\Controllers\Public\OnlinePaymentController;
use App\Livewire\Admin\Booking\PackageBookingShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class OnlinePaymentGatewayTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.provider' => 'simulator', 'payments.webhook_secret' => 'test-secret']);
        $this->seed();
    }

    private function booking(): PackageBooking
    {
        $branch = $this->baliBranch();

        return $this->bookingFor($this->quotationFor($branch, $this->leadFor($branch), $this->packageWithOneHotelRoom($branch)));
    }

    private function event(PaymentIntent $intent, string $type, string $id): string
    {
        return json_encode(['event_id' => $id, 'type' => $type, 'intent' => $intent->public_token, 'reference' => 'REF-'.$id]);
    }

    private function postWebhook(string $body, ?string $signature = null)
    {
        return $this->call('POST', '/webhooks/payments/simulator', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $signature ?? GatewayCheckout::sign($body),
        ], $body);
    }

    public function test_down_payment_then_settlement_moves_booking_to_paid_and_duplicates_are_ignored(): void
    {
        $booking = $this->booking();
        $checkout = app(GatewayCheckout::class);

        $dp = $checkout->startCheckout($booking, Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        $this->assertSame((int) ceil($booking->total_minor * 0.3), $dp->amount_minor);

        $body = $this->event($dp, GatewayCheckout::EVENT_PAID, 'evt_1');
        $this->postWebhook($body)->assertOk()->assertJson(['status' => 'processed']);
        $this->postWebhook($body)->assertOk()->assertJson(['status' => 'duplicate']);

        $booking->refresh();
        $this->assertSame(BookingStatus::PARTIALLY_PAID, $booking->status);
        $this->assertSame(1, Payment::where('booking_id', $booking->id)->count());
        $this->assertSame(Payment::SOURCE_GATEWAY, Payment::first()->source);
        $this->assertSame(Payment::STATUS_VERIFIED, Payment::first()->status);

        $rest = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'international_card', 'card');
        $this->assertSame($booking->remainingBalanceMinor(), $rest->amount_minor);
        $this->postWebhook($this->event($rest, GatewayCheckout::EVENT_PAID, 'evt_2'))->assertOk();

        $this->assertSame(BookingStatus::PAID, $booking->fresh()->status);
        $this->assertSame(0, $booking->fresh()->remainingBalanceMinor());
    }

    public function test_bad_signature_is_rejected(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');

        $this->postWebhook($this->event($intent, GatewayCheckout::EVENT_PAID, 'evt_x'), 'forged')->assertStatus(401);
        $this->assertSame(0, Payment::count());
    }

    public function test_failed_intent_does_not_touch_the_booking(): void
    {
        $booking = $this->booking();
        $intent = app(GatewayCheckout::class)->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');

        $this->postWebhook($this->event($intent, GatewayCheckout::EVENT_FAILED, 'evt_f'))->assertOk();

        $this->assertSame(PaymentIntent::STATUS_CANCELLED, $intent->fresh()->status);
        $this->assertSame(0, Payment::count());
        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_late_payment_on_expired_intent_is_still_recorded_while_balance_is_open(): void
    {
        $booking = $this->booking();
        $intent = app(GatewayCheckout::class)->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $intent->update(['status' => PaymentIntent::STATUS_EXPIRED]);

        $this->postWebhook($this->event($intent, GatewayCheckout::EVENT_PAID, 'evt_late'))->assertJson(['status' => 'processed_late']);

        $this->assertSame(PaymentIntent::STATUS_COMPLETED, $intent->fresh()->status);
        $this->assertSame(1, Payment::count());
        $this->assertSame(BookingStatus::PAID, $booking->fresh()->status);
    }

    public function test_late_payment_after_booking_is_paid_is_flagged_not_recorded(): void
    {
        $booking = $this->booking();
        $checkout = app(GatewayCheckout::class);
        $stale = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $stale->update(['status' => PaymentIntent::STATUS_EXPIRED]);
        $paid = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'international_card', 'card');
        $this->postWebhook($this->event($paid, GatewayCheckout::EVENT_PAID, 'evt_ok'))->assertOk();

        $this->postWebhook($this->event($stale, GatewayCheckout::EVENT_PAID, 'evt_stale'))->assertJson(['status' => 'needs_review']);

        $this->assertSame(1, Payment::count());
        $this->assertSame(PaymentIntent::STATUS_EXPIRED, $stale->fresh()->status);
    }

    public function test_payment_for_booking_cancelled_while_customer_was_paying_is_flagged(): void
    {
        $booking = $this->booking();
        $intent = app(GatewayCheckout::class)->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, 'Customer batal');

        $this->postWebhook($this->event($intent, GatewayCheckout::EVENT_PAID, 'evt_cxl'))->assertJson(['status' => 'needs_review']);

        $this->assertSame(0, Payment::count());
        $intent->refresh();
        $this->assertSame(PaymentIntent::STATUS_COMPLETED, $intent->status);
        $this->assertNotNull($intent->needs_review_at);
    }

    public function test_second_pending_intent_is_cancelled_and_overpayment_is_flagged(): void
    {
        $booking = $this->booking();
        $checkout = app(GatewayCheckout::class);
        $a = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $b = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'international_card', 'card');

        $this->postWebhook($this->event($a, GatewayCheckout::EVENT_PAID, 'evt_a'))->assertJson(['status' => 'processed']);
        $this->assertSame(PaymentIntent::STATUS_CANCELLED, $b->fresh()->status);

        $this->postWebhook($this->event($b, GatewayCheckout::EVENT_PAID, 'evt_b'))->assertJson(['status' => 'needs_review']);
        $this->assertSame(1, Payment::count());
    }

    public function test_intent_exceeding_remaining_balance_is_not_recorded(): void
    {
        $booking = $this->booking();
        $intent = app(GatewayCheckout::class)->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $intent->update(['amount_minor' => $booking->remainingBalanceMinor() + 1]);

        $this->postWebhook($this->event($intent, GatewayCheckout::EVENT_PAID, 'evt_over'))->assertJson(['status' => 'needs_review']);
        $this->assertSame(0, Payment::count());
        $intent->refresh();
        $this->assertSame(PaymentIntent::STATUS_COMPLETED, $intent->status);
        $this->assertNotNull($intent->needs_review_at);
        $this->assertNotNull($intent->paid_at);

        // Terminal: neither the expiry job nor isPayable() may touch it, and a redelivered paid event stays flagged.
        $intent->forceFill(['expires_at' => now()->subHour()])->save();
        $this->artisan('payments:expire-intents')->assertOk();
        $this->assertSame(PaymentIntent::STATUS_COMPLETED, $intent->fresh()->status);
        $this->assertFalse($intent->fresh()->isPayable());
    }

    public function test_malformed_payload_returns_400(): void
    {
        $this->postWebhook('{"foo":1}')->assertStatus(400);
        $this->postWebhook('not json')->assertStatus(400);
    }

    public function test_unknown_intent_is_logged_without_error(): void
    {
        $body = json_encode(['event_id' => 'evt_nx', 'type' => GatewayCheckout::EVENT_PAID, 'intent' => 'nope']);
        $this->postWebhook($body)->assertOk()->assertJson(['status' => 'unknown_intent']);
        $this->assertDatabaseHas('payment_webhook_events', ['event_id' => 'evt_nx']);
    }

    public function test_unknown_event_type_is_ignored_and_logged(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');

        $this->postWebhook($this->event($intent, 'payment.weird', 'evt_w'))->assertOk()->assertJson(['status' => 'ignored']);
        $this->assertSame(PaymentIntent::STATUS_PENDING, $intent->fresh()->status);
        $this->assertDatabaseHas('payment_webhook_events', ['event_id' => 'evt_w']);
    }

    public function test_booking_detail_hides_record_payment_when_fully_paid(): void
    {
        $booking = $this->booking();
        $intent = app(GatewayCheckout::class)->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $this->postWebhook($this->event($intent, GatewayCheckout::EVENT_PAID, 'evt_full'))->assertOk();

        Livewire::actingAs(User::role('CS Admin')->firstOrFail())
            ->test(PackageBookingShow::class, ['packageBooking' => $booking->fresh()])
            ->assertSee(__('booking.no_balance_due'))
            ->assertDontSee('+ '.__('finance.record_payment'));
    }

    public function test_payment_link_must_be_signed_and_gateway_enabled(): void
    {
        $booking = $this->booking();

        $this->get("/id/pay/booking/{$booking->id}")->assertForbidden();

        config(['payments.provider' => 'manual']);
        $this->get(OnlinePaymentController::linkFor($booking))->assertNotFound();
    }

    public function test_expire_command_expires_stale_intents(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $intent->update(['expires_at' => now()->subMinute()]);

        $this->artisan('payments:expire-intents')->assertOk();

        $this->assertSame(PaymentIntent::STATUS_EXPIRED, $intent->fresh()->status);
    }

    public function test_simulator_refuses_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        new SimulatedGatewayProvider;
    }

    public function test_simulate_is_refused_after_provider_switched_away(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        config(['payments.provider' => 'manual']);

        $this->post(route('payments.simulator.simulate', ['intent' => $intent->public_token, 'locale' => 'en']), ['outcome' => 'paid'])->assertNotFound();
        $this->assertSame(PaymentIntent::STATUS_PENDING, $intent->fresh()->status);
    }

    public function test_webhook_refused_without_secret(): void
    {
        config(['payments.webhook_secret' => null]);
        $this->call('POST', '/webhooks/payments/simulator', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{}')->assertStatus(503);
    }

    public function test_expire_command_does_not_flip_paid_intent(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        $intent->forceFill(['expires_at' => now()->subHour()])->save();
        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('payments:expire-intents'));
        $this->assertSame(PaymentIntent::STATUS_EXPIRED, $intent->fresh()->status);
    }

    public function test_expire_command_skips_pending_intent_flagged_for_review(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        $intent->forceFill(['expires_at' => now()->subHour(), 'needs_review_at' => now()])->save();

        $this->artisan('payments:expire-intents')->assertOk();

        $this->assertSame(PaymentIntent::STATUS_PENDING, $intent->fresh()->status);
        $this->assertFalse($intent->fresh()->isPayable());
    }

    public function test_unknown_intent_leaves_no_blocking_state_and_bad_signature_leaves_no_event_row(): void
    {
        $body = json_encode(['event_id' => 'evt_bad', 'type' => GatewayCheckout::EVENT_PAID, 'intent' => 'x']);
        $this->postWebhook($body, 'forged')->assertStatus(401);
        $this->assertDatabaseMissing('payment_webhook_events', ['event_id' => 'evt_bad']);
        $this->postWebhook($body)->assertOk()->assertJson(['status' => 'unknown_intent']);
    }

    public function test_simulate_without_secret_returns_503(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        config(['payments.webhook_secret' => null]);

        $this->post(route('payments.simulator.simulate', ['intent' => $intent->public_token, 'locale' => 'en']), ['outcome' => 'paid'])->assertStatus(503);
    }

    public function test_simulator_page_is_refused_after_provider_switched_away(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        config(['payments.provider' => 'manual']);

        $this->get(route('payments.simulator.show', ['intent' => $intent->public_token, 'locale' => 'en']))->assertNotFound();
    }

    public function test_expire_race_intent_paid_between_select_and_update_stays_completed_without_notification(): void
    {
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking(), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        $intent->forceFill(['expires_at' => now()->subHour()])->save();
        $before = \Illuminate\Support\Facades\DB::table('notifications')->count();

        $flipped = false;
        \Illuminate\Support\Facades\DB::listen(function ($q) use ($intent, &$flipped) {
            if (! $flipped && str_starts_with(strtolower($q->sql), 'select') && str_contains($q->sql, 'payment_intents')) {
                $flipped = true;
                \Illuminate\Support\Facades\DB::table('payment_intents')->where('id', $intent->id)->update(['status' => PaymentIntent::STATUS_COMPLETED]);
            }
        });

        $this->artisan('payments:expire-intents')->assertOk();

        $this->assertTrue($flipped);
        $this->assertSame(PaymentIntent::STATUS_COMPLETED, $intent->fresh()->status);
        $this->assertSame($before, \Illuminate\Support\Facades\DB::table('notifications')->count());
    }
}
