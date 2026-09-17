<?php

namespace App\Policies;

use App\Domain\Catalog\Models\InventoryItem;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $this->viewAny($user) && $item->branch_id === CurrentBranch::id();
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $user->can('catalog.manage') && $item->branch_id === CurrentBranch::id();
    }
}
