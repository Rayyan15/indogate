<?php

namespace App\Policies;

use App\Domain\Finance\Models\Refund;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class RefundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment.verify') || $user->can('report.margin.view');
    }

    public function view(User $user, Refund $refund): bool
    {
        $hasPermission = $user->can('payment.verify') || $user->can('report.margin.view');

        return $hasPermission && (int) $refund->branch_id === (int) CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('payment.verify');
    }
}
