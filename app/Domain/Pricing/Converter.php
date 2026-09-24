<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Domain\Finance\Fx;
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

        $rate = $lockedRate ?? Fx::currentRate($targetCurrency);

        if (! $rate) {
            throw new ExchangeRateNotFoundException("No exchange rate found for {$targetCurrency}.");
        }

        $decimalPlaces = Fx::decimals($targetCurrency);
        $scale = bcpow('10', (string) $decimalPlaces);

        $majorAmount = bcdiv((string) $idrAmount->amountMinor, $rate->rate, $decimalPlaces + 4);
        // Round half-up; bcmul(..., 0) alone truncates (bug-review M-02).
        $minorAmount = bcadd(bcmul($majorAmount, $scale, 4), '0.5', 0);

        if (bccomp($minorAmount, (string) PHP_INT_MAX) > 0) {
            throw new \OverflowException("Converted amount for {$targetCurrency} exceeds the supported range.");
        }

        return Money::of((int) $minorAmount, $targetCurrency);
    }

    /**
     * Storefront selling price: market conversion plus the currency's spread
     * (basis points), then rounded UP to display_rounding major units. Only
     * for prices shown to customers; never for locked (booked) rates or
     * margin reporting, which use toDisplayCurrency()/Fx at raw market rate.
     */
    public function toSellingPrice(Money $idrAmount, string $targetCurrency): Money
    {
        $targetCurrency = strtoupper($targetCurrency);
        $currency = Fx::currency($targetCurrency);
        $decimals = Fx::decimals($targetCurrency);
        $base = $this->toDisplayCurrency($idrAmount, $targetCurrency);

        $minor = (string) $base->amountMinor;
        $spread = (int) ($currency?->spread_bps ?? 0);
        if ($spread > 0) {
            // Half-up, consistent with the conversion step.
            $minor = bcdiv(bcadd(bcmul($minor, (string) (10000 + $spread), 0), '5000', 0), '10000', 0);
        }

        $step = (int) ($currency?->display_rounding ?? 0);
        if ($step > 0) {
            $stepMinor = bcmul((string) $step, bcpow('10', (string) $decimals), 0);
            $units = bcdiv(bcadd($minor, bcsub($stepMinor, '1'), 0), $stepMinor, 0);
            $minor = bcmul($units, $stepMinor, 0);
        }

        if (bccomp($minor, (string) PHP_INT_MAX) > 0) {
            throw new \OverflowException("Selling price for {$targetCurrency} exceeds the supported range.");
        }

        return Money::of((int) $minor, $targetCurrency);
    }
}
