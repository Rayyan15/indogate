<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmed;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::with('booking.customer')->where('status', 'pending')->latest()->paginate(10);

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load('booking.customer');

        return view('admin.payments.show', compact('payment'));
    }

    public function verify(Request $request, Payment $payment)
    {
        $this->authorize('verify', $payment);

        $payment->update([
            'status' => 'verified',
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        // Update booking status
        $payment->booking->update(['status' => 'confirmed']);

        // Send email notification via queue
        if ($payment->booking->customer && $payment->booking->customer->user) {
            Mail::to($payment->booking->customer->user->email)->send(new BookingConfirmed($payment->booking));
        }

        activity()
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Payment verified');

        return redirect()->route('admin.payments.index')->with('success', 'Payment verified. Booking confirmed. Customer notified.');
    }

    public function reject(Payment $payment)
    {
        $this->authorize('verify', $payment);

        $payment->update(['status' => 'rejected']);
        $payment->booking->update(['status' => 'payment_rejected']);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($payment)
            ->log('Payment rejected');

        return redirect()->route('admin.payments.index')->with('success', 'Payment rejected.');
    }

    public function downloadProof(Request $request, $proof)
    {
        $filePath = null;
        $payment = null;
        if (is_numeric($proof)) {
            $payment = \App\Domain\Finance\Models\Payment::withoutGlobalScope(\App\Support\Branch\BranchScope::class)->find($proof);
            $filePath = $payment?->proof_file;
        }

        if (! $filePath || ! Storage::disk('local')->exists($filePath)) {
            abort(404, 'Bukti pembayaran tidak ditemukan.');
        }

        if (Auth::check() && $payment) {
            activity('finance')
                ->causedBy(Auth::user())
                ->performedOn($payment)
                ->log('Melihat atau mengunduh bukti pembayaran');
        }

        return Storage::disk('local')->response($filePath);
    }

    // Unused resource methods
    public function create() {}

    public function store(Request $request) {}

    public function edit(Payment $payment) {}

    public function update(Request $request, Payment $payment) {}

    public function destroy(Payment $payment) {}
}
