<?php

namespace App\Domain\Catalog\Models;

use App\Enums\InventoryItemType;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class InventoryItem extends Model
{
    use BelongsToBranch, HasFactory, HasTranslations, LogsActivity;

    protected $fillable = ['branch_id', 'partner_id', 'type', 'name', 'description', 'capacity', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'type' => InventoryItemType::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ItemMedia::class)->orderBy('sort_order');
    }

    public function blackoutDates(): HasMany
    {
        return $this->hasMany(BlackoutDate::class);
    }
}
