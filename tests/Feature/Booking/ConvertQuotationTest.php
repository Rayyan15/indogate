<?php

namespace Tests\Feature\Booking;

use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ConvertQuotationTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_converting_copies_quotation_data_and_starts_confirmed(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $booking = $this->bookingFor($quotation);

        $this->assertSame($quotation->id, $booking->quotation_id);
        $this->assertSame($quotation->currency, $booking->currency);
        $this->assertSame($quotation->items->sum('total_minor'), $booking->total_minor);
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertNotEmpty($booking->code);

        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => 'draft',
            'to_status' => 'confirmed',
        ]);
    }

    public function test_booking_codes_never_collide(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);

        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $quotation = $this->quotationFor($branch, $lead, $package);
            $codes[] = $this->bookingFor($quotation)->code;
        }

        $this->assertSame($codes, array_unique($codes));
        $this->assertSame(5, PackageBooking::count());
    }
}
