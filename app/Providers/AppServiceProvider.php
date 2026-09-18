<?php

namespace App\Providers;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Packaging\Models\Package;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\Season;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\LeadPolicy;
use App\Policies\MarginRulePolicy;
use App\Policies\PackageBookingPolicy;
use App\Policies\PackagePolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\SeasonPolicy;
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
        Gate::policy(Season::class, SeasonPolicy::class);
        Gate::policy(MarginRule::class, MarginRulePolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(PackageBooking::class, PackageBookingPolicy::class);

        // Route-model binding must bypass BranchScope so a cross-branch id
        // resolves the model and reaches the Policy (which 403s explicitly)
        // instead of the query silently filtering it out into a 404.
        Route::bind('booking', fn ($id) => Booking::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('payment', fn ($id) => Payment::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('partner', fn ($id) => Partner::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('item', fn ($id) => InventoryItem::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('package', fn ($id) => Package::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('lead', fn ($id) => Lead::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        // Public token lookup has no branch/auth context at all — bypass
        // scope unconditionally, the unguessable token is the boundary.
        Route::bind('quotation', fn ($token) => Quotation::withoutGlobalScope(BranchScope::class)->where('token', $token)->firstOrFail());
        Route::bind('packageBooking', fn ($id) => PackageBooking::withoutGlobalScope(BranchScope::class)->findOrFail($id));
    }
}
