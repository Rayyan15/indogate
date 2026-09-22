<?php

namespace App\Livewire\Admin\Packaging;

use App\Domain\Packaging\Models\Package;
use App\Livewire\Concerns\Sortable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PackageList extends Component
{
    use Sortable, WithPagination;

    /** Name is a translatable JSON column — deliberately not sortable, see Sortable trait docblock. */
    private const SORTABLE_FIELDS = ['base_pax', 'duration_days', 'created_at'];

    public string $search = '';

    /**
     * SSR default only, for the very first paint before Alpine hydrates
     * and reads the real preference from localStorage. Switching view is
     * a pure client-side x-show toggle (see the blade view) — no
     * server round-trip, no session write, so it's instant and never
     * blocks on a Livewire request.
     */
    public string $viewMode = 'list';

    public function mount(): void
    {
        $this->authorize('viewAny', Package::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('package-saved')]
    public function refresh(): void {}

    public function duplicate(int $packageId): void
    {
        $package = Package::findOrFail($packageId);
        $this->authorize('view', $package);
        $this->authorize('viewAny', Package::class);

        $package->duplicate();
    }

    public function render(): View
    {
        $packages = $this->applySort(
            Package::query()
                ->withCount('items')
                ->with(['items.inventoryItem:id,name,type'])
                ->when($this->search, function ($q) {
                    $escaped = addcslashes($this->search, '%_\\');
                    $q->where(function ($sub) use ($escaped) {
                        $sub->where('name->id', 'like', "%{$escaped}%")
                            ->orWhere('name->en', 'like', "%{$escaped}%")
                            ->orWhere('name->ar', 'like', "%{$escaped}%");
                    });
                }),
            self::SORTABLE_FIELDS,
            defaultField: 'created_at',
        )->paginate(9);

        return view('livewire.admin.packaging.package-list', ['packages' => $packages]);
    }
}
