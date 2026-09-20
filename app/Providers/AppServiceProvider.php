<?php

namespace App\Providers;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Models\VendorPayment;
use App\Domain\Fleet\Models\Driver;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Packaging\Models\Package;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\Season;
use App\Listeners\LogAuthenticationActivity;
use App\Models\Booking;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\DriverAssignmentPolicy;
use App\Policies\DriverPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\LeadPolicy;
use App\Policies\MarginRulePolicy;
use App\Policies\PackageBookingPolicy;
use App\Policies\PackagePolicy;
use App\Policies\PartnerPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\RefundPolicy;
use App\Policies\SeasonPolicy;
use App\Policies\UserPolicy;
use App\Policies\VehiclePolicy;
use App\Policies\VendorPaymentPolicy;
use App\Support\Branch\BranchScope;
use Illuminate\Support\Facades\Event;
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
        Gate::policy(Driver::class, DriverPolicy::class);
        Gate::policy(Vehicle::class, VehiclePolicy::class);
        Gate::policy(DriverAssignment::class, DriverAssignmentPolicy::class);
        Gate::policy(VendorPayment::class, VendorPaymentPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);

        // Route-model binding must bypass BranchScope so a cross-branch id
        // resolves the model and reaches the Policy (which 403s explicitly)
        // instead of the query silently filtering it out into a 404.
        Route::bind('booking', fn ($id) => Booking::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('payment', fn ($id) => Payment::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('vendorPayment', fn ($id) => VendorPayment::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('refund', fn ($id) => Refund::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('partner', fn ($id) => Partner::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('item', fn ($id) => InventoryItem::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('package', fn ($id) => Package::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('lead', fn ($id) => Lead::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        // Public token lookup has no branch/auth context at all — bypass
        // scope unconditionally, the unguessable token is the boundary.
        Route::bind('quotation', fn ($token) => Quotation::withoutGlobalScope(BranchScope::class)->where('token', $token)->firstOrFail());
        Route::bind('packageBooking', fn ($id) => PackageBooking::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('driver', fn ($id) => Driver::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('vehicle', fn ($id) => Vehicle::withoutGlobalScope(BranchScope::class)->findOrFail($id));
        Route::bind('assignment', fn ($id) => DriverAssignment::withoutGlobalScope(BranchScope::class)->findOrFail($id));

        Event::subscribe(LogAuthenticationActivity::class);
    }
}
