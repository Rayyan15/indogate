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
}
