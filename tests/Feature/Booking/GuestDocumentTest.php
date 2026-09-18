<?php

namespace Tests\Feature\Booking;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class GuestDocumentTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_passport_number_is_stored_encrypted(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $guest = $booking->guests()->create([
            'name' => 'Fatima Al-Rashid',
            'passport_number' => 'A1234567',
            'nationality' => 'SA',
            'is_lead_guest' => true,
        ]);

        // Application-level: the cast decrypts transparently.
        $this->assertSame('A1234567', $guest->fresh()->passport_number);

        // Database-level: the raw stored value must never be the plaintext.
        $raw = DB::table('booking_guests')->where('id', $guest->id)->value('passport_number');
        $this->assertNotSame('A1234567', $raw);
        $this->assertStringNotContainsString('A1234567', $raw);
    }
}
