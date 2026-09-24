<?php

namespace App\Domain\Booking;

use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use Illuminate\Support\Collection;

/** Confirmed / partially paid bookings that still owe money, soonest departure first. */
class AwaitingPaymentBookings
{
    /** @return Collection<int, PackageBooking> */
    public function get(?int $departingWithinDays = null): Collection
    {
        // ponytail: balance is computed in PHP (FX-aware), fine at branch scale; move to SQL if open bookings reach thousands.
        return PackageBooking::with(['quotation.lead', 'payments', 'refunds'])
            ->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID])
            ->when($departingWithinDays !== null, fn ($q) => $q
                ->whereDate('departure_date', '>=', today())
                ->whereDate('departure_date', '<=', today()->addDays($departingWithinDays)))
            ->orderBy('departure_date')
            ->get()
            ->filter(fn (PackageBooking $b) => $b->remainingBalanceMinor() > 0)
            ->values();
    }
}
