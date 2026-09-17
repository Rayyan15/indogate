<?php

namespace App\Livewire\Admin\Catalog;

use App\Domain\Catalog\Models\Partner;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Partner::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    #[On('partner-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        $partners = Partner::query()
            ->withCount('inventoryItems')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.admin.catalog.partner-list', ['partners' => $partners]);
    }
}
