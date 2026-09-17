<?php

namespace App\Support\Localization;

use NumberFormatter;

/**
 * PRD M2 step 7: "Helper format mata uang (menerima currency + locale)".
 * Deliberately NOT the M4 Money value object (BIGINT minor units) — this
 * only formats whatever numeric amount a view already has for display.
 */
class Money
{
    public static function format(int|float $amount, string $currency = 'IDR', ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $regional = config("laravellocalization.supportedLocales.$locale.regional", 'en_US');

        $formatter = new NumberFormatter($regional, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((float) $amount, $currency);
    }
}
