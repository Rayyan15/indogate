<?php

namespace App\Livewire\Admin\Catalog;

use App\Domain\Catalog\Models\Partner;
use App\Livewire\Concerns\Sortable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PartnerList extends Component
{
    use Sortable, WithPagination;

    /** Name is a translatable JSON column — deliberately not sortable, see Sortable trait docblock. */
    private const SORTABLE_FIELDS = ['type', 'city', 'created_at'];

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
        $partners = $this->applySort(
            Partner::query()
                ->withCount('inventoryItems')
                ->when($this->search, function ($q) {
                    $escaped = addcslashes($this->search, '%_\\');
                    $q->where(function ($sub) use ($escaped) {
                        $sub->where('name->id', 'like', "%{$escaped}%")
                            ->orWhere('name->en', 'like', "%{$escaped}%")
                            ->orWhere('name->ar', 'like', "%{$escaped}%");
                    });
                })
                ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter)),
            self::SORTABLE_FIELDS,
            defaultField: 'created_at',
        )->paginate(10);

        return view('livewire.admin.catalog.partner-list', ['partners' => $partners]);
    }
}
