<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $customerId = Auth::user()->customer->id ?? Auth::id();
        $bookings = Booking::where('customer_id', $customerId)->latest()->paginate(10);

        return view('customer.dashboard.index', compact('bookings'));
    }

    public function showBooking(Booking $booking)
    {
        // Ensure the booking belongs to the current user
        $customerId = Auth::user()->customer->id ?? Auth::id();
        if ($booking->customer_id !== $customerId) {
            abort(403);
        }

        $booking->load('items', 'payments');

        return view('customer.dashboard.booking', compact('booking'));
    }

    public function uploadPaymentProof(Request $request, Booking $booking)
    {
        $customerId = Auth::user()->customer->id ?? Auth::id();
        if ($booking->customer_id !== $customerId) {
            abort(403);
        }

        $request->validate([
            'proof' => 'required|image|max:2048',
        ]);

        $path = $request->file('proof')->store('payment-proofs', 'local');

        $booking->update([
            'status' => 'payment_submitted',
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi admin.');
    }
}
