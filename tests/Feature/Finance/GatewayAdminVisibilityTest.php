<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Services\GatewayCheckout;
use App\Livewire\Admin\Finance\PaymentList;
use App\Models\User;
use Database\Seeders\GatewayDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class GatewayAdminVisibilityTest extends TestCase
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

    /** Pay the booking twice online so the second payment lands in the review queue. */
    private function flaggedIntent(): PaymentIntent
    {
        $booking = $this->booking();
        $checkout = app(GatewayCheckout::class);
        $first = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'qris');
        $second = $checkout->startCheckout($booking, Payment::TYPE_FULL_PAYMENT, 'bank_transfer', 'card');

        foreach ([$first, $second] as $i => $intent) {
            $checkout->handleWebhook('simulator', $body = json_encode(['event_id' => "e$i", 'type' => GatewayCheckout::EVENT_PAID, 'intent' => $intent->public_token]), GatewayCheckout::sign($body));
        }

        return $second->fresh();
    }

    public function test_overpaid_gateway_payment_is_flagged_and_shown_to_finance(): void
    {
        $intent = $this->flaggedIntent();

        $this->assertNotNull($intent->needs_review_at);
        Livewire::actingAs(User::role('Finance Admin')->firstOrFail())
            ->test(PaymentList::class)
            ->assertSee(__('payment.admin.review_title'))
            ->assertSee($intent->booking->code);
    }

    public function test_finance_can_resolve_a_flagged_intent(): void
    {
        $intent = $this->flaggedIntent();

        Livewire::actingAs(User::role('Finance Admin')->firstOrFail())
            ->test(PaymentList::class)
            ->call('resolveReview', $intent->id)
            ->assertSee(__('payment.admin.review_resolved'))
            ->assertDontSee(__('payment.admin.review_title'));

        $this->assertNull($intent->fresh()->needs_review_at);
    }

    public function test_intent_of_another_branch_cannot_be_resolved(): void
    {
        $intent = $this->flaggedIntent();
        $jakarta = \App\Models\Branch::where('code', 'JKT')->first() ?? \App\Models\Branch::create(['name' => 'Jakarta', 'code' => 'JKT']);
        \Illuminate\Support\Facades\DB::table('payment_intents')->where('id', $intent->id)->update(['branch_id' => $jakarta->id]);

        $finance = User::role('Finance Admin')->firstOrFail();
        session(['active_branch_id' => $this->baliBranch()->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        try {
            Livewire::actingAs($finance)->test(PaymentList::class)->call('resolveReview', $intent->id);
        } finally {
            $this->assertNotNull(PaymentIntent::withoutGlobalScopes()->find($intent->id)->needs_review_at);
        }
    }

    public function test_user_without_verify_permission_cannot_resolve_review(): void
    {
        $intent = $this->flaggedIntent();
        $user = User::role('CS Admin')->firstOrFail();
        $user->givePermissionTo('booking.manage');

        Livewire::actingAs($user)->test(PaymentList::class)->call('resolveReview', $intent->id)->assertForbidden();

        $this->assertNotNull($intent->fresh()->needs_review_at);
    }

    public function test_source_filter_separates_gateway_from_manual_payments(): void
    {
        $intent = $this->flaggedIntent();
        $booking = $intent->booking;
        Payment::withoutGlobalScopes()->create([
            'branch_id' => $booking->branch_id, 'booking_id' => $booking->id, 'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 1000, 'currency' => $booking->currency, 'fx_rate' => 1, 'idr_equivalent_minor' => 1000,
            'channel' => 'manualchan', 'source' => Payment::SOURCE_MANUAL, 'status' => Payment::STATUS_PENDING,
        ]);

        Livewire::actingAs(User::role('Finance Admin')->firstOrFail())
            ->test(PaymentList::class)
            ->set('sourceFilter', 'manual')
            ->assertSee('MANUALCHAN')
            ->set('sourceFilter', 'gateway')
            ->assertDontSee('MANUALCHAN');
    }

    public function test_demo_seeder_creates_every_gateway_scenario(): void
    {
        $this->booking();
        $this->booking();
        $this->seed(GatewayDemoSeeder::class);

        $statuses = PaymentIntent::withoutGlobalScopes()->where('provider', 'simulator')->pluck('status')->all();
        foreach ([PaymentIntent::STATUS_PENDING, PaymentIntent::STATUS_COMPLETED, PaymentIntent::STATUS_CANCELLED, PaymentIntent::STATUS_EXPIRED] as $status) {
            $this->assertContains($status, $statuses);
        }
        $this->assertSame(1, PaymentIntent::withoutGlobalScopes()->whereNotNull('needs_review_at')->count());
        $this->assertSame(1, Payment::withoutGlobalScopes()->where('source', Payment::SOURCE_GATEWAY)->count());
    }
}
