<?php

namespace App\Livewire\Admin\Finance;

use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AccountsReceivableList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'unpaid'; // unpaid, overdue, settled, all

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'unpaid'],
    ];

    public function mount(): void
    {
        abort_unless(
            Auth::user()->can('payment.verify') || Auth::user()->can('booking.manage'),
            403
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function invoiceUrl(PackageBooking $booking): string
    {
        return route('admin.finance.invoice', ['packageBooking' => $booking->id]);
    }

    public function render(): View
    {
        $query = PackageBooking::with(['quotation.lead', 'payments' => fn ($q) => $q->where('status', 'verified')])
            ->where('status', '!=', BookingStatus::CANCELLED);

        if ($this->search !== '') {
            $term = addcslashes($this->search, '%_\\');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhereHas('quotation.lead', fn ($lq) => $lq->where('name', 'like', "%{$term}%"));
            });
        }

        // Apply receivables status filter
        if ($this->statusFilter === 'unpaid') {
            // has remaining balance: status confirmed or partially_paid
            $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID]);
        } elseif ($this->statusFilter === 'overdue') {
            // departure_date in the past or today, but still confirmed or partially_paid
            $query->whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID])
                ->whereDate('departure_date', '<=', now()->toDateString());
        } elseif ($this->statusFilter === 'settled') {
            $query->whereIn('status', [BookingStatus::PAID, BookingStatus::IN_PROGRESS, BookingStatus::COMPLETED]);
        }

        $bookings = $query->orderBy('departure_date')->paginate(15);

        // Overall branch receivables statistics
        $allUnpaidBookings = PackageBooking::whereIn('status', [BookingStatus::CONFIRMED, BookingStatus::PARTIALLY_PAID])
            ->with(['payments' => fn ($q) => $q->where('status', 'verified')])
            ->get();

        $totalReceivableCount = $allUnpaidBookings->count();
        $totalReceivableMinor = 0;
        foreach ($allUnpaidBookings as $b) {
            $totalReceivableMinor += $b->remainingBalanceMinor();
        }

        return view('livewire.admin.finance.accounts-receivable-list', [
            'bookings' => $bookings,
            'totalReceivableCount' => $totalReceivableCount,
            'totalReceivableMinor' => $totalReceivableMinor,
        ]);
    }
}
