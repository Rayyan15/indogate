<?php

namespace App\Policies;

use App\Domain\Lead\Models\Lead;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lead.manage');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->viewAny($user) && $lead->branch_id === CurrentBranch::id();
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->can('lead.manage') && $lead->branch_id === CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('lead.manage');
    }
}
