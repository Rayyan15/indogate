<?php

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Services\AssignmentService;
use App\Models\Branch;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    private AssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssignmentService;
    }

    public function test_bali_drivers_never_appear_in_suggestions_for_jakarta_booking(): void
    {
        $this->seed();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        // Create driver in Bali
        $baliDriver = $this->createDriver($bali, ['name' => 'Wayan Bali Driver']);

        // Create driver in Jakarta
        $jktDriver = $this->createDriver($jkt, ['name' => 'Bambang JKT Driver']);

        // Create Jakarta booking
        $lead = $this->leadFor($jkt);
        $package = $this->packageWithOneHotelRoom($jkt);
        $quotation = $this->quotationFor($jkt, $lead, $package);
        $jktBooking = $this->bookingFor($quotation);

        $suggested = $this->service->suggestDrivers($jktBooking);

        $this->assertTrue($suggested->contains($jktDriver));
        $this->assertFalse($suggested->contains($baliDriver));
    }

    public function test_assigning_cross_branch_driver_is_rejected(): void
    {
        $this->seed();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        $baliDriver = $this->createDriver($bali, ['name' => 'Wayan Bali Driver']);

        $lead = $this->leadFor($jkt);
        $package = $this->packageWithOneHotelRoom($jkt);
        $quotation = $this->quotationFor($jkt, $lead, $package);
        $jktBooking = $this->bookingFor($quotation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver tidak berasal dari cabang yang sama');

        $this->service->assign(
            $jktBooking,
            $baliDriver,
            null,
            $jktBooking->departure_date,
            $jktBooking->return_date ?? $jktBooking->departure_date
        );
    }

    public function test_assigning_cross_branch_vehicle_is_rejected(): void
    {
        $this->seed();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        $jktDriver = $this->createDriver($jkt, ['name' => 'Bambang JKT Driver']);
        $baliVehicle = $this->createVehicle($bali, ['plate' => 'DK 1111 ZZ']);

        $lead = $this->leadFor($jkt);
        $package = $this->packageWithOneHotelRoom($jkt);
        $quotation = $this->quotationFor($jkt, $lead, $package);
        $jktBooking = $this->bookingFor($quotation);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Kendaraan tidak berasal dari cabang yang sama');

        $this->service->assign(
            $jktBooking,
            $jktDriver,
            $baliVehicle,
            $jktBooking->departure_date,
            $jktBooking->return_date ?? $jktBooking->departure_date
        );
    }

    public function test_cross_branch_fleet_model_access_is_denied_by_policy(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        CurrentBranch::switchTo($bali->id);

        $jktDriver = $this->createDriver($jkt, ['name' => 'Jakarta Driver']);
        $jktVehicle = $this->createVehicle($jkt, ['plate' => 'B 1234 CD']);

        // Super Admin is operating in Bali branch context
        $this->assertFalse(Gate::forUser($superAdmin)->allows('view', $jktDriver));
        $this->assertFalse(Gate::forUser($superAdmin)->allows('update', $jktDriver));
        $this->assertFalse(Gate::forUser($superAdmin)->allows('view', $jktVehicle));
        $this->assertFalse(Gate::forUser($superAdmin)->allows('update', $jktVehicle));
    }
}
