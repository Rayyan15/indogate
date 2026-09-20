<?php

namespace App\Livewire\Admin\Fleet;

use App\Domain\Fleet\Models\Vehicle;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class VehicleList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showModal = false;

    public ?int $editingVehicleId = null;

    public string $plate = '';

    public string $type = '';

    public int $capacity = 6;

    public bool $is_active = true;

    protected $queryString = [
        'search' => ['except' => ''],
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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['editingVehicleId', 'plate', 'type', 'capacity', 'is_active']);
        $this->capacity = 6;
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->authorize('update', $vehicle);

        $this->editingVehicleId = $vehicle->id;
        $this->plate = $vehicle->plate;
        $this->type = $vehicle->type;
        $this->capacity = $vehicle->capacity;
        $this->is_active = (bool) $vehicle->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'plate' => ['required', 'string', 'max:50'],
            'type' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1', 'max:60'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editingVehicleId) {
            $vehicle = Vehicle::findOrFail($this->editingVehicleId);
            $this->authorize('update', $vehicle);

            $vehicle->update([
                'plate' => $this->plate,
                'type' => $this->type,
                'capacity' => $this->capacity,
                'is_active' => $this->is_active,
            ]);
        } else {
            Vehicle::create([
                'branch_id' => CurrentBranch::id(),
                'plate' => $this->plate,
                'type' => $this->type,
                'capacity' => $this->capacity,
                'is_active' => $this->is_active,
            ]);
        }

        $this->showModal = false;
        $this->reset(['editingVehicleId', 'plate', 'type', 'capacity', 'is_active']);
    }

    public function deleteVehicle(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->authorize('delete', $vehicle);

        $vehicle->delete();
    }

    public function toggleStatus(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->authorize('update', $vehicle);

        $vehicle->update(['is_active' => ! $vehicle->is_active]);
    }

    public function render(): View
    {
        $query = Vehicle::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('plate', 'like', "%{$this->search}%")
                        ->orWhere('type', 'like', "%{$this->search}%")
                        ->orWhere('plate_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                $isActive = $this->statusFilter === 'active';
                $q->where('is_active', $isActive);
            })
            ->orderBy('plate');

        return view('livewire.admin.fleet.vehicle-list', [
            'vehicles' => $query->paginate(15),
        ]);
    }
}
