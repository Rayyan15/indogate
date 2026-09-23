<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\ExchangeRate;

/**
 * Converts IDR minor units to another currency using bcmath string decimal
 * math — never float. Rounding happens exactly once, at the very end.
 *
 * rate = how many IDR (major unit) buys 1 major unit of the target
 * currency. IDR itself has 0 decimal places (amount_minor === rupiah).
 */
class Converter
{
    public function toDisplayCurrency(Money $idrAmount, string $targetCurrency, ?ExchangeRate $lockedRate = null): Money
    {
        $targetCurrency = strtoupper($targetCurrency);

        if ($targetCurrency === 'IDR') {
            return Money::of($idrAmount->amountMinor, 'IDR');
        }

        $rate = $lockedRate ?? ExchangeRate::currentFor($targetCurrency);

        if (! $rate) {
            throw new ExchangeRateNotFoundException("No exchange rate found for {$targetCurrency}.");
        }

        $decimalPlaces = Currency::find($targetCurrency)?->decimal_places ?? 2;
        $scale = bcpow('10', (string) $decimalPlaces);

        $majorAmount = bcdiv((string) $idrAmount->amountMinor, $rate->rate, $decimalPlaces + 4);
        // Round half-up; bcmul(..., 0) alone truncates (bug-review M-02).
        $minorAmount = bcadd(bcmul($majorAmount, $scale, 4), '0.5', 0);

        return Money::of((int) $minorAmount, $targetCurrency);
    }
}
