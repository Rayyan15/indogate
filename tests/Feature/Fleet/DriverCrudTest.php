<?php

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Services\AssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class DriverCrudTest extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    private AssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssignmentService;
    }

    public function test_inactive_drivers_do_not_appear_in_suggestions(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $activeDriver = $this->createDriver($branch, [
            'name' => 'Active Driver',
            'is_active' => true,
        ]);
        $inactiveDriver = $this->createDriver($branch, [
            'name' => 'Inactive Driver',
            'is_active' => false,
        ]);

        $suggested = $this->service->suggestDrivers($booking);

        $this->assertTrue($suggested->contains($activeDriver));
        $this->assertFalse($suggested->contains($inactiveDriver));
    }

    public function test_assigning_inactive_driver_throws_exception(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $inactiveDriver = $this->createDriver($branch, [
            'name' => 'Inactive Driver',
            'is_active' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver berstatus nonaktif');

        $this->service->assign($booking, $inactiveDriver, null, '2026-07-01', '2026-07-05');
    }

    public function test_soft_deleted_driver_excluded_from_active_query_and_suggestions(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'To Be Deleted']);
        $driver->delete();

        $this->assertSoftDeleted('drivers', ['id' => $driver->id]);

        $suggested = $this->service->suggestDrivers($booking);
        $this->assertFalse($suggested->contains($driver));
    }

    public function test_driver_languages_stored_and_retrieved_as_json_array(): void
    {
        $this->seed();
        $branch = $this->baliBranch();

        $driver = $this->createDriver($branch, [
            'name' => 'Multilingual Driver',
            'languages' => ['id', 'en', 'ar', 'zh'],
        ]);

        $fresh = Driver::withoutGlobalScopes()->find($driver->id);
        $this->assertIsArray($fresh->languages);
        $this->assertSame(['id', 'en', 'ar', 'zh'], $fresh->languages);
    }
}
