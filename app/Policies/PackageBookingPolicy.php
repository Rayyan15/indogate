<?php

namespace App\Policies;

use App\Domain\Booking\Models\PackageBooking;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class PackageBookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('booking.manage');
    }

    public function view(User $user, PackageBooking $booking): bool
    {
        return $this->viewAny($user) && $booking->branch_id === CurrentBranch::id();
    }

    public function update(User $user, PackageBooking $booking): bool
    {
        return $user->can('booking.manage') && $booking->branch_id === CurrentBranch::id();
    }
}
