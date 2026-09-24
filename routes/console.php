<?php

use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Services\GatewayCheckout;
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

Artisan::command('payments:expire-intents', function () {
    $stale = PaymentIntent::withoutGlobalScopes()
        ->where('status', PaymentIntent::STATUS_PENDING)
        ->where('expires_at', '<', now())
        ->get();
    $count = PaymentIntent::withoutGlobalScopes()->whereKey($stale->modelKeys())
        ->update(['status' => PaymentIntent::STATUS_EXPIRED, 'updated_at' => now()]);
    $stale->each(fn ($intent) => GatewayCheckout::notifyFailed($intent));
    $this->info("Expired {$count} payment intent(s).");
})->purpose('Mark unpaid online payment intents past their deadline as expired');

Schedule::command('payments:expire-intents')->everyFiveMinutes();
