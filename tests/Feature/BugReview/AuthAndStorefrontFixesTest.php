<?php

namespace Tests\Feature\BugReview;

use App\Livewire\Admin\BranchSwitcher;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FlightRoute;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for docs/bug-review findings SEC-01..03, SEC-02/H-01,
 * H-02..H-04, DL-01..DL-05 (storefront payment bridge).
 */
class AuthAndStorefrontFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::where('email', 'cs.bali@indogate.com')->first();
        $user->update(['is_active' => false]);

        $this->post(route('login', ['locale' => 'id']), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_existing_session_of_deactivated_user_is_logged_out(): void
    {
        $user = User::where('email', 'cs.bali@indogate.com')->first();
        $this->actingAs($user)->get(route('admin.desk', ['locale' => 'id']))->assertOk();

        $user->update(['is_active' => false]);

        $this->get(route('admin.dashboard', ['locale' => 'id']))->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_user_with_confirmed_2fa_is_sent_to_challenge_not_logged_in(): void
    {
        $user = User::where('email', 'admin@indogate.com')->first();
        $user->forceFill(['two_factor_secret' => encrypt('SECRET'), 'two_factor_confirmed_at' => now()])->save();

        $this->post(route('login', ['locale' => 'id']), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('two-factor.login', ['locale' => 'id']));

        $this->assertGuest();
        $this->get(route('two-factor.login', ['locale' => 'id']))->assertOk()->assertSee('recovery_code', false);
    }

    public function test_registration_creates_customer_row(): void
    {
        $this->post(route('register', ['locale' => 'id']), [
            'name' => 'New Guest', 'email' => 'guest@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('customers', ['user_id' => User::where('email', 'guest@example.com')->value('id')]);
    }

    public function test_customer_cannot_see_booking_whose_customer_id_equals_their_user_id(): void
    {
        // Attacker has no customer row; the victim's customer id happens to
        // equal the attacker's user id (the old `?? Auth::id()` fallback).
        $attacker = User::factory()->create();
        $attacker->assignRole('Customer');
        $victim = User::where('email', 'customer@indogate.com')->first();
        Customer::forceCreate(['id' => $attacker->id, 'user_id' => $victim->id, 'full_name' => 'Victim']);
        $victimBooking = $this->makeBooking(['customer_id' => $attacker->id]);

        $this->actingAs($attacker)->get(route('customer.bookings.show', ['locale' => 'id', 'booking' => $victimBooking->id]))->assertForbidden();
    }

    public function test_proof_upload_is_stored_on_booking_and_admin_can_verify_once(): void
    {
        Storage::fake('local');
        Mail::fake();
        $booking = $this->makeBooking();
        $owner = $booking->customer->user;

        $this->actingAs($owner)->post(route('customer.payments.store', ['locale' => 'id', 'booking' => $booking->id]), [
            'proof' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(Booking::STATUS_PAYMENT_SUBMITTED, $booking->status);
        Storage::disk('local')->assertExists($booking->payment_proof_path);

        // Can't re-upload while in review.
        $this->actingAs($owner)->post(route('customer.payments.store', ['locale' => 'id', 'booking' => $booking->id]), [
            'proof' => UploadedFile::fake()->image('again.jpg'),
        ])->assertStatus(422);

        $finance = User::where('email', 'finance.bali@indogate.com')->first();
        $this->actingAs($finance)->get(route('admin.bookings.payment-proof', ['locale' => 'id', 'booking' => $booking->id]))->assertOk();
        $this->actingAs($finance)->post(route('admin.bookings.verify-payment', ['locale' => 'id', 'booking' => $booking->id]))->assertSessionHas('success');
        $this->actingAs($finance)->post(route('admin.bookings.verify-payment', ['locale' => 'id', 'booking' => $booking->id]))->assertSessionHas('error');

        $this->assertSame(Booking::STATUS_CONFIRMED, $booking->fresh()->status);
        Mail::assertQueued(BookingConfirmed::class, 1);
    }

    public function test_cs_admin_cannot_verify_storefront_payment(): void
    {
        $booking = $this->makeBooking(['status' => Booking::STATUS_PAYMENT_SUBMITTED]);
        $cs = User::where('email', 'cs.bali@indogate.com')->first();

        $this->actingAs($cs)->post(route('admin.bookings.verify-payment', ['locale' => 'id', 'booking' => $booking->id]))->assertForbidden();
    }

    public function test_confirmation_email_renders_with_locale(): void
    {
        $booking = $this->makeBooking();

        $html = (new BookingConfirmed($booking))->locale('en')->render();

        $this->assertStringContainsString('/en/bookings/'.$booking->id, $html);
    }

    public function test_checkout_redirects_to_existing_route_when_cart_changed(): void
    {
        $customer = User::where('email', 'customer@indogate.com')->first();

        $this->actingAs($customer)
            ->withSession(['cart' => [
                ['bookable_type' => FlightRoute::class, 'bookable_id' => $this->makeFlight()->id, 'name' => 'x', 'quantity' => 1],
                ['bookable_type' => FlightRoute::class, 'bookable_id' => 999999, 'name' => 'gone', 'quantity' => 1],
            ]])
            ->post(route('checkout.store', ['locale' => 'id']))
            ->assertRedirect(route('checkout.index', ['locale' => 'id']));
    }

    public function test_checkout_assigns_item_branch_and_reuses_customer(): void
    {
        $customer = User::where('email', 'customer@indogate.com')->first();
        $flight = $this->makeFlight();

        foreach ([1, 2] as $_) {
            $this->actingAs($customer)
                ->withSession(['cart' => [['bookable_type' => FlightRoute::class, 'bookable_id' => $flight->id, 'name' => 'x', 'quantity' => 1]]])
                ->post(route('checkout.store', ['locale' => 'id']));
        }

        $this->assertSame(1, Customer::where('user_id', $customer->id)->count());
        $this->assertSame(2, Booking::withoutGlobalScopes()->where('branch_id', $flight->branch_id)->count());
    }

    public function test_hotel_can_be_created(): void
    {
        $admin = User::where('email', 'cs.bali@indogate.com')->first();
        $admin->givePermissionTo('catalog.manage');

        $this->actingAs($admin)->post(route('admin.hotels.store', ['locale' => 'id']), [
            'name' => 'Hotel Test', 'description' => 'desc', 'location' => 'Ubud',
            'star_rating' => 4, 'base_price_per_night' => 750000,
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, Hotel::where('star_rating', 4)->count());
    }

    public function test_staff_without_branch_sees_no_branch_data(): void
    {
        $this->makeFlight();
        $cs = User::where('email', 'cs.bali@indogate.com')->first();
        $cs->update(['branch_id' => null]);

        $this->actingAs($cs->fresh());
        $this->assertSame(0, FlightRoute::count());
    }

    public function test_staff_cannot_self_delete_account(): void
    {
        $cs = User::where('email', 'cs.bali@indogate.com')->first();

        $this->actingAs($cs)->delete(route('profile.destroy', ['locale' => 'id']), ['password' => 'password'])->assertForbidden();
        $this->assertNotNull($cs->fresh());
    }

    public function test_branch_switcher_rejects_inactive_branch(): void
    {
        $inactive = Branch::create(['code' => 'OFF', 'name' => 'Closed', 'is_active' => false]);
        $admin = User::where('email', 'admin@indogate.com')->first();

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($admin)->test(BranchSwitcher::class)
            ->call('switchTo', $inactive->id);
    }

    private function makeFlight(): FlightRoute
    {
        return FlightRoute::create([
            'branch_id' => Branch::first()->id, 'airline' => 'GA', 'origin' => 'CGK', 'destination' => 'DPS',
            'departure_at' => now()->addDays(5), 'base_price' => 1_000_000, 'seat_quota' => 10,
        ]);
    }

    private function makeBooking(array $attrs = []): Booking
    {
        $user = User::where('email', 'customer@indogate.com')->first();
        $customer = Customer::firstOrCreate(['user_id' => $user->id], ['full_name' => $user->name]);

        return Booking::withoutGlobalScopes()->create(array_merge([
            'branch_id' => User::where('email', 'finance.bali@indogate.com')->value('branch_id'),
            'booking_number' => Str::uuid(),
            'customer_id' => $customer->id,
            'status' => Booking::STATUS_PENDING_PAYMENT,
            'total_amount' => 1_000_000,
            'currency' => 'IDR',
        ], $attrs));
    }
}
