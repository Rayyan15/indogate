<?php

namespace Tests\Concerns;

use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Models\Vehicle;
use App\Models\Branch;

trait BuildsFleetFixtures
{
    private function createDriver(Branch $branch, array $attributes = []): Driver
    {
        return Driver::withoutGlobalScopes()->create(array_merge([
            'branch_id' => $branch->id,
            'name' => 'Wayan Driver',
            'gender' => 'male',
            'phone' => '08123456789',
            'languages' => ['id', 'en'],
            'is_active' => true,
        ], $attributes));
    }

    private function createVehicle(Branch $branch, array $attributes = []): Vehicle
    {
        return Vehicle::withoutGlobalScopes()->create(array_merge([
            'branch_id' => $branch->id,
            'plate' => 'DK 1234 AB',
            'type' => 'Toyota Avanza',
            'capacity' => 6,
            'is_active' => true,
        ], $attributes));
    }
}
