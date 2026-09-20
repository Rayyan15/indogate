<?php

namespace App\Livewire\Admin\UserManagement;

use App\Models\Branch;
use App\Models\User;
use App\Rules\NoConflictingRolePermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public ?int $branch_id = null;

    public ?string $role = null;

    public bool $is_active = true;

    #[On('create-user')]
    public function openForCreate(): void
    {
        $this->authorize('viewAny', User::class);
        $this->reset(['userId', 'name', 'email', 'password', 'branch_id', 'role']);
        $this->is_active = true;
        $this->dispatch('open-modal', 'user-form');
    }

    #[On('edit-user')]
    public function openForEdit(int $userId): void
    {
        $user = User::with('roles')->findOrFail($userId);
        $this->authorize('update', $user);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->branch_id = $user->branch_id;
        $this->role = $user->roles->first()?->name;
        $this->is_active = $user->is_active;

        $this->dispatch('open-modal', 'user-form');
    }

    public function save(): void
    {
        $editingSelf = $this->userId === auth()->id();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$this->userId],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8'],
            'branch_id' => ['required', 'exists:branches,id'],
            'role' => ['required', 'exists:roles,name', new NoConflictingRolePermissions],
            'is_active' => ['boolean'],
        ]);

        if ($editingSelf && ! $validated['is_active']) {
            $this->addError('is_active', 'Tidak bisa menonaktifkan akun sendiri.');

            return;
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);
            $this->authorize('update', $user);
        } else {
            $this->authorize('viewAny', User::class);
            $user = new User;
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'branch_id' => $validated['branch_id'],
            'is_active' => $validated['is_active'],
        ]);

        if ($validated['password']) {
            $user->password = Hash::make($validated['password']);
        }

        $isUpdate = (bool) $this->userId;
        $user->save();
        $user->syncRoles([$validated['role']]);

        activity('security')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'role' => $validated['role'],
                'branch_id' => $validated['branch_id'],
                'is_active' => $validated['is_active'],
            ])
            ->log($isUpdate ? 'User updated' : 'User created');

        $this->dispatch('user-saved');
        $this->dispatch('close-modal', 'user-form');
    }

    public function render(): View
    {
        return view('livewire.admin.user-management.user-form', [
            'branches' => Branch::orderBy('name')->get(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }
}
