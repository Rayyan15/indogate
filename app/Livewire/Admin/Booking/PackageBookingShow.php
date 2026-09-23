<?php

namespace App\Livewire\Admin\Booking;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Exceptions\InvalidBookingTransitionException;
use App\Domain\Booking\Models\BookingGuest;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Exceptions\SelfApprovalException;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Fleet\Exceptions\ScheduleConflictException;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Fleet\Services\AssignmentService;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Enums\BookingStatus;
use App\Jobs\GenerateBookingVoucher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class PackageBookingShow extends Component
{
    use WithFileUploads;

    /** An id, not the Eloquent model — see App\Livewire\Admin\Lead\LeadForm::$leadId docblock. */
    public int $bookingId;

    // New-guest form
    public string $guest_name = '';

    public string $guest_passport_number = '';

    public string $guest_nationality = '';

    public bool $guest_is_lead = false;

    public $guest_passport_file = null;

    // Notes
    public string $note_text = '';

    // Cancellation
    public bool $showCancelModal = false;

    public string $cancel_reason = '';

    // Voucher export
    public bool $exportPending = false;

    public ?string $downloadUrl = null;

    // Driver & Fleet Assignment
    public string $driver_gender_preference = '';

    public ?int $selected_driver_id = null;

    public ?int $selected_vehicle_id = null;

    public ?string $assignment_date_from = null;

    public ?string $assignment_date_to = null;

    public string $assignment_notes = '';

    public bool $showCancelAssignmentModal = false;

    public ?int $cancellingAssignmentId = null;

    public string $cancel_assignment_reason = '';

    // Finance & Payment
    public bool $showPaymentModal = false;

    public string $payment_type = 'down_payment';

    public int $payment_amount_minor = 0;

    public string $payment_currency = 'IDR';

    public float $payment_fx_rate = 1.0;

    public string $payment_channel = 'manual_transfer';

    public string $payment_notes = '';

    public $paymentProofFile = null;

    public bool $showRefundModal = false;

    public ?int $refundingPaymentId = null;

    public int $refund_amount_minor = 0;

    public string $refund_reason = '';

    public ?string $financeError = null;

    public ?string $financeSuccess = null;

    public function mount(PackageBooking $packageBooking): void
    {
        $this->authorize('view', $packageBooking);
        $this->bookingId = $packageBooking->id;
        $this->driver_gender_preference = $packageBooking->driver_gender_preference ?? '';
        $this->assignment_date_from = $packageBooking->departure_date->toDateString();
        $this->assignment_date_to = ($packageBooking->return_date ?? $packageBooking->departure_date)->toDateString();
        $this->payment_currency = $packageBooking->currency;
    }

    private function booking(): PackageBooking
    {
        return PackageBooking::with([
            'guests',
            'notes.user',
            'statusHistories.user',
            'quotation.lead',
            'activeAssignment.driver',
            'activeAssignment.vehicle',
            'assignments.driver',
            'assignments.vehicle',
        ])->findOrFail($this->bookingId);
    }

    public function addGuest(): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $this->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_passport_number' => ['nullable', 'string', 'max:50'],
            'guest_nationality' => ['nullable', 'string', 'max:100'],
            'guest_passport_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $path = null;
        if ($this->guest_passport_file) {
            $filename = 'passport-'.now()->timestamp.'.'.$this->guest_passport_file->getClientOriginalExtension();
            $path = $this->guest_passport_file->storeAs("booking-guests/{$booking->id}", $filename, 'local');
        }

        $booking->guests()->create([
            'name' => $this->guest_name,
            'passport_number' => $this->guest_passport_number ?: null,
            'nationality' => $this->guest_nationality ?: null,
            'is_lead_guest' => $this->guest_is_lead,
            'passport_file' => $path,
        ]);

        $this->reset(['guest_name', 'guest_passport_number', 'guest_nationality', 'guest_is_lead', 'guest_passport_file']);
    }

    public function removeGuest(int $guestId): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $guest = $booking->guests()->findOrFail($guestId);
        if ($guest->passport_file) {
            Storage::disk('local')->delete($guest->passport_file);
        }
        $guest->delete();
    }

    public function passportDownloadUrl(BookingGuest $guest): ?string
    {
        if (! $guest->passport_file) {
            return null;
        }

        return URL::temporarySignedRoute(
            'admin.package-bookings.guests.passport-download',
            now()->addMinutes(15),
            ['locale' => app()->getLocale(), 'guest' => $guest->id],
        );
    }

    public function addNote(): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $this->validate(['note_text' => ['required', 'string', 'max:1000']]);

        $booking->notes()->create(['user_id' => Auth::id(), 'note' => $this->note_text]);
        $this->reset('note_text');
    }

    public function transitionTo(string $status): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $target = BookingStatus::tryFrom($status);

        // Payment-driven statuses are set only by PaymentService (BF-04);
        // cancellation needs a reason and goes through cancel().
        if (! $target || in_array($target, [BookingStatus::PARTIALLY_PAID, BookingStatus::PAID, BookingStatus::CANCELLED], true)) {
            $this->addError('transition', __('booking.show.transition_denied'));

            return;
        }

        try {
            (new BookingStateMachine)->transition($booking, $target, actor: Auth::user());
        } catch (InvalidBookingTransitionException) {
            $this->addError('transition', __('booking.show.transition_denied'));
        }
    }

    public function cancel(): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $this->validate(['cancel_reason' => ['required', 'string', 'max:500']]);

        try {
            (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, $this->cancel_reason, Auth::user());
        } catch (InvalidBookingTransitionException) {
            $this->addError('transition', __('booking.show.transition_denied'));

            return;
        }

        // Money already received needs an explicit refund decision (BF-13).
        if ($booking->totalPaidMinor() > 0) {
            $this->financeError = __('finance.cancelled_with_paid_balance');
        }

        $this->showCancelModal = false;
        $this->reset('cancel_reason');
    }

    public function exportVoucher(string $locale): void
    {
        $booking = $this->booking();
        $this->authorize('view', $booking);

        GenerateBookingVoucher::dispatch($booking->id, $locale, Auth::id());
        $this->exportPending = true;
        $this->downloadUrl = null;
    }

    public function checkVoucherReady(): void
    {
        if (! $this->exportPending) {
            return;
        }

        foreach (['en', 'id', 'ar'] as $locale) {
            $path = Cache::get(GenerateBookingVoucher::cacheKey($this->bookingId, Auth::id(), $locale));
            if ($path) {
                $this->exportPending = false;
                $this->downloadUrl = URL::temporarySignedRoute('admin.package-bookings.voucher-download', now()->addMinutes(30), [
                    'locale' => app()->getLocale(),
                    'packageBooking' => $this->bookingId,
                    'path' => basename($path),
                ]);
                $this->dispatch('voucher-ready');

                return;
            }
        }
    }

    public function updateGenderPreference(string $pref): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $val = in_array($pref, ['male', 'female']) ? $pref : null;
        $booking->update(['driver_gender_preference' => $val]);
        $this->driver_gender_preference = $val ?? '';
        $this->selected_driver_id = null;
    }

    public function assignDriver(): void
    {
        abort_unless(Auth::user()->can('driver.assign'), 403);

        $booking = $this->booking();

        $this->validate([
            'selected_driver_id' => ['required', 'integer'],
            'selected_vehicle_id' => ['nullable', 'integer'],
            'assignment_date_from' => ['required', 'date'],
            'assignment_date_to' => ['required', 'date', 'after_or_equal:assignment_date_from'],
            'assignment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            (new AssignmentService)->assign(
                $booking,
                $this->selected_driver_id,
                $this->selected_vehicle_id,
                $this->assignment_date_from,
                $this->assignment_date_to,
                $this->assignment_notes ?: null
            );

            $this->reset(['selected_driver_id', 'selected_vehicle_id', 'assignment_notes']);
        } catch (ScheduleConflictException $e) {
            $this->addError('driver_assignment', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addError('driver_assignment', $e->getMessage());
        }
    }

    public function openCancelAssignmentModal(int $assignmentId): void
    {
        $this->cancellingAssignmentId = $assignmentId;
        $this->cancel_assignment_reason = '';
        $this->showCancelAssignmentModal = true;
    }

    public function cancelAssignment(): void
    {
        abort_unless(Auth::user()->can('driver.assign'), 403);

        $this->validate([
            'cancel_assignment_reason' => ['required', 'string', 'max:500'],
        ]);

        $assignment = DriverAssignment::findOrFail($this->cancellingAssignmentId);
        $this->authorize('update', $assignment);

        (new AssignmentService)->cancel(
            $assignment,
            $this->cancel_assignment_reason
        );

        $this->showCancelAssignmentModal = false;
        $this->reset(['cancellingAssignmentId', 'cancel_assignment_reason']);
    }

    public function dutyLetterUrl(DriverAssignment $assignment): string
    {
        return URL::temporarySignedRoute(
            'admin.fleet.assignments.duty-letter',
            now()->addMinutes(60),
            ['assignment' => $assignment->id]
        );
    }

    public function openRecordPaymentModal(): void
    {
        $this->reset(['paymentProofFile', 'payment_notes', 'financeError', 'financeSuccess']);
        $booking = $this->booking();
        $this->payment_type = $booking->totalPaidMinor() === 0 ? 'down_payment' : 'installment';
        $this->payment_currency = $booking->currency;
        $this->payment_amount_minor = $booking->remainingBalanceMinor();
        $this->updatedPaymentCurrency();
        $this->showPaymentModal = true;
    }

    public function updatedPaymentCurrency(): void
    {
        if (strtoupper($this->payment_currency) === 'IDR') {
            $this->payment_fx_rate = 1.0;
        } else {
            // 0 = no rate yet; PaymentService refuses rather than using 1.0.
            $rate = ExchangeRate::currentFor(strtoupper($this->payment_currency));
            $this->payment_fx_rate = $rate ? (float) $rate->rate : 0.0;
        }
    }

    public function recordPayment(): void
    {
        $this->reset(['financeError', 'financeSuccess']);
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $this->validate([
            'payment_type' => ['required', Rule::in(PaymentService::TYPES)],
            'payment_amount_minor' => ['required', 'integer', 'min:1'],
            'payment_currency' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code')],
            'payment_fx_rate' => ['nullable', 'numeric', 'min:0'],
            'payment_channel' => ['required', Rule::in(PaymentService::channels())],
            'payment_notes' => ['nullable', 'string', 'max:500'],
            'paymentProofFile' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            (new PaymentService)->recordPayment(
                $booking,
                $this->payment_amount_minor,
                $this->payment_currency,
                $this->payment_type,
                $this->payment_channel,
                $this->payment_fx_rate,
                $this->paymentProofFile,
                $this->payment_notes ?: null,
                Auth::user()
            );

            $this->showPaymentModal = false;
            $this->financeSuccess = __('finance.payment_recorded_success');
        } catch (\Exception $e) {
            $this->financeError = $e->getMessage();
        }
    }

    public function verifyBookingPayment(int $paymentId): void
    {
        $this->reset(['financeError', 'financeSuccess']);
        $payment = Payment::findOrFail($paymentId);
        $this->authorize('verify', $payment);

        try {
            (new PaymentService)->verifyPayment($payment, Auth::user());
            $this->financeSuccess = __('finance.payment_verified_success');
        } catch (SelfApprovalException $e) {
            $this->financeError = $e->getMessage();
        } catch (PaymentVerificationException $e) {
            $this->financeError = $e->getMessage();
        } catch (\Exception $e) {
            $this->financeError = $e->getMessage();
        }
    }

    public function openRefundModal(int $paymentId): void
    {
        $this->reset(['financeError', 'financeSuccess']);
        $payment = Payment::findOrFail($paymentId);
        $this->authorize('refund', $payment);
        $this->refundingPaymentId = $payment->id;
        $this->refund_amount_minor = $payment->amount_minor;
        $this->refund_reason = '';
        $this->showRefundModal = true;
    }

    public function processRefund(): void
    {
        $this->reset(['financeError', 'financeSuccess']);
        $this->validate([
            'refund_amount_minor' => ['required', 'integer', 'min:1'],
            'refund_reason' => ['required', 'string', 'max:500'],
        ]);

        $payment = Payment::findOrFail($this->refundingPaymentId);
        $this->authorize('refund', $payment);

        try {
            (new PaymentService)->refundPayment(
                $payment,
                $this->refund_amount_minor,
                $this->refund_reason,
                Auth::user()
            );

            $this->showRefundModal = false;
            $this->financeSuccess = __('finance.refund_processed_success');
            $this->reset(['refundingPaymentId', 'refund_amount_minor', 'refund_reason']);
        } catch (\Exception $e) {
            $this->financeError = $e->getMessage();
        }
    }

    public function invoiceUrl(): string
    {
        return route('admin.finance.invoice', ['packageBooking' => $this->bookingId]);
    }

    public function receiptUrl(Payment $payment): string
    {
        return route('admin.finance.receipt', ['payment' => $payment->id]);
    }

    public function proofUrl(Payment $payment): ?string
    {
        if (! $payment->proof_file) {
            return null;
        }

        return URL::temporarySignedRoute(
            'admin.finance.proofs.download',
            now()->addMinutes(30),
            ['payment' => $payment->id]
        );
    }

    public function render(): View
    {
        $booking = $this->booking();
        $stateMachine = new BookingStateMachine;

        $assignmentService = new AssignmentService;
        $suggestedDrivers = $assignmentService->suggestDrivers(
            $booking,
            $this->assignment_date_from,
            $this->assignment_date_to
        );
        $genderWarning = $assignmentService->getSuggestionWarning($booking, $suggestedDrivers);

        $availableVehicles = $assignmentService->suggestVehicles(
            $booking,
            $this->assignment_date_from,
            $this->assignment_date_to
        );

        return view('livewire.admin.booking.package-booking-show', [
            'booking' => $booking,
            // Payment-driven statuses are set by PaymentService only (see transitionTo).
            'nextStatuses' => array_values(array_filter(
                $stateMachine->nextStatuses($booking->status),
                fn (BookingStatus $s) => ! in_array($s, [BookingStatus::PARTIALLY_PAID, BookingStatus::PAID], true),
            )),
            'suggestedDrivers' => $suggestedDrivers,
            'genderWarning' => $genderWarning,
            'availableVehicles' => $availableVehicles,
            'bookingPayments' => $booking->payments()->with(['creator', 'verifiedByUser'])->latest('id')->get(),
        ]);
    }
}
