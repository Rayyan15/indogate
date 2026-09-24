<?php

namespace App\Support\Storefront;

use App\Domain\Pricing\Converter;
use App\Domain\Finance\Fx;
use App\Domain\Pricing\Money;
use NumberFormatter;

class StorefrontCurrency
{
    /** @return list<string> Currencies that can actually be priced (memoised by Fx). */
    public static function supported(): array
    {
        return Fx::pricedCurrencies() ?: ['IDR'];
    }

    public static function isSupported(string $currency): bool
    {
        return in_array($currency, self::supported(), true);
    }

    public static function current(): string
    {
        $sessionCurrency = session('storefront_currency');
        if ($sessionCurrency && self::isSupported($sessionCurrency)) {
            return $sessionCurrency;
        }

        return match (app()->getLocale()) {
            'ar' => 'SAR',
            'id' => 'IDR',
            default => 'USD',
        };
    }

    public static function set(string $currency): void
    {
        $upper = strtoupper(trim($currency));
        if (self::isSupported($upper)) {
            session(['storefront_currency' => $upper]);
        }
    }

    public static function format(?int $amountIdrMinor, ?string $targetCurrency = null, ?string $locale = null): string
    {
        if ($amountIdrMinor === null || $amountIdrMinor <= 0) {
            return '-';
        }

        $currency = $targetCurrency ?? self::current();
        $locale = $locale ?? app()->getLocale();

        $converter = new Converter;
        $idrMoney = Money::of($amountIdrMinor, 'IDR');

        try {
            $converted = $converter->toSellingPrice($idrMoney, $currency);
            $amountMajor = $converted->amountMinor / (10 ** Fx::decimals($currency));
        } catch (\Throwable) {
            // No rate stored for this currency yet: show IDR rather than a made-up rate.
            $currency = 'IDR';
            $amountMajor = $idrMoney->amountMinor;
        }

        $regional = config("laravellocalization.supportedLocales.$locale.regional", 'en_US');
        $formatter = new NumberFormatter($regional, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency((float) $amountMajor, $currency);
    }
}
