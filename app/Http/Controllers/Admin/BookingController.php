<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmed;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Storefront (cart) bookings. Payment verification here is the manual
 * bridge until a payment gateway replaces it — see Booking model docblock.
 */
class BookingController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::with('customer')->latest()->paginate(10);

        return view('admin.bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        $booking->load('customer.user', 'items', 'paymentVerifier');

        return view('admin.bookings.show', compact('booking'));
    }

    public function paymentProof(Booking $booking)
    {
        $this->authorize('view', $booking);

        abort_unless($booking->payment_proof_path && Storage::disk('local')->exists($booking->payment_proof_path), 404);

        activity('finance')->causedBy(Auth::user())->performedOn($booking)->log('Melihat bukti pembayaran booking storefront');

        return Storage::disk('local')->response($booking->payment_proof_path);
    }

    public function verifyPayment(Booking $booking)
    {
        $this->authorize('verifyPayment', $booking);

        $verified = DB::transaction(function () use ($booking) {
            // Lock + re-check so a double click can't verify twice.
            $locked = Booking::withoutGlobalScopes()->lockForUpdate()->findOrFail($booking->id);
            if ($locked->status !== Booking::STATUS_PAYMENT_SUBMITTED) {
                return false;
            }

            $locked->update([
                'status' => Booking::STATUS_CONFIRMED,
                'payment_verified_by' => Auth::id(),
                'payment_verified_at' => now(),
            ]);

            return true;
        });

        if (! $verified) {
            return back()->with('error', __('admin.bookings.payment_not_pending'));
        }

        $booking->refresh()->load('customer.user');
        if ($email = $booking->customer?->user?->email) {
            Mail::to($email)->locale(app()->getLocale())->queue(new BookingConfirmed($booking));
        }

        activity()->causedBy(Auth::user())->performedOn($booking)->log('Storefront payment verified');

        return redirect()->route('admin.bookings.show', $booking)->with('success', __('admin.bookings.payment_verified'));
    }

    public function rejectPayment(Request $request, Booking $booking)
    {
        $this->authorize('verifyPayment', $booking);

        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $rejected = DB::transaction(function () use ($booking, $data) {
            $locked = Booking::withoutGlobalScopes()->lockForUpdate()->findOrFail($booking->id);
            if ($locked->status !== Booking::STATUS_PAYMENT_SUBMITTED) {
                return false;
            }

            $locked->update([
                'status' => Booking::STATUS_PAYMENT_REJECTED,
                'payment_rejection_reason' => $data['reason'],
            ]);

            return true;
        });

        if (! $rejected) {
            return back()->with('error', __('admin.bookings.payment_not_pending'));
        }

        activity()->causedBy(Auth::user())->performedOn($booking)->log('Storefront payment rejected');

        return redirect()->route('admin.bookings.show', $booking)->with('success', __('admin.bookings.payment_rejected'));
    }
}
