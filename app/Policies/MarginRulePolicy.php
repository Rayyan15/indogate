<?php

namespace App\Policies;

use App\Domain\Pricing\Models\MarginRule;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class MarginRulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pricing.manage');
    }

    public function view(User $user, MarginRule $marginRule): bool
    {
        return $this->viewAny($user) && $marginRule->branch_id === CurrentBranch::id();
    }

    public function update(User $user, MarginRule $marginRule): bool
    {
        return $user->can('pricing.manage') && $marginRule->branch_id === CurrentBranch::id();
    }
}
