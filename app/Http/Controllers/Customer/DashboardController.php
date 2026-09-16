<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentProof;
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

        $booking->load('items', 'payments.proofs');

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

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
            'status' => 'pending',
        ]);

        $path = $request->file('proof')->store('payment_proofs', 'local');

        PaymentProof::create([
            'payment_id' => $payment->id,
            'file_path' => $path,
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Payment proof uploaded successfully. Waiting for admin verification.');
    }
}
