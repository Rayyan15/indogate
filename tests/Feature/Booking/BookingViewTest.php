<?php

namespace Tests\Feature\Booking;

use App\Livewire\Admin\Booking\PackageBookingList;
use App\Livewire\Admin\Booking\PackageBookingShow;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class BookingViewTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_cs_admin_can_access_package_bookings_index(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->assignRole('CS Admin');

        $response = $this->actingAs($user)->get('/id/admin/package-bookings');
        $response->assertOk();

        Livewire::actingAs($user)
            ->test(PackageBookingList::class)
            ->assertSuccessful();
    }

    public function test_cs_admin_can_access_package_booking_show(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->assignRole('CS Admin');

        $response = $this->actingAs($user)->get("/id/admin/package-bookings/{$booking->id}");
        $response->assertOk();

        Livewire::actingAs($user)
            ->test(PackageBookingShow::class, ['packageBooking' => $booking])
            ->assertSuccessful()
            ->assertSee($booking->code);
    }

    public function test_cross_branch_access_is_forbidden_403(): void
    {
        $this->seed();
        $bali = $this->baliBranch();
        $jakarta = Branch::where('name', 'Jakarta')->first() ?? Branch::create(['name' => 'Jakarta', 'code' => 'JKT']);

        $lead = $this->leadFor($bali);
        $package = $this->packageWithOneHotelRoom($bali);
        $quotation = $this->quotationFor($bali, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $jakartaUser = User::factory()->create([
            'branch_id' => $jakarta->id,
            'is_active' => true,
        ]);
        $jakartaUser->givePermissionTo('booking.manage');

        $response = $this->actingAs($jakartaUser)->get("/id/admin/package-bookings/{$booking->id}");
        $response->assertForbidden();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/id/admin/package-bookings');
        $response->assertForbidden();
    }

    public function test_component_can_add_and_remove_guest(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->assignRole('CS Admin');

        $component = Livewire::actingAs($user)
            ->test(PackageBookingShow::class, ['packageBooking' => $booking])
            ->set('guest_name', 'Ahmed Al-Farsi')
            ->set('guest_passport_number', 'P12345678')
            ->set('guest_nationality', 'SA')
            ->set('guest_is_lead', true)
            ->call('addGuest');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('booking_guests', [
            'booking_id' => $booking->id,
            'name' => 'Ahmed Al-Farsi',
            'nationality' => 'SA',
            'is_lead_guest' => true,
        ]);

        $guest = $booking->guests()->first();
        $component->call('removeGuest', $guest->id);
        $this->assertDatabaseMissing('booking_guests', [
            'id' => $guest->id,
        ]);
    }

    public function test_component_can_transition_status(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->assignRole('CS Admin');

        Livewire::actingAs($user)
            ->test(PackageBookingShow::class, ['packageBooking' => $booking])
            ->call('transitionTo', 'partially_paid')
            ->assertHasNoErrors();

        $this->assertSame('partially_paid', $booking->fresh()->status->value);
    }

    public function test_component_can_add_note_and_cancel_booking(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->assignRole('CS Admin');

        $component = Livewire::actingAs($user)
            ->test(PackageBookingShow::class, ['packageBooking' => $booking])
            ->set('note_text', 'Catatan penting untuk operasional')
            ->call('addNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('booking_notes', [
            'booking_id' => $booking->id,
            'note' => 'Catatan penting untuk operasional',
        ]);

        $component->set('cancel_reason', 'Tamu ada keperluan mendadak')
            ->call('cancel')
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $booking->fresh()->status->value);
    }

    public function test_package_booking_list_calendar_navigation(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $user = User::factory()->create([
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->assignRole('CS Admin');

        $initialMonth = now()->format('Y-m');
        $prevMonth = now()->subMonth()->format('Y-m');
        $nextMonth = now()->addMonth()->format('Y-m');

        Livewire::actingAs($user)
            ->test(PackageBookingList::class)
            ->assertSet('calendarMonth', $initialMonth)
            ->call('previousMonth')
            ->assertSet('calendarMonth', $prevMonth)
            ->call('nextMonth')
            ->call('nextMonth')
            ->assertSet('calendarMonth', $nextMonth);
    }
}
