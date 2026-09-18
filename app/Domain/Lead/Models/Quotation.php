<?php

namespace App\Domain\Lead\Models;

use App\Domain\Packaging\Models\Package;
use App\Enums\QuotationStatus;
use App\Support\Branch\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id', 'lead_id', 'package_id', 'token',
        'currency', 'locked_rate', 'valid_until', 'status',
    ];

    protected function casts(): array
    {
        return [
            'locked_rate' => 'decimal:8',
            'valid_until' => 'datetime',
            'status' => QuotationStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function isExpired(): bool
    {
        return $this->status === QuotationStatus::EXPIRED || $this->valid_until->isPast();
    }
}
