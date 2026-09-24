<?php

namespace App\Domain\Pricing\Models;

use App\Enums\InventoryItemType;
use App\Enums\SeasonType;
use App\Models\Branch;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * PRD table name "pricing_rules" is taken by the old MVP schema, so the M4
 * schema lives here as margin_rules. margin_percent is basis points.
 */
class MarginRule extends Model
{
    use BelongsToBranch, LogsActivity;

    protected $fillable = ['branch_id', 'product_type', 'season_type', 'margin_percent', 'is_active'];

    protected function casts(): array
    {
        return [
            'product_type' => InventoryItemType::class,
            'season_type' => SeasonType::class,
            'margin_percent' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
