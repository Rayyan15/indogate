<?php

namespace App\Domain\Pricing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Insert-only: rows are never updated. "Current rate" for a currency is the
 * latest row where effective_from <= now().
 */
class ExchangeRate extends Model
{
    use LogsActivity;

    protected $fillable = ['currency', 'rate', 'effective_from', 'created_by'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'effective_from' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function currentFor(string $currency, ?\DateTimeInterface $asOf = null): ?self
    {
        return static::query()
            ->where('currency', strtoupper($currency))
            ->where('effective_from', '<=', $asOf ?? now())
            ->orderByDesc('effective_from')
            ->first();
    }
}
