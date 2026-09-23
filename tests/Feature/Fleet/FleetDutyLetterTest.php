<?php

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Services\AssignmentService;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsFleetFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class FleetDutyLetterTest extends TestCase
{
    use BuildsBookingFixtures, BuildsFleetFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_signed_url_allows_viewing_duty_letter_and_logs_activity(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch, ['name' => 'Wayan Driver']);
        $vehicle = $this->createVehicle($branch, ['plate' => 'DK 8888 BB']);

        $assignment = (new AssignmentService)->assign(
            $booking,
            $driver,
            $vehicle,
            $booking->departure_date,
            $booking->return_date ?? $booking->departure_date
        );

        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');
        CurrentBranch::switchTo($branch->id);

        $signedUrl = URL::temporarySignedRoute(
            'admin.fleet.assignments.duty-letter',
            now()->addHour(),
            ['locale' => 'id', 'assignment' => $assignment->id]
        );

        $response = $this->actingAs($user)->get($signedUrl);
        $response->assertOk();
        $response->assertSee('Wayan Driver');
        $response->assertSee('DK 8888 BB');
        $response->assertSee($booking->code);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'fleet',
            'causer_id' => $user->id,
            'description' => "Surat tugas dicetak untuk booking {$booking->code}",
        ]);
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $driver = $this->createDriver($branch);
        $assignment = (new AssignmentService)->assign(
            $booking,
            $driver,
            null,
            $booking->departure_date,
            $booking->return_date ?? $booking->departure_date
        );

        $user = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $user->givePermissionTo('driver.assign');

        // Plain URL without signature
        $plainUrl = "/id/admin/fleet/assignments/{$assignment->id}/duty-letter";
        $response = $this->actingAs($user)->get($plainUrl);
        $response->assertForbidden();
    }
}
