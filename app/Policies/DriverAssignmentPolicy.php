<?php

namespace App\Policies;

use App\Domain\Fleet\Models\DriverAssignment;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class DriverAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('driver.assign') || $user->can('booking.manage');
    }

    public function view(User $user, DriverAssignment $assignment): bool
    {
        return $this->viewAny($user) && $assignment->branch_id === CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('driver.assign');
    }

    public function assign(User $user): bool
    {
        return $user->can('driver.assign');
    }

    public function update(User $user, DriverAssignment $assignment): bool
    {
        return $user->can('driver.assign') && $assignment->branch_id === CurrentBranch::id();
    }

    public function delete(User $user, DriverAssignment $assignment): bool
    {
        return $user->can('driver.assign') && $assignment->branch_id === CurrentBranch::id();
    }
}
