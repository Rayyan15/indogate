<?php

namespace App\Livewire\Public;

use App\Domain\Packaging\Models\Package;
use App\Models\Branch;
use App\Support\Storefront\StorefrontCurrency;
use Livewire\Component;
use Livewire\WithPagination;

class PackageCatalog extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $branchId = null;

    public string $duration = '';

    public string $sort = 'latest';

    public string $currency = 'SAR';

    protected $queryString = [
        'search' => ['except' => ''],
        'branchId' => ['except' => null, 'as' => 'branch'],
        'duration' => ['except' => ''],
        'sort' => ['except' => 'latest'],
    ];

    public function mount(): void
    {
        $this->currency = StorefrontCurrency::current();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingBranchId(): void
    {
        $this->resetPage();
    }

    public function updatingDuration(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function setCurrency(string $currency): void
    {
        if (in_array($currency, StorefrontCurrency::SUPPORTED_CURRENCIES, true)) {
            StorefrontCurrency::set($currency);
            $this->currency = $currency;
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'branchId', 'duration', 'sort']);
        $this->resetPage();
    }

    public function render()
    {
        $query = Package::published()->with(['branch']);

        // Search filter across locale name and description
        if (! empty(trim($this->search))) {
            $keyword = '%'.trim($this->search).'%';
            $locale = app()->getLocale();
            $query->where(function ($q) use ($keyword, $locale) {
                $q->where("name->{$locale}", 'like', $keyword)
                    ->orWhere("description->{$locale}", 'like', $keyword)
                    ->orWhere('name', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword);
            });
        }

        // Branch / Destination filter
        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        // Duration filter
        if ($this->duration === '1-3') {
            $query->whereBetween('duration_days', [1, 3]);
        } elseif ($this->duration === '4-7') {
            $query->whereBetween('duration_days', [4, 7]);
        } elseif ($this->duration === '8+') {
            $query->where('duration_days', '>=', 8);
        }

        // Sorting
        match ($this->sort) {
            'price_asc' => $query->orderBy('starting_price_idr', 'asc'),
            'price_desc' => $query->orderBy('starting_price_idr', 'desc'),
            'duration_asc' => $query->orderBy('duration_days', 'asc'),
            default => $query->latest('id'),
        };

        $packages = $query->paginate(9);
        $branches = Branch::where('is_active', true)->get();

        return view('livewire.public.package-catalog', [
            'packages' => $packages,
            'branches' => $branches,
        ]);
    }
}
