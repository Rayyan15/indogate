<?php

namespace App\Domain\Packaging\Models;

use App\Domain\Packaging\PackageCalculator;
use App\Enums\PaymentChannel;
use App\Support\Branch\BelongsToBranch;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Package extends Model
{
    use BelongsToBranch, HasTranslations, LogsActivity;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'is_template',
        'is_published',
        'is_featured',
        'base_pax',
        'duration_days',
        'brochure_path',
        'cover_image',
        'highlights',
        'starting_price_idr',
    ];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'base_pax' => 'integer',
            'duration_days' => 'integer',
            'highlights' => 'array',
            'starting_price_idr' => 'integer',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class)->orderBy('sort_order');
    }

    public function days(): HasMany
    {
        return $this->hasMany(PackageDay::class)->orderBy('day_number');
    }

    /**
     * Storefront "starting from" = selling price for base_pax today via bank transfer
     * (cheapest channel). Keeps the old value when the package can't be priced yet
     * (no items, missing rate or FX), so a half-built package never shows 0.
     */
    public function refreshStartingPrice(): void
    {
        $this->load('items');

        if ($this->items->isEmpty()) {
            return;
        }

        try {
            $result = app(PackageCalculator::class)->calculate(
                $this, max(1, (int) $this->base_pax), new DateTimeImmutable('today'), PaymentChannel::BANK_TRANSFER, 'IDR'
            );
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        $this->forceFill(['starting_price_idr' => $result->grandSellIdrMinor->amountMinor])->saveQuietly();
    }

    /**
     * PRD M5: duplication must produce an independent copy — new ids
     * throughout, always starts as a non-template working package.
     */
    public function duplicate(): self
    {
        return DB::transaction(function () {
            $copy = $this->replicate();
            $copy->is_template = false;
            // A copy is a draft and must not share files with the original
            // (replacing the copy's brochure would delete the original's; H-01/M-07).
            $copy->is_published = false;
            $copy->is_featured = false;
            $copy->brochure_path = null;
            $copy->cover_image = null;

            foreach ($this->getTranslations('name') as $locale => $value) {
                $suffixed = $locale === app()->getLocale() ? $value.' (Copy)' : $value;
                $copy->setTranslation('name', $locale, $suffixed);
            }

            $copy->save();

            foreach ($this->items as $item) {
                $copy->items()->create([
                    'inventory_item_id' => $item->inventory_item_id,
                    'day_from' => $item->day_from,
                    'day_to' => $item->day_to,
                    'qty' => $item->qty,
                    'nights' => $item->nights,
                    'sort_order' => $item->sort_order,
                ]);
            }

            foreach ($this->days as $day) {
                $newDay = $copy->days()->make(['day_number' => $day->day_number]);
                foreach (['title', 'notes'] as $field) {
                    foreach ($day->getTranslations($field) as $locale => $value) {
                        $newDay->setTranslation($field, $locale, $value);
                    }
                }
                $newDay->save();
            }

            return $copy->fresh(['items', 'days']);
        });
    }
}
