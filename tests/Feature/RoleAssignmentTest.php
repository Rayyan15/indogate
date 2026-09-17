<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement\UserForm;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_saved_when_no_conflict(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('openForCreate')
            ->set('name', 'New Sales')
            ->set('email', 'sales@indogate.com')
            ->set('password', 'password123')
            ->set('branch_id', $bali->id)
            ->set('role', 'CS Admin')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(User::where('email', 'sales@indogate.com')->firstOrFail()->hasRole('CS Admin'));
    }

    public function test_conflicting_dual_role_permissions_are_rejected(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        // Give one custom role both conflicting permissions directly to
        // simulate the forbidden combination, then try to assign it.
        $conflict = Role::create(['name' => 'Conflicted']);
        $conflict->syncPermissions(['booking.manage', 'payment.verify']);

        $bali = Branch::where('code', 'BALI')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('openForCreate')
            ->set('name', 'Bad Combo')
            ->set('email', 'badcombo@indogate.com')
            ->set('password', 'password123')
            ->set('branch_id', $bali->id)
            ->set('role', 'Conflicted')
            ->call('save')
            ->assertHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'badcombo@indogate.com']);
    }
}
