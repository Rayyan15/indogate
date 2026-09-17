<?php

namespace App\Domain\Catalog\Models;

use App\Enums\PartnerType;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Partner extends Model
{
    use BelongsToBranch, HasFactory, HasTranslations, LogsActivity;

    protected $fillable = ['branch_id', 'name', 'type', 'city', 'contact', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
