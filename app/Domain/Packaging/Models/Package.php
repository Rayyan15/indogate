<?php

namespace App\Domain\Packaging\Models;

use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Package extends Model
{
    use BelongsToBranch, HasTranslations, LogsActivity;

    protected $fillable = ['branch_id', 'name', 'description', 'is_template', 'base_pax', 'duration_days', 'brochure_path'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
            'base_pax' => 'integer',
            'duration_days' => 'integer',
        ];
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
     * PRD M5: duplication must produce an independent copy — new ids
     * throughout, always starts as a non-template working package.
     */
    public function duplicate(): self
    {
        return DB::transaction(function () {
            $copy = $this->replicate();
            $copy->is_template = false;

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
