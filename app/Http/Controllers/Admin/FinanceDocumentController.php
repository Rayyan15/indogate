<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Http\Controllers\Controller;
use App\Support\Branch\CurrentBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FinanceDocumentController extends Controller
{
    public function invoice(Request $request, PackageBooking $packageBooking)
    {
        $booking = $packageBooking;

        // Allow signed access or authenticated user with view access
        if (! $request->hasValidSignature()) {
            abort_unless(Auth::check() && (Auth::user()->can('payment.verify') || Auth::user()->can('booking.manage')), 403);
            abort_unless((int) $booking->branch_id === (int) CurrentBranch::id(), 403);
        }

        activity('finance')
            ->causedBy(Auth::user())
            ->performedOn($booking)
            ->log('Viewed booking invoice');

        $locale = $request->query('lang', app()->getLocale());

        return view('pdf.invoice', [
            'booking' => $booking->load(['quotation.items', 'guests', 'payments' => fn ($q) => $q->where('status', 'verified')]),
            'locale' => $locale,
        ]);
    }

    public function receipt(Request $request, Payment $payment)
    {
        if (! $request->hasValidSignature()) {
            abort_unless(Auth::check() && (Auth::user()->can('payment.verify') || Auth::user()->can('booking.manage')), 403);
            abort_unless((int) $payment->branch_id === (int) CurrentBranch::id(), 403);
        }

        activity('finance')
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Viewed payment receipt');

        $locale = $request->query('lang', app()->getLocale());

        return view('pdf.receipt', [
            'payment' => $payment->load(['booking.guests', 'booking.quotation', 'branch', 'verifiedByUser']),
            'locale' => $locale,
        ]);
    }

    public function downloadProof(Request $request, Payment $payment)
    {
        abort_unless($request->hasValidSignature(), 403);

        if (! $payment->proof_file || ! Storage::disk('local')->exists($payment->proof_file)) {
            abort(404);
        }

        activity('finance')
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Downloaded payment proof');

        return Storage::disk('local')->download($payment->proof_file);
    }
}
