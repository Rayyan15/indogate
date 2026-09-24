<?php

namespace App\Domain\Pricing\Models;

use App\Domain\Finance\Fx;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Pricing\Services\RateChangeLogger;

/**
 * Insert-only: rows are never updated. "Current rate" for a currency is the
 * latest row where effective_from <= now().
 */
#[ObservedBy(RateChangeLogger::class)]
class ExchangeRate extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_API_PREFIX = 'api:';

    public const SOURCE_FAWAZAHMED0 = 'api:fawazahmed0';

    protected $fillable = ['currency', 'rate', 'source', 'pinned_until', 'effective_from', 'created_by'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'effective_from' => 'datetime',
            'pinned_until' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => Fx::flush();
        static::saved($flush);
        static::deleted($flush);
    }

    public function isPinned(): bool
    {
        return $this->pinned_until !== null && $this->pinned_until->isFuture();
    }

    public function isAuto(): bool
    {
        return str_starts_with($this->source, self::SOURCE_API_PREFIX);
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
