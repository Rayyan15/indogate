<?php

namespace App\Domain\Pricing\Services;

use App\Domain\Pricing\Models\ExchangeRate;

/** Observer: writes one 'pricing' activity entry per new rate: source, old -> new, % change. */
final class RateChangeLogger
{
    public function created(ExchangeRate $new): void
    {
        // The rate in force just before this one takes effect (handles back-dated rows).
        $old = ExchangeRate::where('currency', $new->currency)
            ->whereKeyNot($new->getKey())
            ->where('effective_from', '<=', $new->effective_from)
            ->latest('effective_from')->latest('id')->first();

        $change = $old && (float) $old->rate > 0
            ? round(((float) $new->rate / (float) $old->rate - 1) * 100, 4)
            : null;

        activity('pricing')
            ->performedOn($new)
            ->causedBy($new->creator)
            ->withProperties([
                'currency' => $new->currency,
                'source' => $new->source,
                'old_rate' => $old?->rate,
                'new_rate' => $new->rate,
                'change_percent' => $change,
                'pinned_until' => $new->pinned_until?->toIso8601String(),
            ])
            ->log('exchange_rate_changed');
    }
}
