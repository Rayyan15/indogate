<?php

namespace App\Policies;

use App\Domain\Pricing\Models\Season;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class SeasonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pricing.manage');
    }

    public function view(User $user, Season $season): bool
    {
        return $this->viewAny($user) && $season->branch_id === CurrentBranch::id();
    }

    public function update(User $user, Season $season): bool
    {
        return $user->can('pricing.manage') && $season->branch_id === CurrentBranch::id();
    }
}
