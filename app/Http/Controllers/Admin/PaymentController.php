<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('booking.customer')->where('status', 'pending')->latest()->paginate(10);
        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $payment->load('booking.customer', 'proofs');
        return view('admin.payments.show', compact('payment'));
    }

    public function verify(Request $request, Payment $payment)
    {
        $payment->update([
            'status'      => 'verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Update booking status
        $payment->booking->update(['status' => 'confirmed']);

        // Send email notification via queue
        if ($payment->booking->customer && $payment->booking->customer->user) {
            \Illuminate\Support\Facades\Mail::to($payment->booking->customer->user->email)->send(new \App\Mail\BookingConfirmed($payment->booking));
        }

        activity()
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Payment verified');

        return redirect()->route('admin.payments.index')->with('success', 'Payment verified. Booking confirmed. Customer notified.');
    }

    public function reject(Payment $payment)
    {
        $payment->update(['status' => 'rejected']);
        $payment->booking->update(['status' => 'payment_rejected']);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Payment rejected');

        return redirect()->route('admin.payments.index')->with('success', 'Payment rejected.');
    }

    // Unused resource methods
    public function create() {}
    public function store(Request $request) {}
    public function edit(Payment $payment) {}
    public function update(Request $request, Payment $payment) {}
    public function destroy(Payment $payment) {}
}
