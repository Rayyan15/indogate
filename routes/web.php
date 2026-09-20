<?php

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Lead\Models\Lead;
use App\Domain\Packaging\Models\Package;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\BookingGuestDocumentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FinanceDocumentController;
use App\Http\Controllers\Admin\FleetDutyLetterController;
use App\Http\Controllers\Admin\FlightRouteController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\LeadCaptureController;
use App\Http\Controllers\Public\QuotationController;
use App\Http\Controllers\Public\StorefrontController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/'.app()->getLocale());
});

Route::prefix('{locale}')
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales')))
    ->middleware('setLocale')
    ->group(function () {
        // Storefront Publik (PRD M10)
        Route::get('/', [StorefrontController::class, 'home'])->name('public.home');
        Route::get('/packages', [StorefrontController::class, 'catalog'])->name('public.catalog');
        Route::get('/packages/{package}', [StorefrontController::class, 'show'])->name('public.package.show');
        Route::post('/currency', [StorefrontController::class, 'switchCurrency'])->name('public.currency.switch');

        // Admin Routes
        Route::middleware(['auth', 'role:Super Admin|CS Admin|Finance Admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            if (app()->isLocal()) {
                Route::view('/styleguide', 'admin.styleguide')->name('styleguide');
            }

            Route::middleware('permission:catalog.manage')->resource('hotels', HotelController::class);
            Route::middleware('permission:catalog.manage')->resource('flights', FlightRouteController::class);
            Route::middleware('permission:catalog.manage')->resource('drivers', DriverController::class);
            Route::middleware('permission:pricing.manage')->resource('pricing-rules', PricingRuleController::class)->names('pricing');

            Route::middleware('permission:catalog.manage')->prefix('packages')->name('packages.')->group(function () {
                Route::view('/', 'admin.packaging.index')->name('index');
                Route::view('/create', 'admin.packaging.builder')->name('create');
                Route::get('/{package}/edit', fn (Package $package) => view('admin.packaging.builder', ['package' => $package]))->name('edit');

                // The 'local' disk (storage/app/private) is not web-servable via
                // the public /storage symlink — stream it through a signed route
                // instead, same pattern as PaymentController::downloadProof.
                Route::get('/{package}/export/{path}', function (Package $package, string $path) {
                    $fullPath = "package-exports/{$package->id}/{$path}";
                    abort_unless(Storage::disk('local')->exists($fullPath), 404);

                    return Storage::disk('local')->download($fullPath);
                })->where('path', '[A-Za-z0-9_.\-]+')->middleware('signed')->name('export-download');
            });

            Route::middleware('permission:pricing.manage')->prefix('pricing-engine')->name('pricing-engine.')->group(function () {
                Route::view('/currencies', 'admin.pricing-engine.currencies')->name('currencies');
                Route::view('/exchange-rates', 'admin.pricing-engine.exchange-rates')->name('exchange-rates');
                Route::view('/seasons', 'admin.pricing-engine.seasons')->name('seasons');
                Route::view('/margin-rules', 'admin.pricing-engine.margin-rules')->name('margin-rules');
                Route::view('/channel-costs', 'admin.pricing-engine.channel-costs')->name('channel-costs');
                Route::view('/simulator', 'admin.pricing-engine.simulator')->name('simulator');
            });

            Route::middleware('permission:catalog.manage')->prefix('catalog')->name('catalog.')->group(function () {
                Route::view('/partners', 'admin.catalog.partners')->name('partners.index');
                Route::view('/inventory-items', 'admin.catalog.inventory-items')->name('inventory-items.index');
                Route::get('/inventory-items/create', fn () => view('admin.catalog.inventory-item-form'))->name('inventory-items.create');
                Route::get('/inventory-items/{item}/edit', fn (InventoryItem $item) => view('admin.catalog.inventory-item-form', ['item' => $item]))->name('inventory-items.edit');
            });

            Route::resource('bookings', BookingController::class)->only(['index', 'show']);
            Route::middleware('permission:driver.assign')->post('bookings/{booking}/assign-driver', [BookingController::class, 'assignDriver'])->name('bookings.assign-driver');

            Route::middleware('permission:payment.verify')->group(function () {
                Route::resource('payments', PaymentController::class)->only(['index', 'show']);
                Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
                Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

                // Secure payment proof download (Signed Route target)
                Route::get('payment-proofs/{proof}', [PaymentController::class, 'downloadProof'])
                    ->name('payments.download-proof')
                    ->middleware('signed');
            });

            Route::middleware('permission:user.manage')->get('/users', fn () => view('admin.users.index'))->name('users.index');

            Route::middleware('permission:lead.manage')->prefix('leads')->name('leads.')->group(function () {
                Route::view('/', 'admin.lead.index')->name('index');
                Route::view('/create', 'admin.lead.form')->name('create');
                Route::get('/{lead}/edit', fn (Lead $lead) => view('admin.lead.form', ['lead' => $lead]))->name('edit');
            });

            Route::middleware('permission:booking.manage|payment.verify')->prefix('package-bookings')->name('package-bookings.')->group(function () {
                Route::view('/', 'admin.booking.index')->name('index');
                Route::get('/{packageBooking}', fn (PackageBooking $packageBooking) => view('admin.booking.show', ['packageBooking' => $packageBooking]))->name('show');

                // Passport document: signed URL + throttle is the technical
                // block on bulk download (PRD M7 step 9) — no zip/bulk
                // endpoint exists anywhere in this app.
                Route::get('/guests/{guest}/passport', [BookingGuestDocumentController::class, 'download'])
                    ->middleware(['signed', 'throttle:20,1'])
                    ->name('guests.passport-download');

                Route::get('/{packageBooking}/voucher/{path}', function (PackageBooking $packageBooking, string $path) {
                    $fullPath = "booking-exports/{$packageBooking->id}/{$path}";
                    abort_unless(Storage::disk('local')->exists($fullPath), 404);

                    return Storage::disk('local')->download($fullPath);
                })->where('path', '[A-Za-z0-9_.\-]+')->middleware('signed')->name('voucher-download');
            });

            Route::middleware('permission:driver.assign')->prefix('fleet')->name('fleet.')->group(function () {
                Route::view('/drivers', 'admin.fleet.drivers')->name('drivers');
                Route::view('/vehicles', 'admin.fleet.vehicles')->name('vehicles');
                Route::view('/calendar', 'admin.fleet.calendar')->name('calendar');

                Route::get('/assignments/{assignment}/duty-letter', [FleetDutyLetterController::class, 'show'])
                    ->middleware('signed')
                    ->name('assignments.duty-letter');
            });

            Route::prefix('finance')->name('finance.')->group(function () {
                Route::view('/payments', 'admin.finance.payments')
                    ->middleware('can:payment.verify')
                    ->name('payments');

                Route::view('/receivables', 'admin.finance.receivables')
                    ->middleware('permission:payment.verify|booking.manage')
                    ->name('receivables');

                Route::view('/vendor-payments', 'admin.finance.vendor-payments')
                    ->middleware('can:payment.verify')
                    ->name('vendor-payments');

                Route::view('/margin-report', 'admin.finance.margin-report')
                    ->middleware('permission:report.margin.view|payment.verify')
                    ->name('margin-report');

                Route::get('/invoices/{packageBooking}', [FinanceDocumentController::class, 'invoice'])
                    ->name('invoice');

                Route::get('/receipts/{payment}', [FinanceDocumentController::class, 'receipt'])
                    ->name('receipt');

                Route::get('/proofs/{payment}', [FinanceDocumentController::class, 'downloadProof'])
                    ->middleware('signed')
                    ->name('proofs.download');
            });

            Route::prefix('reports')->name('reports.')->middleware('permission:report.margin.view|lead.manage|booking.manage|payment.verify')->group(function () {
                Route::view('/', 'admin.reporting.reports')->name('index');
            });

            Route::prefix('security')->name('security.')->group(function () {
                Route::view('/audit-logs', 'admin.security.audit-logs')
                    ->middleware('permission:activitylog.view')
                    ->name('audit-logs.index');
            });
        });

        Route::middleware('auth')->group(function () {
            Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
            Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
            Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

            // Customer Routes
            Route::middleware('role:Customer')->group(function () {
                Route::get('/dashboard', [App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
                Route::get('/bookings/{booking}', [App\Http\Controllers\Customer\DashboardController::class, 'showBooking'])->name('customer.bookings.show');
                Route::post('/bookings/{booking}/payment', [App\Http\Controllers\Customer\DashboardController::class, 'uploadPaymentProof'])->name('customer.payments.store');

                Route::get('/search', [SearchController::class, 'index'])->name('search.index');

                Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
                Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
                Route::delete('/cart/remove/{index}', [CartController::class, 'remove'])->name('cart.remove');

                Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
                Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
            });
        });

        // Public, no-auth — token is the security boundary for the
        // quotation link; the lead form is throttled against spam.
        Route::get('/contact', fn () => view('public.lead-form'))->name('leads.public-form');
        Route::post('/leads', [LeadCaptureController::class, 'store'])->middleware('throttle:10,1')->name('leads.public-store');
        Route::get('/q/{quotation}', [QuotationController::class, 'show'])->name('quotations.public-show');

        require __DIR__.'/auth.php';
    });

// Any URL that didn't match a {locale} segment falls through to here —
// either no locale at all ("/login") or an unrecognized one ("/fr/login").
// Both PRD-required cases redirect into a supported locale, preserving the
// rest of the path.
Route::fallback(function (Request $request) {
    $locale = session('locale', app()->getLocale());
    $supported = array_keys(config('laravellocalization.supportedLocales'));

    $segments = array_values(array_filter(explode('/', $request->path())));

    if (isset($segments[0]) && strlen($segments[0]) === 2 && ! in_array($segments[0], $supported, true)) {
        array_shift($segments);
    }

    return redirect('/'.$locale.($segments ? '/'.implode('/', $segments) : ''));
});
