<?php

namespace Tests\Feature;

use App\Rules\NoConflictingRolePermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SegregationOfDutiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_cs_and_finance_roles_never_share_the_conflicting_permissions(): void
    {
        $this->seed();

        $cs = Role::where('name', 'CS Admin')->firstOrFail();
        $finance = Role::where('name', 'Finance Admin')->firstOrFail();

        $this->assertTrue($cs->hasPermissionTo('booking.manage'));
        $this->assertFalse($cs->hasPermissionTo('payment.verify'));
        $this->assertTrue($finance->hasPermissionTo('payment.verify'));
        $this->assertFalse($finance->hasPermissionTo('booking.manage'));
    }

    public function test_role_holding_both_conflicting_permissions_is_rejected_by_validation_rule(): void
    {
        $this->seed();

        $conflict = Role::create(['name' => 'Conflicted']);
        $conflict->syncPermissions(['booking.manage', 'payment.verify']);

        $validator = Validator::make(
            ['role' => 'Conflicted'],
            ['role' => [new NoConflictingRolePermissions]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_super_admin_role_is_exempt_from_the_conflict_check(): void
    {
        $this->seed();

        $validator = Validator::make(
            ['role' => 'Super Admin'],
            ['role' => [new NoConflictingRolePermissions]]
        );

        $this->assertFalse($validator->fails());
    }
}
