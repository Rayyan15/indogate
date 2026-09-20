<?php

namespace App\Policies;

use App\Domain\Fleet\Models\Driver;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class DriverPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('driver.assign') || $user->can('booking.manage');
    }

    public function view(User $user, Driver $driver): bool
    {
        return $this->viewAny($user) && $driver->branch_id === CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('driver.assign');
    }

    public function update(User $user, Driver $driver): bool
    {
        return $user->can('driver.assign') && $driver->branch_id === CurrentBranch::id();
    }

    public function delete(User $user, Driver $driver): bool
    {
        return $user->can('driver.assign') && $driver->branch_id === CurrentBranch::id();
    }
}
