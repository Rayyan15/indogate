<?php

namespace App\Domain\Packaging\Models;

use App\Domain\Catalog\Models\InventoryItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No BelongsToBranch here — a package item is scoped transitively through
 * its package, same way Rate has no branch column of its own.
 */
class PackageItem extends Model
{
    protected $fillable = ['package_id', 'inventory_item_id', 'day_from', 'day_to', 'qty', 'nights', 'sort_order'];

    protected function casts(): array
    {
        return [
            'day_from' => 'integer',
            'day_to' => 'integer',
            'qty' => 'integer',
            'nights' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
