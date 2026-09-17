<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment.verify');
    }

    /**
     * Route::bind() for 'payment' (AppServiceProvider) resolves without
     * BranchScope, so cross-branch access reaches here for an explicit
     * 403 rather than being filtered into a 404 by the query itself.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->can('payment.verify') && $payment->branch_id === CurrentBranch::id();
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->can('payment.verify') && $payment->branch_id === CurrentBranch::id();
    }
}
