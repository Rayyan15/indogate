<?php

namespace App\Policies;

use App\Domain\Packaging\Models\Package;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function view(User $user, Package $package): bool
    {
        return $this->viewAny($user) && $package->branch_id === CurrentBranch::id();
    }

    public function update(User $user, Package $package): bool
    {
        return $user->can('catalog.manage') && $package->branch_id === CurrentBranch::id();
    }
}
