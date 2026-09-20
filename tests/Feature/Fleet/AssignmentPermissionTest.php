<?php

namespace Tests\Feature\Fleet;

use App\Domain\Fleet\Models\DriverAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AssignmentPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_admin_cannot_assign_drivers(): void
    {
        $this->seed();
        $finance = User::where('email', 'finance.bali@indogate.com')->firstOrFail();

        $this->assertFalse(Gate::forUser($finance)->allows('create', DriverAssignment::class));
        $this->assertFalse(Gate::forUser($finance)->allows('assign', DriverAssignment::class));
    }

    public function test_cs_admin_can_assign_drivers(): void
    {
        $this->seed();
        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($cs)->allows('create', DriverAssignment::class));
        $this->assertTrue(Gate::forUser($cs)->allows('assign', DriverAssignment::class));
    }

    public function test_super_admin_can_assign_drivers(): void
    {
        $this->seed();
        $superAdmin = User::where('email', 'admin@indogate.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('create', DriverAssignment::class));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('assign', DriverAssignment::class));
    }
}
