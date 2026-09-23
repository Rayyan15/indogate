<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('booking.manage') || $user->can('payment.verify');
    }

    /**
     * Cross-branch direct access must 403, not 404. Route::bind() for
     * 'booking' (AppServiceProvider) resolves the model without
     * BranchScope, so a wrong-branch id still reaches here and gets an
     * explicit denial instead of the query silently filtering it to
     * "not found".
     */
    public function view(User $user, Booking $booking): bool
    {
        return $this->viewAny($user) && $booking->branch_id === CurrentBranch::id();
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage') && $booking->branch_id === CurrentBranch::id();
    }

    public function verifyPayment(User $user, Booking $booking): bool
    {
        return $user->can('payment.verify') && $booking->branch_id === CurrentBranch::id();
    }

    public function assignDriver(User $user, Booking $booking): bool
    {
        return $user->can('driver.assign') && $booking->branch_id === CurrentBranch::id();
    }
}
