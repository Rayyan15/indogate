<?php

namespace App\Support\Pricing;

use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;

/** Latest rates + decimals as plain data for the client-side money preview. */
final class FxPreview
{
    /** @return array<string, array{rate: float, decimals: int}> IDR per 1 major unit */
    public static function data(): array
    {
        $currencies = Currency::where('is_active', true)->get();
        // Ascending order: keyBy keeps the last row per currency, i.e. the latest.
        $rates = ExchangeRate::where('effective_from', '<=', now())
            ->whereIn('currency', $currencies->pluck('code'))
            ->orderBy('effective_from')->orderBy('id')
            ->get()->keyBy('currency');

        $out = [];
        foreach ($currencies as $c) {
            $rate = $c->code === 'IDR' ? 1.0 : (float) ($rates[$c->code]->rate ?? 0);
            if ($rate > 0) {
                $out[$c->code] = ['rate' => $rate, 'decimals' => $c->decimal_places];
            }
        }

        return $out;
    }
}
