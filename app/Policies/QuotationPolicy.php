<?php

namespace App\Policies;

use App\Domain\Lead\Models\Quotation;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quotation.create');
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $this->viewAny($user) && $quotation->branch_id === CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('quotation.create');
    }
}
