<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement\RoleMatrix;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function superAdmin(): User
    {
        return User::role('Super Admin')->firstOrFail();
    }

    private function pid(string $name): int
    {
        return Permission::where('name', $name)->value('id');
    }

    public function test_route_and_component_require_user_manage(): void
    {
        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $this->actingAs($cs)->get(route('admin.roles.index'))->assertForbidden();
        Livewire::actingAs($cs)->test(RoleMatrix::class)->assertForbidden();

        $this->actingAs($this->superAdmin())->get(route('admin.roles.index'))->assertOk();
    }

    public function test_toggling_a_permission_saves_it(): void
    {
        $cs = Role::findByName('CS Admin');

        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->set("grants.{$cs->id}.{$this->pid('driver.assign')}", true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($cs->fresh()->hasPermissionTo('driver.assign'));
    }

    public function test_segregation_of_duties_conflict_is_rejected(): void
    {
        $cs = Role::findByName('CS Admin');

        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->set("grants.{$cs->id}.{$this->pid('payment.verify')}", true)
            ->call('save')
            ->assertHasErrors('grants');

        $this->assertFalse($cs->fresh()->hasPermissionTo('payment.verify'));
    }

    public function test_super_admin_role_is_never_changed(): void
    {
        $sa = Role::findByName('Super Admin');
        $before = $sa->permissions()->count();

        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->set("grants.{$sa->id}.{$this->pid('user.manage')}", false)
            ->call('save');

        $this->assertSame($before, $sa->fresh()->permissions()->count());
        $this->assertTrue($sa->fresh()->hasPermissionTo('user.manage'));
    }

    public function test_create_role_and_its_user_can_reach_admin_panel(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->set('newRole', 'Sales')
            ->call('createRole')
            ->assertHasNoErrors();

        $role = Role::findByName('Sales');
        $this->assertSame(0, $role->permissions()->count());

        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->set("grants.{$role->id}.{$this->pid('lead.manage')}", true)
            ->call('save');

        $user = User::factory()->create(['branch_id' => Branch::first()->id, 'is_active' => true]);
        $user->assignRole('Sales');

        $this->actingAs($user)->get(route('admin.leads.index', ['locale' => 'id']))->assertOk();
    }

    public function test_reserved_role_names_are_rejected(): void
    {
        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->set('newRole', 'Customer')
            ->call('createRole')
            ->assertHasErrors('newRole');
    }

    public function test_role_in_use_or_builtin_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Sales', 'guard_name' => 'web']);
        User::factory()->create(['branch_id' => Branch::first()->id])->assignRole('Sales');

        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->call('deleteRole', $role->id)
            ->assertHasErrors('delete')
            ->call('deleteRole', Role::findByName('CS Admin')->id)
            ->assertHasErrors('delete');

        $this->assertNotNull(Role::where('name', 'Sales')->first());
        $this->assertNotNull(Role::where('name', 'CS Admin')->first());
    }

    public function test_unused_custom_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'Temp', 'guard_name' => 'web']);

        Livewire::actingAs($this->superAdmin())
            ->test(RoleMatrix::class)
            ->call('deleteRole', $role->id)
            ->assertHasNoErrors();

        $this->assertNull(Role::where('name', 'Temp')->first());
    }
}
