<?php

namespace Tests\Unit\Booking;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Exceptions\InvalidBookingTransitionException;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Quotation;
use App\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class BookingStateMachineTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    private function booking(BookingStatus $status = BookingStatus::CONFIRMED): PackageBooking
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);

        $quotation = Quotation::create([
            'branch_id' => $branch->id,
            'lead_id' => $lead->id,
            'package_id' => $package->id,
            'token' => Str::random(48),
            'currency' => 'IDR',
            'locked_rate' => '1.00000000',
            'valid_until' => now()->addDays(7),
            'status' => 'draft',
        ]);

        return PackageBooking::create([
            'branch_id' => $branch->id,
            'quotation_id' => $quotation->id,
            'code' => 'BK-'.strtoupper(Str::random(8)),
            'status' => $status,
            'departure_date' => now()->addMonth(),
            'total_minor' => 1_000_000,
            'currency' => 'IDR',
        ]);
    }

    #[DataProvider('validTransitions')]
    public function test_valid_transitions_succeed(BookingStatus $from, BookingStatus $to): void
    {
        $booking = $this->booking($from);

        (new BookingStateMachine)->transition($booking, $to, reason: $to === BookingStatus::CANCELLED ? 'test' : null);

        $this->assertSame($to, $booking->fresh()->status);
        $this->assertDatabaseHas('booking_status_histories', [
            'booking_id' => $booking->id,
            'from_status' => $from->value,
            'to_status' => $to->value,
        ]);
    }

    public static function validTransitions(): array
    {
        return [
            'draft->quoted' => [BookingStatus::DRAFT, BookingStatus::QUOTED],
            'draft->cancelled' => [BookingStatus::DRAFT, BookingStatus::CANCELLED],
            'quoted->confirmed' => [BookingStatus::QUOTED, BookingStatus::CONFIRMED],
            'quoted->expired' => [BookingStatus::QUOTED, BookingStatus::EXPIRED],
            'confirmed->partially_paid' => [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID],
            'partially_paid->paid' => [BookingStatus::PARTIALLY_PAID, BookingStatus::PAID],
            'paid->in_progress' => [BookingStatus::PAID, BookingStatus::IN_PROGRESS],
            'in_progress->completed' => [BookingStatus::IN_PROGRESS, BookingStatus::COMPLETED],
            'paid->cancelled' => [BookingStatus::PAID, BookingStatus::CANCELLED],
        ];
    }

    #[DataProvider('invalidTransitions')]
    public function test_invalid_transitions_throw(BookingStatus $from, BookingStatus $to): void
    {
        $booking = $this->booking($from);

        $this->expectException(InvalidBookingTransitionException::class);

        (new BookingStateMachine)->transition($booking, $to, reason: 'test');
    }

    public static function invalidTransitions(): array
    {
        return [
            'confirmed->completed skips steps' => [BookingStatus::CONFIRMED, BookingStatus::COMPLETED],
            'draft->paid skips steps' => [BookingStatus::DRAFT, BookingStatus::PAID],
            'completed->anything' => [BookingStatus::COMPLETED, BookingStatus::CANCELLED],
            'cancelled->anything' => [BookingStatus::CANCELLED, BookingStatus::CONFIRMED],
        ];
    }

    public function test_cancel_without_reason_is_rejected(): void
    {
        $booking = $this->booking(BookingStatus::CONFIRMED);

        $this->expectException(InvalidArgumentException::class);

        (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, reason: null);

        $this->assertSame(BookingStatus::CONFIRMED, $booking->fresh()->status);
    }

    public function test_each_transition_writes_exactly_one_history_row(): void
    {
        $booking = $this->booking(BookingStatus::CONFIRMED);

        (new BookingStateMachine)->transition($booking, BookingStatus::PARTIALLY_PAID);
        (new BookingStateMachine)->transition($booking, BookingStatus::PAID);

        $this->assertSame(2, $booking->statusHistories()->count());
    }
}
