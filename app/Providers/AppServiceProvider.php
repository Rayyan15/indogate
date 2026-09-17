<?php

namespace App\Providers;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\UserPolicy;
use App\Support\Branch\BranchScope;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Partner::class, PartnerPolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);

        // Route-model binding must bypass BranchScope so a cross-branch id
        // resolves the model and reaches the Policy (which 403s explicitly)
        // instead of the query silently filtering it out into a 404.
        Route::bind('booking', fn ($id) => Booking::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('payment', fn ($id) => Payment::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('partner', fn ($id) => Partner::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('item', fn ($id) => InventoryItem::withoutGlobalScope(BranchScope::class)->findOrFail($id));
    }
}
