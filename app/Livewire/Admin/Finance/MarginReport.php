<?php

namespace App\Livewire\Admin\Finance;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Services\MarginReportService;
use App\Enums\BookingStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MarginReport extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(
            Auth::user()->can('report.margin.view') || Auth::user()->can('payment.verify'),
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

    public function render(): View
    {
        $query = PackageBooking::with(['quotation.items', 'quotation.lead', 'payments', 'refunds', 'vendorPayments'])
            ->where('status', '!=', BookingStatus::CANCELLED)
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('code', 'like', "%{$this->search}%")
                        ->orWhereHas('quotation.lead', fn ($lq) => $lq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->latest('departure_date');

        $bookings = $query->paginate(15);

        $reportService = new MarginReportService;

        $rows = [];
        foreach ($bookings->items() as $booking) {
            $rows[] = $reportService->computeBookingMargin($booking);
        }

        // Summary across all matched bookings in branch
        $allMatched = (clone $query)->lazyById(500);
        $summary = $reportService->computeSummary($allMatched);

        return view('livewire.admin.finance.margin-report', [
            'bookings' => $bookings,
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }
}
