<?php

namespace Tests\Feature\Booking;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_unsigned_access_is_rejected_and_signed_access_is_logged(): void
    {
        Storage::fake('local');
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);
        $cs = User::role('CS Admin')->firstOrFail();

        Storage::disk('local')->put("booking-guests/{$booking->id}/passport-1.pdf", 'fake-pdf-content');
        $guest = $booking->guests()->create([
            'name' => 'Fatima', 'passport_number' => 'A1234567', 'is_lead_guest' => true,
            'passport_file' => "booking-guests/{$booking->id}/passport-1.pdf",
        ]);

        $unsignedUrl = route('admin.package-bookings.guests.passport-download', ['locale' => 'id', 'guest' => $guest->id]);
        $this->actingAs($cs)->get($unsignedUrl)->assertForbidden();

        $signedUrl = URL::temporarySignedRoute(
            'admin.package-bookings.guests.passport-download',
            now()->addMinutes(15),
            ['locale' => 'id', 'guest' => $guest->id],
        );
        $this->actingAs($cs)->get($signedUrl)->assertOk();

        $this->assertTrue(
            Activity::query()->where('subject_type', $guest->getMorphClass())->where('subject_id', $guest->id)->exists(),
            'Expected a document-access activity log entry.'
        );
    }
}
