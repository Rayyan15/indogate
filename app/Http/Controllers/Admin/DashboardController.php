<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Hotel;
use App\Models\FlightRoute;
use App\Models\Driver;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_bookings'    => Booking::count(),
            'pending_payments'  => Payment::where('status', 'pending')->count(),
            'total_customers'   => User::role('Customer')->count(),
            'confirmed_bookings'=> Booking::where('status', 'confirmed')->count(),
            'total_hotels'      => Hotel::count(),
            'total_flights'     => FlightRoute::count(),
            'total_drivers'     => Driver::count(),
        ];

        $recent_bookings = Booking::with(['customer.user'])
            ->latest()
            ->take(7)
            ->get();

        $recent_payments = Payment::with(['booking.customer.user'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_bookings', 'recent_payments'));
    }
}

