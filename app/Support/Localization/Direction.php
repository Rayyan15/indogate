<?php

namespace App\Support\Localization;

class Direction
{
    private const RTL_LOCALES = ['ar'];

    public static function current(): string
    {
        return static::of(app()->getLocale());
    }

    public static function of(string $locale): string
    {
        return in_array($locale, self::RTL_LOCALES, true) ? 'rtl' : 'ltr';
    }
}
