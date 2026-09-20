<?php

namespace App\Policies;

use App\Domain\Fleet\Models\Vehicle;
use App\Models\User;
use App\Support\Branch\CurrentBranch;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('driver.assign') || $user->can('booking.manage');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $this->viewAny($user) && $vehicle->branch_id === CurrentBranch::id();
    }

    public function create(User $user): bool
    {
        return $user->can('driver.assign');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->can('driver.assign') && $vehicle->branch_id === CurrentBranch::id();
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->can('driver.assign') && $vehicle->branch_id === CurrentBranch::id();
    }
}
