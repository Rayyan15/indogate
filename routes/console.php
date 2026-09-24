<?php

use App\Domain\Packaging\Models\Package;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('packages:refresh-prices', function () {
    Package::withoutGlobalScopes()->each(function ($package) {
        $package->refreshStartingPrice();
        $this->line("#{$package->id} {$package->name}: IDR ".number_format((int) $package->starting_price_idr, 0, ',', '.'));
    });
})->purpose('Recompute storefront starting prices from the pricing engine');

Schedule::command('payments:expire-intents')->everyFiveMinutes();
