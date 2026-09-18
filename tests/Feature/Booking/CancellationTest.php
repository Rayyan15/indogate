<?php

namespace Tests\Feature\Booking;

use App\Domain\Booking\BookingStateMachine;
use App\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class CancellationTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_cancellation_without_reason_is_rejected(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $this->expectException(InvalidArgumentException::class);
        (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, reason: null);
    }

    public function test_cancellation_with_reason_succeeds(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, reason: 'Tamu batal berangkat');

        $this->assertSame(BookingStatus::CANCELLED, $booking->fresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'to_status' => 'cancelled',
            'reason' => 'Tamu batal berangkat',
        ]);
    }
}
