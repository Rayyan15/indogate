<?php

namespace Tests\Unit\Fleet;

use App\Domain\Fleet\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class AssignmentServiceTest extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    private AssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssignmentService;
    }

    public function test_suggest_drivers_returns_only_female_when_female_preferred(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $booking->update(['driver_gender_preference' => 'female']);

        $femaleDriver = $this->createDriver($branch, [
            'name' => 'Ni Wayan Driver',
            'gender' => 'female',
        ]);
        $maleDriver = $this->createDriver($branch, [
            'name' => 'I Made Driver',
            'gender' => 'male',
        ]);

        $suggested = $this->service->suggestDrivers($booking);

        $this->assertCount(1, $suggested);
        $this->assertTrue($suggested->contains($femaleDriver));
        $this->assertFalse($suggested->contains($maleDriver));
        $this->assertNull($this->service->getSuggestionWarning($booking, $suggested));
    }

    public function test_suggest_drivers_returns_empty_when_no_female_driver_exists(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $booking->update(['driver_gender_preference' => 'female']);

        $maleDriver = $this->createDriver($branch, [
            'name' => 'I Made Driver',
            'gender' => 'male',
        ]);

        $suggested = $this->service->suggestDrivers($booking);

        $this->assertCount(0, $suggested);
        $warning = $this->service->getSuggestionWarning($booking, $suggested);
        $this->assertNotNull($warning);
        $this->assertStringContainsString('perempuan', $warning);
    }

    public function test_suggest_drivers_returns_any_driver_when_no_preference(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $booking->update(['driver_gender_preference' => null]);

        $femaleDriver = $this->createDriver($branch, [
            'name' => 'Ni Wayan Driver',
            'gender' => 'female',
        ]);
        $maleDriver = $this->createDriver($branch, [
            'name' => 'I Made Driver',
            'gender' => 'male',
        ]);

        $suggested = $this->service->suggestDrivers($booking);

        $this->assertCount(2, $suggested);
    }

    public function test_assign_strictly_rejects_wrong_gender_driver(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $booking->update(['driver_gender_preference' => 'female']);

        $maleDriver = $this->createDriver($branch, [
            'name' => 'I Made Driver',
            'gender' => 'male',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver tidak sesuai preferensi gender');

        $this->service->assign(
            $booking,
            $maleDriver,
            null,
            $booking->departure_date,
            $booking->return_date ?? $booking->departure_date
        );
    }

    public function test_suggest_vehicles_excludes_conflicting_vehicles(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch);
        $veh1 = $this->createVehicle($branch, ['plate' => 'DK 1111 AA']);
        $veh2 = $this->createVehicle($branch, ['plate' => 'DK 2222 BB']);

        // Assign veh1 to booking
        $this->service->assign(
            $booking,
            $driver,
            $veh1,
            $booking->departure_date,
            $booking->return_date ?? $booking->departure_date
        );

        // For another booking on same date, veh1 should not be suggested, only veh2
        $booking2 = $this->bookingFor($this->quotationFor($branch, $this->leadFor($branch), $package));
        $suggestedVehicles = $this->service->suggestVehicles($booking2);

        $this->assertCount(1, $suggestedVehicles);
        $this->assertEquals('DK 2222 BB', $suggestedVehicles->first()->plate);
    }
}
