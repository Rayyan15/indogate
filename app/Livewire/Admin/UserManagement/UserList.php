<?php

namespace App\Livewire\Admin\UserManagement;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * UserForm (child) dispatches 'user-saved' after create/update. Without
     * a listener here, this sibling component never re-renders and the
     * table silently goes stale — Livewire does not auto-refresh siblings
     * on an unrelated component's request.
     */
    #[On('user-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        $users = User::query()
            ->with(['branch', 'roles'])
            ->when($this->search, fn ($query) => $query
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.user-management.user-list', ['users' => $users]);
    }
}
