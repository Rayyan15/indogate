<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\StorefrontProofUploaded;
use App\Support\Branch\BranchScope;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $customerId = Auth::user()->customer?->id;

        $bookings = Booking::withoutGlobalScope(BranchScope::class)
            ->where('customer_id', $customerId ?? 0)
            ->latest()
            ->paginate(10);

        return view('customer.dashboard.index', compact('bookings'));
    }

    public function showBooking(Booking $booking)
    {
        $this->ensureOwnedByCurrentCustomer($booking);

        $booking->load('items');

        return view('customer.dashboard.booking', compact('booking'));
    }

    public function uploadPaymentProof(Request $request, Booking $booking)
    {
        $this->ensureOwnedByCurrentCustomer($booking);

        abort_unless($booking->canSubmitPayment(), 422, __('customer.booking.payment_not_accepted'));

        $request->validate([
            'proof' => 'required|image|max:2048',
        ]);

        $path = $request->file('proof')->store('payment-proofs', 'local');

        $booking->update([
            'status' => Booking::STATUS_PAYMENT_SUBMITTED,
            'payment_proof_path' => $path,
            'payment_submitted_at' => now(),
            'payment_rejection_reason' => null,
        ]);

        Notify::send(new StorefrontProofUploaded(['code' => $booking->booking_number], Notify::url('admin.bookings.show', ['booking' => $booking->id]), $booking->branch_id), $booking->id.'@'.$booking->payment_submitted_at->timestamp, 'payment.verify');

        return back()->with('success', __('customer.booking.proof_uploaded'));
    }

    /**
     * Strict ownership: a user without a customer row owns nothing. Never
     * fall back to the user id — users and customers are separate key spaces.
     */
    private function ensureOwnedByCurrentCustomer(Booking $booking): void
    {
        $customerId = Auth::user()->customer?->id;

        abort_unless($customerId !== null && (int) $booking->customer_id === (int) $customerId, 403);
    }
}
