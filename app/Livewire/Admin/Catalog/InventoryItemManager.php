<?php

namespace App\Livewire\Admin\Catalog;

use App\Domain\Catalog\Models\BlackoutDate;
use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\ItemMedia;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Domain\Catalog\Rules\RateDoesNotOverlap;
use App\Enums\InventoryItemType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class InventoryItemManager extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $itemId = null;

    /** @var array<string,string> */
    public array $name = ['en' => '', 'id' => '', 'ar' => ''];

    /** @var array<string,string> */
    public array $description = ['en' => '', 'id' => '', 'ar' => ''];

    public ?int $partner_id = null;

    public string $type = '';

    public ?int $capacity = null;

    public bool $is_active = true;

    // Rate sub-form
    public string $rate_valid_from = '';

    public string $rate_valid_to = '';

    public ?int $rate_cost_minor = null;

    public string $rate_currency = 'IDR';

    // Blackout sub-form
    public string $blackout_date = '';

    public string $blackout_reason = '';

    /** @var array<int,TemporaryUploadedFile> */
    public array $newPhotos = [];

    public function mount(?InventoryItem $item = null): void
    {
        if ($item?->exists) {
            $this->authorize('update', $item);
            $this->itemId = $item->id;
            $this->name = [
                'en' => $item->getTranslation('name', 'en', false) ?? '',
                'id' => $item->getTranslation('name', 'id', false) ?? '',
                'ar' => $item->getTranslation('name', 'ar', false) ?? '',
            ];
            $this->description = [
                'en' => $item->getTranslation('description', 'en', false) ?? '',
                'id' => $item->getTranslation('description', 'id', false) ?? '',
                'ar' => $item->getTranslation('description', 'ar', false) ?? '',
            ];
            $this->partner_id = $item->partner_id;
            $this->type = $item->type->value;
            $this->capacity = $item->capacity;
            $this->is_active = $item->is_active;
        } else {
            $this->authorize('viewAny', InventoryItem::class);
        }
    }

    public function item(): ?InventoryItem
    {
        return $this->itemId ? InventoryItem::findOrFail($this->itemId) : null;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name.en' => ['required', 'string', 'max:255'],
            'name.id' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'description.en' => ['nullable', 'string'],
            'description.id' => ['nullable', 'string'],
            'description.ar' => ['nullable', 'string'],
            'partner_id' => ['required', 'exists:partners,id'],
            'type' => ['required', 'in:'.implode(',', array_column(InventoryItemType::cases(), 'value'))],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $item = $this->item() ?? new InventoryItem;

        if ($item->exists) {
            $this->authorize('update', $item);
        } else {
            $this->authorize('viewAny', InventoryItem::class);
        }

        $item->fill([
            'partner_id' => $validated['partner_id'],
            'type' => $validated['type'],
            'capacity' => $validated['capacity'],
            'is_active' => $validated['is_active'],
        ]);

        foreach ($validated['name'] as $locale => $value) {
            $item->setTranslation('name', $locale, $value);
        }
        foreach ($validated['description'] as $locale => $value) {
            $item->setTranslation('description', $locale, (string) $value);
        }

        $item->save();
        $this->itemId = $item->id;

        session()->flash('success', __('catalog.item.saved'));
    }

    public function addRate(): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        $validated = $this->validate([
            'rate_valid_from' => ['required', 'date'],
            'rate_valid_to' => [
                'required', 'date', 'after_or_equal:rate_valid_from',
                new RateDoesNotOverlap($item->id, $this->rate_valid_from),
            ],
            'rate_cost_minor' => ['required', 'integer', 'min:0'],
            'rate_currency' => ['required', 'string', 'size:3'],
        ]);

        Rate::create([
            'inventory_item_id' => $item->id,
            'valid_from' => $validated['rate_valid_from'],
            'valid_to' => $validated['rate_valid_to'],
            'cost_minor' => $validated['rate_cost_minor'],
            'currency' => strtoupper($validated['rate_currency']),
        ]);

        $this->reset(['rate_valid_from', 'rate_valid_to', 'rate_cost_minor']);
        $this->rate_currency = 'IDR';
    }

    public function deleteRate(int $rateId): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        Rate::where('inventory_item_id', $item->id)->whereKey($rateId)->delete();
    }

    public function addBlackoutDate(): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        $validated = $this->validate([
            'blackout_date' => ['required', 'date'],
            'blackout_reason' => ['nullable', 'string', 'max:255'],
        ]);

        BlackoutDate::firstOrCreate([
            'inventory_item_id' => $item->id,
            'date' => $validated['blackout_date'],
        ], [
            'reason' => $validated['blackout_reason'],
        ]);

        $this->reset(['blackout_date', 'blackout_reason']);
    }

    public function deleteBlackoutDate(int $blackoutId): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        BlackoutDate::where('inventory_item_id', $item->id)->whereKey($blackoutId)->delete();
    }

    public function uploadPhotos(): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        $this->validate([
            'newPhotos.*' => ['image', 'max:4096'],
        ]);

        $nextSort = (int) $item->media()->max('sort_order') + 1;

        foreach ($this->newPhotos as $photo) {
            $filename = uniqid('item_').'.jpg';
            $relativePath = "catalog/{$item->id}/{$filename}";

            // Re-encode at ~80% JPEG quality — PRD "kompresi" requirement,
            // no new dependency: GD ships with PHP.
            if (function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
                $source = @imagecreatefromstring(file_get_contents($photo->getRealPath()));
                if ($source !== false) {
                    Storage::disk('public')->makeDirectory("catalog/{$item->id}");
                    imagejpeg($source, Storage::disk('public')->path($relativePath), 80);
                    imagedestroy($source);
                } else {
                    Storage::disk('public')->putFileAs("catalog/{$item->id}", $photo, $filename);
                }
            } else {
                Storage::disk('public')->putFileAs("catalog/{$item->id}", $photo, $filename);
            }

            ItemMedia::create([
                'inventory_item_id' => $item->id,
                'path' => $relativePath,
                'sort_order' => $nextSort++,
            ]);
        }

        $this->reset('newPhotos');
    }

    public function deleteMedia(int $mediaId): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        $media = ItemMedia::where('inventory_item_id', $item->id)->whereKey($mediaId)->first();
        if ($media) {
            Storage::disk('public')->delete($media->path);
            $media->delete();
        }
    }

    public function moveMedia(int $mediaId, int $direction): void
    {
        $item = $this->item();
        if (! $item) {
            return;
        }
        $this->authorize('update', $item);

        $ordered = $item->media()->get();
        $index = $ordered->search(fn ($m) => $m->id === $mediaId);
        $swapWith = $index + $direction;

        if ($index === false || ! $ordered->has($swapWith)) {
            return;
        }

        $a = $ordered[$index];
        $b = $ordered[$swapWith];
        [$a->sort_order, $b->sort_order] = [$b->sort_order, $a->sort_order];
        $a->save();
        $b->save();
    }

    public function render(): View
    {
        $item = $this->item();

        return view('livewire.admin.catalog.inventory-item-manager', [
            'partners' => Partner::orderBy('id')->get(),
            'types' => InventoryItemType::cases(),
            'rates' => $item?->rates()->orderByDesc('valid_from')->get() ?? collect(),
            'mediaItems' => $item?->media ?? collect(),
            'blackoutDates' => $item?->blackoutDates()->orderBy('date')->get() ?? collect(),
        ]);
    }
}
