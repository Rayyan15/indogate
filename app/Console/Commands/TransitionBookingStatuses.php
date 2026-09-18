<?php

namespace App\Console\Commands;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use App\Support\Branch\BranchScope;
use Illuminate\Console\Command;

class TransitionBookingStatuses extends Command
{
    protected $signature = 'bookings:transition-status';

    protected $description = 'Auto-advance bookings past their departure/return date (PRD M7 step 13)';

    public function handle(BookingStateMachine $machine): int
    {
        $advanced = 0;

        // One row at a time through the state machine — not a bulk update —
        // because every transition must still produce a history row and an
        // activity log entry (PRD M7, non-negotiable per rule.md §3).
        PackageBooking::withoutGlobalScope(BranchScope::class)
            ->where('status', BookingStatus::PAID)
            ->whereDate('departure_date', '<=', now())
            ->each(function (PackageBooking $booking) use ($machine, &$advanced) {
                $machine->transition($booking, BookingStatus::IN_PROGRESS);
                $advanced++;
            });

        PackageBooking::withoutGlobalScope(BranchScope::class)
            ->where('status', BookingStatus::IN_PROGRESS)
            ->whereNotNull('return_date')
            ->whereDate('return_date', '<', now())
            ->each(function (PackageBooking $booking) use ($machine, &$advanced) {
                $machine->transition($booking, BookingStatus::COMPLETED);
                $advanced++;
            });

        $this->info("Advanced {$advanced} booking(s).");

        return self::SUCCESS;
    }
}
