<?php

namespace App\Policies;

use App\Domain\Finance\Models\Payment;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment.verify') || $user->can('booking.manage');
    }

    public function view(User $user, Payment $payment): bool
    {
        $hasPermission = $user->can('payment.verify') || $user->can('booking.manage');

        return $hasPermission && (int) $payment->branch_id === (int) CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('booking.manage') || $user->can('payment.verify');
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->can('payment.verify') && (int) $payment->branch_id === (int) CurrentBranch::id();
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->can('payment.verify') && (int) $payment->branch_id === (int) CurrentBranch::id();
    }
}
