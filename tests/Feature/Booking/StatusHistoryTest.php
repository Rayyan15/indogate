<?php

namespace Tests\Feature\Booking;

use App\Domain\Booking\BookingStateMachine;
use App\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class StatusHistoryTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_each_status_change_produces_exactly_one_history_row_in_order(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);
        $machine = new BookingStateMachine;

        // conversion already wrote 1 row (draft -> confirmed)
        $this->assertSame(1, $booking->statusHistories()->count());

        $machine->transition($booking, BookingStatus::PARTIALLY_PAID);
        $machine->transition($booking, BookingStatus::PAID);
        $machine->transition($booking, BookingStatus::IN_PROGRESS);

        $this->assertSame(4, $booking->statusHistories()->count());

        $sequence = $booking->statusHistories()->oldest('id')->pluck('to_status')->map(fn ($s) => $s->value)->all();
        $this->assertSame(['confirmed', 'partially_paid', 'paid', 'in_progress'], $sequence);
    }
}
