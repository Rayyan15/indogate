<?php

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Exceptions\ScheduleConflictException;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ConflictDetectionTest extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    private AssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssignmentService;
    }

    public function test_overlapping_driver_assignment_dates_are_rejected(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'Ketut Driver']);
        $vehicle = $this->createVehicle($branch, ['plate' => 'DK 9999 XX']);

        // First assignment: 2026-07-01 to 2026-07-05
        $this->service->assign(
            $booking,
            $driver,
            $vehicle,
            '2026-07-01',
            '2026-07-05'
        );

        // Second booking attempting 2026-07-03 to 2026-07-07 with the same driver
        $lead2 = $this->leadFor($branch);
        $quotation2 = $this->quotationFor($branch, $lead2, $package);
        $booking2 = $this->bookingFor($quotation2);

        $this->expectException(ScheduleConflictException::class);
        $this->expectExceptionMessage('sudah ditugaskan');

        $this->service->assign(
            $booking2,
            $driver,
            null,
            '2026-07-03',
            '2026-07-07'
        );
    }

    public function test_boundary_overlapping_dates_are_rejected(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'Wayan Driver']);

        $this->service->assign($booking, $driver, null, '2026-07-01', '2026-07-05');

        $lead2 = $this->leadFor($branch);
        $quotation2 = $this->quotationFor($branch, $lead2, $package);
        $booking2 = $this->bookingFor($quotation2);

        $this->expectException(ScheduleConflictException::class);

        // Overlaps on boundary date 2026-07-05
        $this->service->assign($booking2, $driver, null, '2026-07-05', '2026-07-08');
    }

    public function test_non_overlapping_dates_are_allowed(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'Wayan Driver']);

        $assignment1 = $this->service->assign($booking, $driver, null, '2026-07-01', '2026-07-05');

        $lead2 = $this->leadFor($branch);
        $quotation2 = $this->quotationFor($branch, $lead2, $package);
        $booking2 = $this->bookingFor($quotation2);

        // Starts next day: 2026-07-06 to 2026-07-10
        $assignment2 = $this->service->assign($booking2, $driver, null, '2026-07-06', '2026-07-10');

        $this->assertInstanceOf(DriverAssignment::class, $assignment1);
        $this->assertInstanceOf(DriverAssignment::class, $assignment2);
        $this->assertNotSame($assignment1->id, $assignment2->id);
    }

    public function test_cancelled_assignments_do_not_cause_conflict(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'Wayan Driver']);

        $assignment = $this->service->assign($booking, $driver, null, '2026-07-01', '2026-07-05');
        $this->service->cancel($assignment, 'Tamu membatalkan penugasan');

        $lead2 = $this->leadFor($branch);
        $quotation2 = $this->quotationFor($branch, $lead2, $package);
        $booking2 = $this->bookingFor($quotation2);

        // Now assigning same driver for same date range should succeed
        $assignment2 = $this->service->assign($booking2, $driver, null, '2026-07-01', '2026-07-05');

        $this->assertInstanceOf(DriverAssignment::class, $assignment2);
        $this->assertSame(DriverAssignment::STATUS_ASSIGNED, $assignment2->status);
    }

    public function test_overlapping_vehicle_assignment_is_rejected(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver1 = $this->createDriver($branch, ['name' => 'Driver 1']);
        $driver2 = $this->createDriver($branch, ['name' => 'Driver 2']);
        $vehicle = $this->createVehicle($branch, ['plate' => 'DK 7777 AB']);

        $this->service->assign($booking, $driver1, $vehicle, '2026-07-01', '2026-07-05');

        $lead2 = $this->leadFor($branch);
        $quotation2 = $this->quotationFor($branch, $lead2, $package);
        $booking2 = $this->bookingFor($quotation2);

        $this->expectException(ScheduleConflictException::class);
        $this->expectExceptionMessage('Kendaraan');

        // Different driver, but same vehicle during overlapping period
        $this->service->assign($booking2, $driver2, $vehicle, '2026-07-03', '2026-07-07');
    }
}
