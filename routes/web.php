<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HotelController;
use App\Http\Controllers\Admin\FlightRouteController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Routes
Route::middleware(['auth', 'role:Super Admin|CS Admin|Finance Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('permission:manage hotels')->resource('hotels', HotelController::class);
    Route::middleware('permission:manage flights')->resource('flights', FlightRouteController::class);
    Route::middleware('permission:manage vehicles')->resource('drivers', DriverController::class);
    Route::middleware('role:Super Admin')->resource('pricing-rules', \App\Http\Controllers\Admin\PricingRuleController::class)->names('pricing');
    
    Route::resource('bookings', BookingController::class)->only(['index', 'show']);
    Route::middleware('permission:manage bookings')->post('bookings/{booking}/assign-driver', [BookingController::class, 'assignDriver'])->name('bookings.assign-driver');
    
    Route::middleware('permission:verify payments')->group(function () {
        Route::resource('payments', PaymentController::class)->only(['index', 'show']);
        Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        
        // Secure payment proof download (Signed Route target)
        Route::get('payment-proofs/{proof}', [PaymentController::class, 'downloadProof'])
            ->name('payments.download-proof')
            ->middleware('signed');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Customer Routes
    Route::middleware('role:Customer')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Customer\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/bookings/{booking}', [\App\Http\Controllers\Customer\DashboardController::class, 'showBooking'])->name('customer.bookings.show');
        Route::post('/bookings/{booking}/payment', [\App\Http\Controllers\Customer\DashboardController::class, 'uploadPaymentProof'])->name('customer.payments.store');

        Route::get('/search', [\App\Http\Controllers\Customer\SearchController::class, 'index'])->name('search.index');
        
        Route::get('/cart', [\App\Http\Controllers\Customer\CartController::class, 'index'])->name('cart.index');
        Route::post('/cart/add', [\App\Http\Controllers\Customer\CartController::class, 'add'])->name('cart.add');
        Route::delete('/cart/remove/{index}', [\App\Http\Controllers\Customer\CartController::class, 'remove'])->name('cart.remove');
        
        Route::get('/checkout', [\App\Http\Controllers\Customer\CheckoutController::class, 'index'])->name('checkout.index');
        Route::post('/checkout', [\App\Http\Controllers\Customer\CheckoutController::class, 'store'])->name('checkout.store');
    });
});

require __DIR__.'/auth.php';
