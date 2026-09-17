<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement\UserList;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_user_manage_holders_can_open_the_user_list_route(): void
    {
        $this->seed();

        $superAdmin = User::role('Super Admin')->firstOrFail();
        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertOk();

        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $this->actingAs($cs)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_only_user_manage_holders_can_mount_the_livewire_component(): void
    {
        $this->seed();

        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();
        $this->actingAs($cs);

        try {
            (new UserList)->mount();
            $this->fail('Expected an AuthorizationException.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    /**
     * Regression: UserList must re-query on 'user-saved' (dispatched by
     * the sibling UserForm after create/update). Found in manual browser
     * testing — without an #[On('user-saved')] listener, Livewire never
     * re-renders this component and the table silently goes stale even
     * though the record was actually created.
     */
    public function test_user_list_refreshes_after_a_user_is_saved_elsewhere(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();

        $component = Livewire::actingAs($superAdmin)->test(UserList::class);
        $component->assertDontSee('freshly-created@indogate.com');

        User::create([
            'name' => 'Freshly Created',
            'email' => 'freshly-created@indogate.com',
            'password' => bcrypt('password'),
            'branch_id' => $bali->id,
            'is_active' => true,
        ]);

        $component->dispatch('user-saved')->assertSee('freshly-created@indogate.com');
    }
}
