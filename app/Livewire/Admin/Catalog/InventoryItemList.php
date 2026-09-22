<?php

namespace App\Livewire\Admin\Catalog;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Livewire\Concerns\Sortable;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryItemList extends Component
{
    use Sortable, WithPagination;

    /** Name is a translatable JSON column — deliberately not sortable, see Sortable trait docblock. */
    private const SORTABLE_FIELDS = ['type', 'is_active', 'created_at'];

    public string $search = '';

    public string $partnerFilter = '';

    public string $typeFilter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', InventoryItem::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPartnerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $items = $this->applySort(
            InventoryItem::query()
                ->with('partner')
                ->withCount('rates')
                ->when($this->search, function ($q) {
                    $escaped = addcslashes($this->search, '%_\\');
                    $q->where(function ($sub) use ($escaped) {
                        $sub->where('name->id', 'like', "%{$escaped}%")
                            ->orWhere('name->en', 'like', "%{$escaped}%")
                            ->orWhere('name->ar', 'like', "%{$escaped}%");
                    });
                })
                ->when($this->partnerFilter, fn ($q) => $q->where('partner_id', $this->partnerFilter))
                ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter)),
            self::SORTABLE_FIELDS,
            defaultField: 'created_at',
        )->paginate(10);

        return view('livewire.admin.catalog.inventory-item-list', [
            'items' => $items,
            'partners' => Partner::orderBy('id')->get(),
        ]);
    }
}
