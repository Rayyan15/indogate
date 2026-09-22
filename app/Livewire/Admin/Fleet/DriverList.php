<?php

namespace App\Livewire\Admin\Fleet;

use App\Domain\Fleet\Models\Driver;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DriverList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $genderFilter = '';

    public string $statusFilter = '';

    public bool $showModal = false;

    public ?int $editingDriverId = null;

    public string $name = '';

    public string $gender = 'male';

    public string $phone = '';

    public array $languages = ['id'];

    public bool $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
        'genderFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()->can('driver.assign'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGenderFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingDriverId', 'name', 'gender', 'phone', 'languages', 'is_active']);
        $this->gender = 'male';
        $this->languages = ['id'];
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('update', $driver);

        $this->editingDriverId = $driver->id;
        $this->name = $driver->name;
        $this->gender = $driver->gender;
        $this->phone = $driver->phone ?? '';
        $this->languages = is_array($driver->languages) ? $driver->languages : ['id'];
        $this->is_active = (bool) $driver->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'in:male,female'],
            'phone' => ['nullable', 'string', 'max:30'],
            'languages' => ['array'],
            'languages.*' => ['string', 'max:10'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editingDriverId) {
            $driver = Driver::findOrFail($this->editingDriverId);
            $this->authorize('update', $driver);

            $driver->update([
                'name' => $this->name,
                'gender' => $this->gender,
                'phone' => $this->phone ?: null,
                'languages' => $this->languages,
                'is_active' => $this->is_active,
            ]);
        } else {
            $this->authorize('create', Driver::class);
            Driver::create([
                'branch_id' => CurrentBranch::id(),
                'name' => $this->name,
                'gender' => $this->gender,
                'phone' => $this->phone ?: null,
                'languages' => $this->languages,
                'is_active' => $this->is_active,
            ]);
        }

        $this->showModal = false;
        $this->reset(['editingDriverId', 'name', 'gender', 'phone', 'languages', 'is_active']);
    }

    public function deleteDriver(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('delete', $driver);

        $driver->delete();
    }

    public function toggleStatus(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $this->authorize('update', $driver);

        $driver->update(['is_active' => ! $driver->is_active]);
    }

    public function render(): View
    {
        $query = Driver::query()
            ->when($this->search !== '', function ($q) {
                $escaped = addcslashes($this->search, '%_\\');
                $q->where(function ($sq) use ($escaped) {
                    $sq->where('name', 'like', "%{$escaped}%")
                        ->orWhere('phone', 'like', "%{$escaped}%");
                });
            })
            ->when($this->genderFilter !== '', fn ($q) => $q->where('gender', $this->genderFilter))
            ->when($this->statusFilter !== '', function ($q) {
                $isActive = $this->statusFilter === 'active';
                $q->where('is_active', $isActive);
            })
            ->orderBy('name');

        return view('livewire.admin.fleet.driver-list', [
            'drivers' => $query->paginate(15),
        ]);
    }
}
