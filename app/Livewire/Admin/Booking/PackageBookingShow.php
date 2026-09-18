<?php

namespace App\Livewire\Admin\Booking;

use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\Exceptions\InvalidBookingTransitionException;
use App\Domain\Booking\Models\BookingGuest;
use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use App\Jobs\GenerateBookingVoucher;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

    public function mount(PackageBooking $packageBooking): void
    {
        $this->authorize('view', $packageBooking);
        $this->bookingId = $packageBooking->id;
    }

    private function booking(): PackageBooking
    {
        return PackageBooking::with(['guests', 'notes.user', 'statusHistories.user', 'quotation.lead'])
            ->findOrFail($this->bookingId);
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

        try {
            (new BookingStateMachine)->transition($booking, BookingStatus::from($status), actor: Auth::user());
        } catch (InvalidBookingTransitionException) {
            $this->addError('transition', __('booking.show.transition_denied'));
        }
    }

    public function cancel(): void
    {
        $booking = $this->booking();
        $this->authorize('update', $booking);

        $this->validate(['cancel_reason' => ['required', 'string', 'max:500']]);

        (new BookingStateMachine)->transition($booking, BookingStatus::CANCELLED, $this->cancel_reason, Auth::user());

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

    public function render(): View
    {
        $booking = $this->booking();
        $stateMachine = new BookingStateMachine;

        return view('livewire.admin.booking.package-booking-show', [
            'booking' => $booking,
            'nextStatuses' => $stateMachine->nextStatuses($booking->status),
        ]);
    }
}
