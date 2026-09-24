<?php

namespace App\Support\Storefront;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Money;
use NumberFormatter;

class StorefrontCurrency
{
    public const SUPPORTED_CURRENCIES = ['SAR', 'USD', 'IDR'];

    public static function current(): string
    {
        $sessionCurrency = session('storefront_currency');
        if ($sessionCurrency && in_array($sessionCurrency, self::SUPPORTED_CURRENCIES, true)) {
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
        if (in_array($upper, self::SUPPORTED_CURRENCIES, true)) {
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
            $converted = $converter->toDisplayCurrency($idrMoney, $currency);
            $amountMajor = $currency === 'IDR' ? $converted->amountMinor : $converted->amountMinor / 100;
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
