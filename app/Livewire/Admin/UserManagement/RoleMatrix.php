<?php

namespace App\Livewire\Admin\UserManagement;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleMatrix extends Component
{
    public const SUPER_ADMIN = 'Super Admin';

    public const PROTECTED_ROLES = ['Super Admin', 'CS Admin', 'Finance Admin', 'Customer'];

    private const CONFLICTING = ['booking.manage', 'payment.verify'];

    /** @var array<int, array<int, bool>> role id => permission id => granted */
    public array $grants = [];

    public string $newRole = '';

    public function mount(): void
    {
        $this->authorizeManage();
        $this->loadGrants();
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('user.manage'), 403);
    }

    private function staffRoles()
    {
        return Role::where('name', '!=', 'Customer')->with('permissions')->withCount('users')->orderBy('id')->get();
    }

    private function loadGrants(): void
    {
        $permissionIds = Permission::pluck('id');
        $this->grants = [];

        foreach ($this->staffRoles() as $role) {
            $held = $role->permissions->pluck('id')->all();
            foreach ($permissionIds as $pid) {
                $this->grants[$role->id][$pid] = in_array($pid, $held, true);
            }
        }
    }

    public function save(): void
    {
        $this->authorizeManage();
        $this->resetErrorBag();

        $permissions = Permission::pluck('name', 'id');
        $roles = $this->staffRoles()->where('name', '!=', self::SUPER_ADMIN);
        $plan = [];

        foreach ($roles as $role) {
            $names = collect($this->grants[$role->id] ?? [])
                ->filter()
                ->keys()
                ->map(fn ($pid) => $permissions[$pid] ?? null)
                ->filter()
                ->values()
                ->all();

            if (count(array_intersect(self::CONFLICTING, $names)) === count(self::CONFLICTING)) {
                $this->addError('grants', __('roles.sod_conflict', ['role' => $role->name]));

                return;
            }

            $plan[] = [$role, $names];
        }

        foreach ($plan as [$role, $names]) {
            $before = $role->permissions->pluck('name')->sort()->values()->all();
            $after = collect($names)->sort()->values()->all();

            if ($before === $after) {
                continue;
            }

            $role->syncPermissions($names);

            activity('security')
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->withProperties(['old' => $before, 'new' => $after])
                ->log('Role permissions updated');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->loadGrants();
        session()->flash('success', __('roles.saved'));
    }

    public function createRole(): void
    {
        $this->authorizeManage();

        $validated = $this->validate([
            'newRole' => ['required', 'string', 'max:50', Rule::unique('roles', 'name'), Rule::notIn(['Customer', self::SUPER_ADMIN])],
        ]);

        $role = Role::create(['name' => trim($validated['newRole']), 'guard_name' => 'web']);

        activity('security')->causedBy(auth()->user())->performedOn($role)->log('Role created');

        $this->newRole = '';
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->loadGrants();
    }

    public function deleteRole(int $roleId): void
    {
        $this->authorizeManage();

        $role = Role::withCount('users')->findOrFail($roleId);

        if (in_array($role->name, self::PROTECTED_ROLES, true)) {
            $this->addError('delete', __('roles.cannot_delete_builtin'));

            return;
        }

        if ($role->users_count > 0) {
            $this->addError('delete', __('roles.cannot_delete_in_use', ['count' => $role->users_count]));

            return;
        }

        activity('security')->causedBy(auth()->user())->withProperties(['role' => $role->name])->log('Role deleted');

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->loadGrants();
    }

    public function render(): View
    {
        $groups = Permission::orderBy('id')->get()
            ->groupBy(fn ($p) => explode('.', $p->name)[0]);

        return view('livewire.admin.user-management.role-matrix', [
            'roles' => $this->staffRoles(),
            'groups' => $groups,
            'protected' => self::PROTECTED_ROLES,
        ]);
    }
}
