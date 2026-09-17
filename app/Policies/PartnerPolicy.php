<?php

namespace App\Policies;

use App\Domain\Catalog\Models\Partner;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class PartnerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    /**
     * Cross-branch direct access must 403, not 404 — mirrors BookingPolicy.
     * Route::bind('partner', ...) in AppServiceProvider bypasses
     * BranchScope so a wrong-branch id still reaches here.
     */
    public function view(User $user, Partner $partner): bool
    {
        return $this->viewAny($user) && $partner->branch_id === CurrentBranch::id();
    }

    public function update(User $user, Partner $partner): bool
    {
        return $user->can('catalog.manage') && $partner->branch_id === CurrentBranch::id();
    }
}
