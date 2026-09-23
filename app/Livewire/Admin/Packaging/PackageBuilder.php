<?php

namespace App\Livewire\Admin\Packaging;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Packaging\Models\Package;
use App\Domain\Packaging\Models\PackageItem;
use App\Domain\Packaging\PackageCalculationResult;
use App\Domain\Packaging\PackageCalculator;
use App\Domain\Packaging\PackageItemResult;
use App\Domain\Pricing\PricingBreakdown;
use App\Enums\PaymentChannel;
use App\Jobs\GeneratePackageItineraryPdf;
use App\Support\Branch\CurrentBranch;
use App\Support\Localization\Money as DisplayMoney;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * PRD M5: builder must feel fast — Alpine owns add/remove/reorder of
 * $items client-side, the server is only called on recalculate()/save().
 * Margin/cost are never assigned to $summary for a user without
 * pricing.manage — see buildSummaryArray(). This is the same technique as
 * App\Livewire\Admin\Pricing\PricingSimulator: never hold a domain value
 * object as a public property, hand-pick which keys exist.
 */
class PackageBuilder extends Component
{
    use WithFileUploads;

    public ?int $packageId = null;

    public array $name = ['en' => '', 'id' => '', 'ar' => ''];

    public array $description = ['en' => '', 'id' => '', 'ar' => ''];

    public bool $is_template = false;

    // New packages start as drafts; publishing to the storefront is explicit (bug-review H-01).
    public bool $is_published = false;

    public int $base_pax = 2;

    public ?int $duration_days = null;

    public string $preview_date;

    public int $current_pax = 2;

    public string $channel = PaymentChannel::BANK_TRANSFER->value;

    public string $display_currency = 'IDR';

    /** @var array<int, array{id: int|string, inventory_item_id: int, name: string, day_from: int, day_to: int, qty: int, nights: ?int, sort_order: int}> */
    public array $items = [];

    public ?array $summary = null;

    public ?string $durationWarning = null;

    public string $componentSearch = '';

    public bool $exportPending = false;

    public ?string $downloadUrl = null;

    public ?string $brochure_path = null;

    /** @var TemporaryUploadedFile|null */
    public $brochure = null;

    public function mount(?Package $package = null): void
    {
        $this->authorize('viewAny', Package::class);
        $this->preview_date = now()->addMonth()->format('Y-m-d');

        if ($package && $package->exists) {
            $this->authorize('update', $package);
            $this->packageId = $package->id;
            $this->name = ['en' => $package->getTranslation('name', 'en', false) ?? '', 'id' => $package->getTranslation('name', 'id', false) ?? '', 'ar' => $package->getTranslation('name', 'ar', false) ?? ''];
            $this->description = ['en' => $package->getTranslation('description', 'en', false) ?? '', 'id' => $package->getTranslation('description', 'id', false) ?? '', 'ar' => $package->getTranslation('description', 'ar', false) ?? ''];
            $this->is_template = $package->is_template;
            $this->is_published = (bool) $package->is_published;
            $this->base_pax = $package->base_pax;
            $this->duration_days = $package->duration_days;
            $this->current_pax = $package->base_pax;
            $this->brochure_path = $package->brochure_path;

            $this->items = $package->items->map(fn ($item) => [
                'id' => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'name' => $item->inventoryItem->getTranslation('name', app()->getLocale(), false) ?? '',
                'type' => $item->inventoryItem->type->value ?? 'other',
                'partner_name' => $item->inventoryItem->partner?->name ?? '',
                'day_from' => $item->day_from,
                'day_to' => $item->day_to,
                'qty' => $item->qty,
                'nights' => $item->nights,
                'sort_order' => $item->sort_order,
            ])->all();
        }
    }

    #[Computed]
    public function componentResults()
    {
        if ($this->componentSearch === '') {
            return collect();
        }

        return InventoryItem::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $escaped = addcslashes($this->componentSearch, '%_\\');
                $q->where('name->id', 'like', "%{$escaped}%")
                    ->orWhere('name->en', 'like', "%{$escaped}%")
                    ->orWhere('name->ar', 'like', "%{$escaped}%");
            })
            ->with('partner')
            ->limit(10)
            ->get(['id', 'name', 'type', 'partner_id']);
    }

    public function recalculate(array $items, int $pax): void
    {
        $this->authorize($this->packageId ? 'update' : 'viewAny', $this->packageOrClass());

        $this->current_pax = $pax;
        $this->items = $this->normalizeItems($items);
        $this->durationWarning = $this->validateDuration();

        $package = $this->buildTransientPackage();

        $result = app(PackageCalculator::class)->calculate(
            $package,
            $pax,
            new DateTimeImmutable($this->preview_date),
            PaymentChannel::from($this->channel),
            strtoupper($this->display_currency),
        );

        $this->summary = $this->buildSummaryArray($result->itemResults, $result);
    }

    public function updatedCurrentPax(): void
    {
        if ($this->items !== []) {
            $this->recalculate($this->items, $this->current_pax);
        }
    }

    public function updatedDisplayCurrency(): void
    {
        if ($this->items !== []) {
            $this->recalculate($this->items, $this->current_pax);
        }
    }

    public function updatedChannel(): void
    {
        if ($this->items !== []) {
            $this->recalculate($this->items, $this->current_pax);
        }
    }

    public function updatedPreviewDate(): void
    {
        if ($this->items !== []) {
            $this->recalculate($this->items, $this->current_pax);
        }
    }

    public function save(): void
    {
        $branchId = CurrentBranch::id();
        $isNewPackage = ! $this->packageId;

        $validated = $this->validate([
            'name.en' => ['required', 'string', 'max:255'],
            'name.id' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'base_pax' => ['required', 'integer', 'min:1'],
            'duration_days' => ['nullable', 'integer', 'min:1'],
            'is_template' => ['boolean'],
            'is_published' => ['boolean'],
            // Items come from the client: same-branch inventory only, sane numbers (H-04).
            'items' => ['array'],
            'items.*.inventory_item_id' => ['required', 'integer', Rule::exists('inventory_items', 'id')->where('branch_id', $branchId)],
            'items.*.day_from' => ['required', 'integer', 'min:0'],
            'items.*.day_to' => ['required', 'integer', 'gte:items.*.day_from'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.nights' => ['nullable', 'integer', 'min:0'],
            'items.*.sort_order' => ['nullable', 'integer'],
        ]);

        if ($this->packageId) {
            $package = Package::findOrFail($this->packageId);
            $this->authorize('update', $package);
        } else {
            $this->authorize('viewAny', Package::class);
            $package = new Package(['branch_id' => $branchId]);
        }

        $package->fill([
            'base_pax' => $validated['base_pax'],
            'duration_days' => $validated['duration_days'],
            'is_template' => $validated['is_template'] ?? false,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        foreach ($this->name as $locale => $value) {
            $package->setTranslation('name', $locale, $value);
        }
        foreach ($this->description as $locale => $value) {
            $package->setTranslation('description', $locale, $value ?? '');
        }

        // One transaction: a failing row must not leave the package without items (H-05).
        DB::transaction(function () use ($package) {
            $package->save();

            $package->items()->delete();
            foreach ($this->items as $row) {
                $package->items()->create([
                    'inventory_item_id' => $row['inventory_item_id'],
                    'day_from' => $row['day_from'],
                    'day_to' => $row['day_to'],
                    'qty' => $row['qty'],
                    'nights' => $row['nights'],
                    'sort_order' => $row['sort_order'],
                ]);
            }
        });
        $this->packageId = $package->id;

        $this->dispatch('package-saved');

        /**
         * Redirect to the edit route on first save rather than staying on
         * the create page. Livewire's morph preserves the Alpine x-data
         * instance across a same-page re-render, but the item rows carry
         * temporary negative ids assigned client-side before save() — a
         * fresh navigation is simpler and more reliable than trying to
         * reconcile those ids with the real ones save() just created.
         */
        if ($isNewPackage) {
            $this->redirect(route('admin.packages.edit', ['locale' => app()->getLocale(), 'package' => $package]));
        }
    }

    public function uploadBrochure(): void
    {
        abort_unless($this->packageId, 422, 'Save the package before uploading a brochure.');
        $package = Package::findOrFail($this->packageId);
        $this->authorize('update', $package);

        $this->validate([
            'brochure' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        if ($package->brochure_path) {
            Storage::disk('public')->delete($package->brochure_path);
        }

        $filename = 'brochure-'.now()->timestamp.'.'.$this->brochure->extension();
        $path = $this->brochure->storeAs("packages/{$package->id}", $filename, 'public');

        $package->update(['brochure_path' => $path]);
        $this->brochure_path = $path;
        $this->reset('brochure');
    }

    public function removeBrochure(): void
    {
        abort_unless($this->packageId, 422);
        $package = Package::findOrFail($this->packageId);
        $this->authorize('update', $package);

        if ($package->brochure_path) {
            Storage::disk('public')->delete($package->brochure_path);
            $package->update(['brochure_path' => null]);
        }

        $this->brochure_path = null;
    }

    public function exportPdf(string $locale): void
    {
        abort_unless($this->packageId, 422, 'Save the package before exporting.');
        $this->authorize('view', Package::findOrFail($this->packageId));

        GeneratePackageItineraryPdf::dispatch($this->packageId, $locale, Auth::id());
        $this->exportPending = true;
        $this->downloadUrl = null;
    }

    public function checkExportReady(): void
    {
        if (! $this->exportPending || ! $this->packageId) {
            return;
        }

        foreach (['en', 'id', 'ar'] as $locale) {
            $path = Cache::get(GeneratePackageItineraryPdf::cacheKey($this->packageId, Auth::id(), $locale));
            if ($path) {
                $this->exportPending = false;
                $this->downloadUrl = URL::temporarySignedRoute('admin.packages.export-download', now()->addMinutes(30), [
                    'locale' => app()->getLocale(),
                    'package' => $this->packageId,
                    'path' => basename($path),
                ]);
                $this->dispatch('export-ready');

                return;
            }
        }
    }

    private function packageOrClass(): Package|string
    {
        return $this->packageId ? Package::findOrFail($this->packageId) : Package::class;
    }

    private function buildTransientPackage(): Package
    {
        $package = new Package([
            'branch_id' => CurrentBranch::id(),
            'base_pax' => $this->base_pax,
            'duration_days' => $this->duration_days,
        ]);
        $package->id = $this->packageId ?? 0;
        $package->setRelation('items', collect($this->items)->map(function ($row, $index) {
            $item = new PackageItem([
                'inventory_item_id' => $row['inventory_item_id'],
                'day_from' => $row['day_from'],
                'day_to' => $row['day_to'],
                'qty' => $row['qty'],
                'nights' => $row['nights'],
                'sort_order' => $row['sort_order'] ?? $index,
            ]);
            $item->id = is_int($row['id']) ? $row['id'] : 0;
            $item->setRelation('inventoryItem', InventoryItem::findOrFail($row['inventory_item_id']));

            return $item;
        }));

        return $package;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items): array
    {
        return collect($items)->values()->map(fn ($row, $index) => [
            'id' => $row['id'],
            'inventory_item_id' => (int) $row['inventory_item_id'],
            'name' => $row['name'] ?? '',
            'type' => $row['type'] ?? null,
            'partner_name' => $row['partner_name'] ?? null,
            'day_from' => (int) $row['day_from'],
            'day_to' => (int) $row['day_to'],
            'qty' => max(1, (int) $row['qty']),
            'nights' => isset($row['nights']) && $row['nights'] !== '' && $row['nights'] !== null ? (int) $row['nights'] : null,
            'sort_order' => $index,
        ])->all();
    }

    private function validateDuration(): ?string
    {
        if (! $this->duration_days) {
            return null;
        }

        $maxDayTo = collect($this->items)->max('day_to') ?? 0;

        if ($maxDayTo >= $this->duration_days) {
            return __('packaging.builder.duration_warning');
        }

        return null;
    }

    /**
     * PRD M5 payload-leak warning: cost/margin keys are OMITTED entirely
     * (never null) from the arrays below when the user lacks
     * pricing.manage — this happens before anything is assigned to
     * $this->summary, which is the only thing Livewire ships to the
     * browser.
     */
    private function buildSummaryArray(array $itemResults, PackageCalculationResult $result): array
    {
        $canSeeMargin = Auth::user()->can('pricing.manage');

        $names = InventoryItem::whereIn('id', array_map(fn (PackageItemResult $r) => $r->inventoryItemId, $itemResults))
            ->get()->mapWithKeys(fn (InventoryItem $i) => [$i->id => $i->name]);

        $items = array_map(function (PackageItemResult $r) use ($canSeeMargin, $names) {
            $row = [
                'package_item_id' => $r->packageItemId,
                'inventory_item_id' => $r->inventoryItemId,
                'name' => $names[$r->inventoryItemId] ?? '#'.$r->inventoryItemId,
                'effective_qty' => $r->effectiveQty,
                'rate_missing' => $r->rateMissing,
            ];

            if (! $r->rateMissing && $r->breakdown instanceof PricingBreakdown) {
                $row['display_price'] = $r->breakdown->displayPrice->amountMinor;
                $row['display_price_formatted'] = DisplayMoney::format($r->breakdown->displayPrice->amountMinor, $r->breakdown->displayPrice->currency);

                if ($canSeeMargin) {
                    $row['cost_total'] = $r->breakdown->costTotal->amountMinor;
                    $row['margin_percent'] = $r->breakdown->marginPercent;
                    $row['margin_minor'] = $r->breakdown->marginMinor->amountMinor;
                    $row['channel_cost'] = $r->breakdown->channelCost->amountMinor;
                }
            }

            return $row;
        }, $itemResults);

        $grand = [
            'sell_idr_minor' => $result->grandSellIdrMinor->amountMinor,
            'display_price' => $result->grandDisplayPrice->amountMinor,
            'display_price_formatted' => DisplayMoney::format($result->grandDisplayPrice->amountMinor, $result->grandDisplayPrice->currency),
        ];

        if ($canSeeMargin) {
            $grand['cost_total'] = $result->grandCostTotal->amountMinor;
            $grand['margin_minor'] = $result->grandMarginMinor->amountMinor;
            $grand['channel_cost'] = $result->grandChannelCost->amountMinor;
        }

        return ['items' => $items, 'grand' => $grand];
    }

    public function render(): View
    {
        $branchId = CurrentBranch::id();
        $availableInventory = InventoryItem::query()
            ->where('is_active', true)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['partner', 'rates'])
            ->get();

        return view('livewire.admin.packaging.package-builder', [
            'availableInventory' => $availableInventory,
        ]);
    }
}
