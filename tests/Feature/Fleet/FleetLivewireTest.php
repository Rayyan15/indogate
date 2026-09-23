<?php

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Services\AssignmentService;
use App\Livewire\Admin\Booking\PackageBookingShow;
use App\Livewire\Admin\Fleet\AssignmentCalendar;
use App\Livewire\Admin\Fleet\DriverList;
use App\Livewire\Admin\Fleet\VehicleList;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class FleetLivewireTest extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_driver_list_crud_operations(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');
        CurrentBranch::switchTo($branch->id);

        Livewire::actingAs($user)
            ->test(DriverList::class)
            ->assertSuccessful()
            ->call('openCreateModal')
            ->set('name', 'Made Sukadana')
            ->set('gender', 'male')
            ->set('phone', '08123456789')
            ->set('languages', ['id', 'en'])
            ->call('save')
            ->assertHasNoErrors();

        $driver = Driver::where('name', 'Made Sukadana')->first();
        $this->assertNotNull($driver);
        $this->assertSame('male', $driver->gender);

        // Edit driver
        Livewire::actingAs($user)
            ->test(DriverList::class)
            ->call('openEditModal', $driver->id)
            ->set('phone', '08999999999')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('08999999999', $driver->fresh()->phone);

        // Toggle status
        Livewire::actingAs($user)
            ->test(DriverList::class)
            ->call('toggleStatus', $driver->id);

        $this->assertFalse($driver->fresh()->is_active);

        // Delete driver
        Livewire::actingAs($user)
            ->test(DriverList::class)
            ->call('deleteDriver', $driver->id);

        $this->assertSoftDeleted('drivers', ['id' => $driver->id]);
    }

    public function test_vehicle_list_crud_operations(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');
        CurrentBranch::switchTo($branch->id);

        Livewire::actingAs($user)
            ->test(VehicleList::class)
            ->assertSuccessful()
            ->call('openCreateModal')
            ->set('plate', 'DK 5555 ZZ')
            ->set('type', 'Toyota HiAce')
            ->set('capacity', 12)
            ->call('save')
            ->assertHasNoErrors();

        $vehicle = Vehicle::where('plate', 'DK 5555 ZZ')->first();
        $this->assertNotNull($vehicle);
        $this->assertSame(12, $vehicle->capacity);

        // Edit
        Livewire::actingAs($user)
            ->test(VehicleList::class)
            ->call('openEditModal', $vehicle->id)
            ->set('capacity', 14)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(14, $vehicle->fresh()->capacity);

        // Delete
        Livewire::actingAs($user)
            ->test(VehicleList::class)
            ->call('deleteVehicle', $vehicle->id);

        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }

    public function test_package_booking_show_driver_assignment_workflow(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $femaleDriver = $this->createDriver($branch, ['name' => 'Kadek Driver', 'gender' => 'female']);
        $vehicle = $this->createVehicle($branch, ['plate' => 'DK 3333 CC']);

        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');
        CurrentBranch::switchTo($branch->id);

        $component = Livewire::actingAs($user)
            ->test(PackageBookingShow::class, ['packageBooking' => $booking])
            ->assertSuccessful();

        // 1. Set gender preference to female
        $component->call('updateGenderPreference', 'female');
        $this->assertSame('female', $booking->fresh()->driver_gender_preference);

        // 2. Assign driver and vehicle
        $component->set('selected_driver_id', $femaleDriver->id)
            ->set('selected_vehicle_id', $vehicle->id)
            ->set('assignment_notes', 'Antar jemput bandara Ngurah Rai')
            ->call('assignDriver')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('driver_assignments', [
            'booking_id' => $booking->id,
            'driver_id' => $femaleDriver->id,
            'vehicle_id' => $vehicle->id,
            'status' => DriverAssignment::STATUS_ASSIGNED,
        ]);

        $assignment = DriverAssignment::where('booking_id', $booking->id)->firstOrFail();

        // 3. Cancel assignment
        $component->call('openCancelAssignmentModal', $assignment->id)
            ->set('cancel_assignment_reason', 'Tamu mengganti rencana')
            ->call('cancelAssignment')
            ->assertHasNoErrors();

        $this->assertSame(DriverAssignment::STATUS_CANCELLED, $assignment->fresh()->status);
    }

    public function test_assignment_calendar_view_and_cancellation(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'Nyoman Driver']);
        $assignment = (new AssignmentService)->assign(
            $booking,
            $driver,
            null,
            now()->toDateString(),
            now()->addDays(2)->toDateString()
        );

        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');
        CurrentBranch::switchTo($branch->id);

        Livewire::actingAs($user)
            ->test(AssignmentCalendar::class)
            ->assertSuccessful()
            ->assertSee('Nyoman Driver')
            ->assertSee($booking->code)
            ->call('openCancelModal', $assignment->id)
            ->set('cancel_reason', 'Jadwal dibatalkan')
            ->call('cancelAssignment')
            ->assertHasNoErrors();

        $this->assertSame(DriverAssignment::STATUS_CANCELLED, $assignment->fresh()->status);
    }
}
