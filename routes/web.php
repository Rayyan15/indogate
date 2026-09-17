<?php

use App\Domain\Catalog\Models\InventoryItem;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FlightRouteController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PricingRuleController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CheckoutController;
use App\Http\Controllers\Customer\SearchController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/'.app()->getLocale());
});

Route::prefix('{locale}')
    ->whereIn('locale', array_keys(config('laravellocalization.supportedLocales')))
    ->middleware('setLocale')
    ->group(function () {
        Route::get('/', function () {
            return view('welcome');
        });

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
