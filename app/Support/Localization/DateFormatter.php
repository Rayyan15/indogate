<?php

namespace App\Support\Localization;

use DateTimeInterface;
use Illuminate\Support\Carbon;

class DateFormatter
{
    public static function format(DateTimeInterface $date, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $regional = config("laravellocalization.supportedLocales.$locale.regional", 'en_US');

        return Carbon::instance($date)->locale($regional)->translatedFormat('d MMMM Y');
    }
}
