<?php

namespace App\Livewire\Admin\Desk;

use App\Domain\Booking\AwaitingPaymentBookings;
use App\Domain\Booking\Models\BookingStatusHistory;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Lead\Models\Lead;
use App\Domain\Packaging\Models\Package;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Branch control desk for the head's right hand: today's operations, CS
 * supervision, catalog health and exceptions. Queries run through BranchScope.
 */
class AdminDesk extends Component
{
    private const LIST_LIMIT = 8;

    private const DRIVER_WINDOW_DAYS = 7;

    private const RATE_WINDOW_DAYS = 30;

    /** @var array<int, int|string> */
    public array $selectedLeads = [];

    public ?int $assignTo = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('desk.admin'), 403);
    }

    public function assign(): void
    {
        abort_unless(Auth::user()?->can('lead.manage') && Auth::user()->can('desk.admin'), 403);

        $this->validate([
            'selectedLeads' => ['required', 'array'],
            'assignTo' => ['required', 'integer', 'in:'.$this->assignableUsers()->pluck('id')->implode(',')],
        ]);

        // BranchScope keeps other branches' leads out of this query; each update is logged by Lead.
        Lead::whereIn('id', $this->selectedLeads)
            ->whereNull('assigned_to')
            ->get()
            ->each(fn (Lead $lead) => $lead->update(['assigned_to' => $this->assignTo]));

        $this->reset('selectedLeads', 'assignTo');
    }

    public function render(): View
    {
        return view('livewire.admin.desk.admin-desk', [
            'kpi' => Auth::user()->can('report.view') ? $this->kpi() : null,
            'departures' => $this->departures(),
            'withoutDriver' => $this->withoutDriver(),
            'unpaid' => app(AwaitingPaymentBookings::class)->get(self::DRIVER_WINDOW_DAYS),
            'team' => $this->team(),
            'unassigned' => $this->unassignedLeads(),
            'assignable' => $this->assignableUsers(),
            'noPrice' => $this->packagesWithoutPrice(),
            'expiringRates' => $this->packagesWithExpiringRates(),
            'pendingPayments' => Payment::pending()->count(),
            'cancellations' => $this->recentCancellations(),
        ]);
    }

    /** @return array<string, mixed> */
    private function kpi(): array
    {
        $metrics = app(DashboardMetricsService::class);
        [$from, $to] = $metrics->resolveDateRange(DashboardMetricsService::PERIOD_THIS_MONTH);
        $branchId = CurrentBranch::id();

        return [
            'bookings' => $metrics->getBookingsSummary($branchId, $from, $to)['total'],
            'revenue' => $metrics->getFinancialMetrics($branchId, $from, $to)['gross_revenue_idr'],
            'funnel' => $metrics->getLeadFunnelMetrics($branchId, $from, $to),
        ];
    }

    private function activeBookings(): Builder
    {
        return PackageBooking::with('quotation.lead')
            ->whereNotIn('status', [BookingStatus::CANCELLED, BookingStatus::EXPIRED, BookingStatus::DRAFT]);
    }

    private function departures(): Collection
    {
        return $this->activeBookings()
            ->whereDate('departure_date', '>=', today())
            ->whereDate('departure_date', '<=', today()->addDay())
            ->orderBy('departure_date')->limit(self::LIST_LIMIT)->get();
    }

    private function withoutDriver(): Collection
    {
        return $this->activeBookings()
            ->whereDate('departure_date', '>=', today())
            ->whereDate('departure_date', '<=', today()->addDays(self::DRIVER_WINDOW_DAYS))
            ->whereDoesntHave('activeAssignment')
            ->orderBy('departure_date')->limit(self::LIST_LIMIT)->get();
    }

    /**
     * Active CS of this branch: CS Admin role or a direct desk.cs grant. Super
     * Admin/Admin are excluded (Super Admin passes desk.cs only via the gate bypass).
     */
    private function csStaff(): \Illuminate\Database\Eloquent\Builder
    {
        return User::where('branch_id', CurrentBranch::id())
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn (Builder $r) => $r->whereIn('name', ['Super Admin', 'Admin']))
            ->where(fn (Builder $q) => $q->role('CS Admin')->orWhereHas('permissions', fn (Builder $p) => $p->where('name', 'desk.cs')))
            ->orderBy('name');
    }

    /** Active CS of this branch who work leads (same rule as the lead form). */
    private function assignableUsers(): Collection
    {
        return $this->csStaff()
            ->permission('lead.manage')
            ->get(['id', 'name']);
    }

    /** CS staff of this branch with their workload. */
    private function team(): Collection
    {
        $monthStart = now(CurrentBranch::model()?->timezone ?: 'Asia/Jakarta')->startOfMonth()->setTimezone(config('app.timezone'));

        return $this->csStaff()
            ->get(['id', 'name'])
            ->each(function (User $cs) use ($monthStart) {
                $cs->active_leads = Lead::where('assigned_to', $cs->id)->whereNotIn('status', [LeadStatus::WON, LeadStatus::LOST])->count();
                $cs->overdue_follow_ups = Lead::where('assigned_to', $cs->id)->followUpOverdue()->count();
                $cs->month_bookings = PackageBooking::where('created_by', $cs->id)->where('created_at', '>=', $monthStart)->count();
            });
    }

    private function unassignedLeads(): Collection
    {
        return Lead::whereNull('assigned_to')
            ->whereNotIn('status', [LeadStatus::WON, LeadStatus::LOST])
            ->oldest()->limit(self::LIST_LIMIT * 2)->get();
    }

    private function packagesWithoutPrice(): Collection
    {
        return Package::published()
            ->where(fn (Builder $q) => $q->whereNull('starting_price_idr')->orWhere('starting_price_idr', '<=', 0))
            ->orderBy('id')->limit(self::LIST_LIMIT)->get();
    }

    /** Published packages with an item whose newest rate ends within the window (or already ended). */
    private function packagesWithExpiringRates(): Collection
    {
        $limit = today()->addDays(self::RATE_WINDOW_DAYS);

        return Package::published()
            ->whereHas('items.inventoryItem', fn (Builder $item) => $item
                ->whereHas('rates')
                ->whereDoesntHave('rates', fn (Builder $rate) => $rate->whereDate('valid_to', '>', $limit)))
            ->orderBy('id')->limit(self::LIST_LIMIT)->get();
    }

    private function recentCancellations(): Collection
    {
        return BookingStatusHistory::with(['booking.quotation.lead', 'user'])
            ->where('to_status', BookingStatus::CANCELLED)
            ->where('created_at', '>=', now()->subDays(7))
            ->whereHas('booking')
            ->latest('created_at')->limit(self::LIST_LIMIT)->get();
    }
}
