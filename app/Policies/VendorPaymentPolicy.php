<?php

namespace App\Policies;

use App\Domain\Finance\Models\VendorPayment;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class VendorPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('payment.verify') || $user->can('report.margin.view');
    }

    public function view(User $user, VendorPayment $vendorPayment): bool
    {
        $hasPermission = $user->can('payment.verify') || $user->can('report.margin.view');

        return $hasPermission && (int) $vendorPayment->branch_id === (int) CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('payment.verify');
    }

    public function update(User $user, VendorPayment $vendorPayment): bool
    {
        return $user->can('payment.verify') && (int) $vendorPayment->branch_id === (int) CurrentBranch::id();
    }

    public function delete(User $user, VendorPayment $vendorPayment): bool
    {
        return $user->can('payment.verify') && (int) $vendorPayment->branch_id === (int) CurrentBranch::id();
    }
}
