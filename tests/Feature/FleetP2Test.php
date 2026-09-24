<?php

namespace Tests\Feature;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Fleet\Exceptions\ScheduleConflictException;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Services\AssignmentService;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Enums\BookingStatus;
use App\Jobs\GenerateBookingVoucher;
use App\Jobs\GeneratePackageItineraryPdf;
use App\Livewire\Admin\Booking\PackageBookingShow;
use App\Livewire\Admin\Fleet\DriverList;
use App\Livewire\Admin\Fleet\VehicleList;
use App\Livewire\Admin\Packaging\PackageBuilder;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class FleetP2Test extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    private function newBooking(): array
    {
        $branch = $this->baliBranch();
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $this->leadFor($branch), $package);

        return [$branch, $this->bookingFor($quotation), $package];
    }

    private function staff(): User
    {
        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');
        CurrentBranch::switchTo($this->baliBranch()->id);

        return $user;
    }

    public function test_cancelling_booking_cancels_open_assignments(): void
    {
        $this->seed();
        [$branch, $booking] = $this->newBooking();
        $assignment = (new AssignmentService)->assign($booking, $this->createDriver($branch), null, '2026-12-01', '2026-12-03');
        $done = DriverAssignment::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'booking_id' => $booking->id,
            'driver_id' => $assignment->driver_id, 'date_from' => '2026-01-01', 'date_to' => '2026-01-02',
            'status' => DriverAssignment::STATUS_COMPLETED,
        ]);

        (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, 'test', $this->staff());

        $this->assertSame(DriverAssignment::STATUS_CANCELLED, $assignment->fresh()->status);
        $this->assertSame(DriverAssignment::STATUS_COMPLETED, $done->fresh()->status);
    }

    public function test_assign_rejects_cancelled_and_completed_bookings(): void
    {
        $this->seed();
        [$branch, $booking] = $this->newBooking();
        $driver = $this->createDriver($branch);

        foreach ([BookingStatus::CANCELLED, BookingStatus::COMPLETED] as $status) {
            DB::table('package_bookings')->where('id', $booking->id)->update(['status' => $status->value]);

            try {
                (new AssignmentService)->assign($booking->fresh(), $driver, null, '2026-12-01', '2026-12-03');
                $this->fail("assign should reject {$status->value} booking");
            } catch (InvalidArgumentException) {
                $this->assertSame(0, DriverAssignment::withoutGlobalScopes()->count());
            }
        }
    }

    public function test_operational_stats_filter_assignments_by_range(): void
    {
        $this->seed();
        [$branch, $booking] = $this->newBooking();
        (new AssignmentService)->assign($booking, $this->createDriver($branch), null, '2026-03-10', '2026-03-12');

        $svc = new DashboardMetricsService;
        $in = $svc->getOperationalStats($branch->id, Carbon::parse('2026-03-11'), Carbon::parse('2026-03-20'));
        $out = $svc->getOperationalStats($branch->id, Carbon::parse('2026-04-01'), Carbon::parse('2026-04-30'));

        $this->assertSame(1, $in['active_assignments']);
        $this->assertSame(0, $out['active_assignments']);
    }

    public function test_driver_and_vehicle_with_future_assignment_cannot_be_deleted_or_deactivated(): void
    {
        $this->seed();
        [$branch, $booking] = $this->newBooking();
        $driver = $this->createDriver($branch);
        $vehicle = $this->createVehicle($branch);
        (new AssignmentService)->assign($booking, $driver, $vehicle, now()->addDays(5)->toDateString(), now()->addDays(7)->toDateString());
        $user = $this->staff();

        Livewire::actingAs($user)->test(DriverList::class)
            ->call('deleteDriver', $driver->id)->assertHasErrors('fleet')
            ->call('toggleStatus', $driver->id)->assertHasErrors('fleet');
        Livewire::actingAs($user)->test(VehicleList::class)
            ->call('deleteVehicle', $vehicle->id)->assertHasErrors('fleet')
            ->call('toggleStatus', $vehicle->id)->assertHasErrors('fleet');

        $this->assertNotSoftDeleted('drivers', ['id' => $driver->id]);
        $this->assertNotSoftDeleted('vehicles', ['id' => $vehicle->id]);
        $this->assertTrue($driver->fresh()->is_active);
        $this->assertTrue($vehicle->fresh()->is_active);
    }

    public function test_failed_voucher_job_flags_cache_and_stops_polling(): void
    {
        $this->seed();
        [, $booking] = $this->newBooking();
        $user = $this->staff();

        (new GenerateBookingVoucher($booking->id, 'en', $user->id))->failed(new \RuntimeException('boom'));
        $this->assertTrue(Cache::has(GenerateBookingVoucher::failedKey($booking->id, $user->id)));

        Livewire::actingAs($user)->test(PackageBookingShow::class, ['packageBooking' => $booking])
            ->set('exportPending', true)
            ->call('checkVoucherReady')
            ->assertSet('exportPending', false)
            ->assertHasErrors('export');
    }

    public function test_failed_itinerary_job_flags_cache_and_stops_polling(): void
    {
        $this->seed();
        [, , $package] = $this->newBooking();
        $user = $this->staff();
        $user->givePermissionTo('catalog.manage');

        (new GeneratePackageItineraryPdf($package->id, 'en', $user->id))->failed(new \RuntimeException('boom'));

        Livewire::actingAs($user)->test(PackageBuilder::class, ['package' => $package])
            ->set('exportPending', true)
            ->call('checkExportReady')
            ->assertSet('exportPending', false)
            ->assertHasErrors('export');
    }

    public function test_assign_runs_inside_a_transaction_and_blocks_double_booking(): void
    {
        $this->seed();
        [$branch, $booking] = $this->newBooking();
        $driver = $this->createDriver($branch);
        $levels = [];
        DB::listen(function () use (&$levels) {
            $levels[] = DB::transactionLevel();
        });

        (new AssignmentService)->assign($booking, $driver, null, '2026-12-01', '2026-12-03');

        // overlap check + insert ran while a transaction was open
        $this->assertNotEmpty(array_filter($levels, fn ($l) => $l > 1));

        [, $booking2] = $this->newBooking();
        $this->expectException(ScheduleConflictException::class);
        (new AssignmentService)->assign($booking2, $driver, null, '2026-12-02', '2026-12-04');
    }

    public function test_status_transition_command_advances_assignments_idempotently(): void
    {
        $this->seed();
        [$branch, $booking] = $this->newBooking();
        $driver = $this->createDriver($branch);
        $mk = fn ($from, $to) => DriverAssignment::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'booking_id' => $booking->id, 'driver_id' => $driver->id,
            'date_from' => $from, 'date_to' => $to, 'status' => DriverAssignment::STATUS_ASSIGNED,
        ]);
        $future = $mk(now()->addDays(10)->toDateString(), now()->addDays(12)->toDateString());
        $running = $mk(now()->subDay()->toDateString(), now()->addDay()->toDateString());
        $past = $mk(now()->subDays(10)->toDateString(), now()->subDays(5)->toDateString());
        $cancelled = $mk(now()->subDays(10)->toDateString(), now()->subDays(5)->toDateString());
        $cancelled->update(['status' => DriverAssignment::STATUS_CANCELLED]);

        $this->artisan('fleet:transition-assignments')->assertSuccessful();
        $this->artisan('fleet:transition-assignments')->expectsOutput('Advanced 0 assignment(s).')->assertSuccessful();

        $this->assertSame(DriverAssignment::STATUS_ASSIGNED, $future->fresh()->status);
        $this->assertSame(DriverAssignment::STATUS_IN_PROGRESS, $running->fresh()->status);
        $this->assertSame(DriverAssignment::STATUS_COMPLETED, $past->fresh()->status);
        $this->assertSame(DriverAssignment::STATUS_CANCELLED, $cancelled->fresh()->status);
    }
}
