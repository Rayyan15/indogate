<?php

namespace App\Livewire\Admin\Booking;

use App\Domain\Booking\Models\PackageBooking;
use App\Enums\BookingStatus;
use App\Livewire\Concerns\Sortable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PackageBookingList extends Component
{
    use Sortable, WithPagination;

    private const SORTABLE_FIELDS = ['code', 'departure_date', 'status', 'created_at'];

    /** SSR default only — real preference read from localStorage, same pattern as LeadList::$viewMode. */
    public string $viewMode = 'list';

    public string $search = '';

    public string $statusFilter = '';

    /** Y-m format, drives the calendar view month. */
    public string $calendarMonth = '';

    public function mount(): void
    {
        $this->authorize('viewAny', PackageBooking::class);
        $this->calendarMonth = now()->format('Y-m');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function previousMonth(): void
    {
        $this->calendarMonth = now()->parse($this->calendarMonth.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->calendarMonth = now()->parse($this->calendarMonth.'-01')->addMonth()->format('Y-m');
    }

    #[On('booking-saved')]
    public function refresh(): void {}

    private function filteredQuery()
    {
        return PackageBooking::query()
            ->with('quotation.lead')
            ->when($this->search, fn ($q) => $q->where('code', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter));
    }

    public function render(): View
    {
        $bookings = $this->applySort($this->filteredQuery(), self::SORTABLE_FIELDS, defaultField: 'departure_date')
            ->paginate(15);

        $monthStart = now()->parse($this->calendarMonth.'-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $calendarBookings = $this->filteredQuery()
            ->whereBetween('departure_date', [$monthStart, $monthEnd])
            ->get()
            ->groupBy(fn (PackageBooking $b) => $b->departure_date->format('Y-m-d'));

        return view('livewire.admin.booking.package-booking-list', [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'calendarBookings' => $calendarBookings,
        ]);
    }
}
