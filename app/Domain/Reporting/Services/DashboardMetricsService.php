<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\MarginReportService;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Packaging\Models\Package;
use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Support\Branch\BranchScope;
use App\Support\Branch\CurrentBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardMetricsService
{
    public const PERIOD_ALL_TIME = 'all_time';

    public const PERIOD_TODAY = 'today';

    public const PERIOD_LAST_7_DAYS = 'last_7_days';

    public const PERIOD_THIS_MONTH = 'this_month';

    public const PERIOD_LAST_MONTH = 'last_month';

    public const PERIOD_THIS_QUARTER = 'this_quarter';

    public const PERIOD_THIS_YEAR = 'this_year';

    public const PERIOD_CUSTOM = 'custom';

    /**
     * Resolve start and end dates from period string.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function resolveDateRange(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        // "Today"/"this month" are the branch's local calendar (Bali = WITA,
        // Jakarta = WIB), then converted back to the app timezone (UTC) the
        // timestamps are stored in. Previously the boundaries were UTC days.
        $tz = CurrentBranch::model()?->timezone ?: 'Asia/Jakarta';
        $now = now($tz);
        $toApp = fn (?Carbon $d) => $d?->setTimezone(config('app.timezone'));

        return array_map($toApp, match ($period) {
            self::PERIOD_TODAY => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            self::PERIOD_LAST_7_DAYS => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            self::PERIOD_THIS_MONTH => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            self::PERIOD_LAST_MONTH => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            self::PERIOD_THIS_QUARTER => [$now->copy()->firstOfQuarter()->startOfDay(), $now->copy()->lastOfQuarter()->endOfDay()],
            self::PERIOD_THIS_YEAR => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            self::PERIOD_CUSTOM => [
                $customStart ? Carbon::parse($customStart, $tz)->startOfDay() : null,
                $customEnd ? Carbon::parse($customEnd, $tz)->endOfDay() : null,
            ],
            default => [null, null],
        });
    }

    /**
     * Get comprehensive dashboard metrics.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(?int $branchId = null, string $period = self::PERIOD_THIS_MONTH, ?string $customStart = null, ?string $customEnd = null): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($period, $customStart, $customEnd);
        $effectiveBranchId = $this->resolveEffectiveBranchId($branchId);

        $bookingsSummary = $this->getBookingsSummary($effectiveBranchId, $startDate, $endDate);
        $financialMetrics = $this->getFinancialMetrics($effectiveBranchId, $startDate, $endDate);
        $leadFunnel = $this->getLeadFunnelMetrics($effectiveBranchId, $startDate, $endDate);
        $operationalStats = $this->getOperationalStats($effectiveBranchId, $startDate, $endDate);
        $recentBookings = $this->getRecentBookings($effectiveBranchId, 5);
        $pendingVerifications = $this->getPendingVerifications($effectiveBranchId, 5);
        $packagePerformance = $this->getPackagePerformance($effectiveBranchId, $startDate, $endDate);

        return [
            'period' => $period,
            'start_date' => $startDate?->toDateString(),
            'end_date' => $endDate?->toDateString(),
            'branch_id' => $effectiveBranchId,
            'bookings' => $bookingsSummary,
            'financials' => $financialMetrics,
            'lead_funnel' => $leadFunnel,
            'operations' => $operationalStats,
            'recent_bookings' => $recentBookings,
            'pending_verifications' => $pendingVerifications,
            'package_performance' => $packagePerformance,
        ];
    }

    /**
     * Bookings status breakdown.
     *
     * @return array<string, int>
     */
    public function getBookingsSummary(?int $branchId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $query = PackageBooking::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($startDate !== null, fn (Builder $q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $q) => $q->where('created_at', '<=', $endDate));

        $counts = (clone $query)->reorder()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $n = fn (BookingStatus $s) => (int) ($counts[$s->value] ?? 0);

        return [
            'total' => (int) $counts->sum(),
            'confirmed' => $n(BookingStatus::CONFIRMED),
            'partially_paid' => $n(BookingStatus::PARTIALLY_PAID),
            'paid' => $n(BookingStatus::PAID),
            'in_progress' => $n(BookingStatus::IN_PROGRESS),
            'completed' => $n(BookingStatus::COMPLETED),
            'cancelled' => $n(BookingStatus::CANCELLED),
        ];
    }

    /**
     * Financial & Margin metrics (deducting channel fees & vendor costs).
     *
     * @return array<string, mixed>
     */
    public function getFinancialMetrics(?int $branchId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $bookingsQuery = PackageBooking::query()
            ->withoutGlobalScope(BranchScope::class)
            ->with(['payments', 'refunds', 'vendorPayments', 'quotation.items'])
            ->where('status', '!=', BookingStatus::CANCELLED->value)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($startDate !== null, fn (Builder $q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $q) => $q->where('created_at', '<=', $endDate));

        $bookings = $bookingsQuery->get();
        $marginService = new MarginReportService;
        $summary = $marginService->computeOverallSummary($bookings);

        return [
            'gross_revenue_idr' => $summary['total_gross_idr'],
            'channel_fees_idr' => $summary['total_fees_idr'],
            'net_revenue_idr' => $summary['total_net_idr'],
            'vendor_costs_idr' => $summary['total_vendor_idr'],
            'actual_margin_idr' => $summary['total_margin_idr'],
            'overall_margin_percentage' => $summary['overall_margin_percentage'],
        ];
    }

    /**
     * Lead conversion funnel metrics and lost reasons breakdown.
     *
     * @return array<string, mixed>
     */
    public function getLeadFunnelMetrics(?int $branchId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $leadsQuery = Lead::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($startDate !== null, fn (Builder $q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $q) => $q->where('created_at', '<=', $endDate));

        $leads = $leadsQuery->get(['id', 'status', 'lost_reason', 'source']);
        $totalLeads = $leads->count();

        $quotationsQuery = Quotation::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($startDate !== null, fn (Builder $q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $q) => $q->where('created_at', '<=', $endDate));

        $totalQuotations = $quotationsQuery->count();

        $wonLeads = $leads->where('status', LeadStatus::WON)->count();
        $lostLeads = $leads->where('status', LeadStatus::LOST)->count();

        // Reasons breakdown
        $lostReasons = $leads->where('status', LeadStatus::LOST)
            ->filter(fn ($l) => ! empty($l->lost_reason))
            ->groupBy('lost_reason')
            ->map->count()
            ->toArray();

        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0.0;

        return [
            'total_leads' => $totalLeads,
            'quotations_count' => $totalQuotations,
            'won_count' => $wonLeads,
            'lost_count' => $lostLeads,
            'conversion_rate' => $conversionRate,
            'lost_reasons' => $lostReasons,
            'by_status' => [
                'new' => $leads->where('status', LeadStatus::NEW)->count(),
                'contacted' => $leads->where('status', LeadStatus::CONTACTED)->count(),
                'qualified' => $leads->where('status', LeadStatus::QUALIFIED)->count(),
                'quoted' => $leads->where('status', LeadStatus::QUOTED)->count(),
                'won' => $wonLeads,
                'lost' => $lostLeads,
            ],
        ];
    }

    /**
     * Operational and Fleet utilization.
     *
     * @return array<string, int>
     */
    public function getOperationalStats(?int $branchId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $driversQuery = Driver::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId));
        $totalDrivers = (clone $driversQuery)->where('is_active', true)->count();

        $vehiclesQuery = Vehicle::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId));
        $totalVehicles = (clone $vehiclesQuery)->where('is_active', true)->count();

        $assignmentsQuery = DriverAssignment::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->whereIn('status', [DriverAssignment::STATUS_ASSIGNED, DriverAssignment::STATUS_IN_PROGRESS])
            // Overlap with selected range; no range = assignments running today.
            ->whereDate('date_from', '<=', ($endDate ?? now())->toDateString())
            ->whereDate('date_to', '>=', ($startDate ?? now())->toDateString());

        $activeAssignments = (clone $assignmentsQuery)->count();
        $vehiclesInUse = (clone $assignmentsQuery)->distinct('vehicle_id')->count('vehicle_id');

        $partnersQuery = Partner::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId));
        $hotelPartners = (clone $partnersQuery)->where('type', 'hotel')->count();

        return [
            'total_drivers' => $totalDrivers,
            'active_assignments' => $activeAssignments,
            'total_vehicles' => $totalVehicles,
            'vehicles_in_use' => $vehiclesInUse,
            'hotel_partners' => $hotelPartners,
        ];
    }

    /**
     * Get recent package bookings.
     */
    public function getRecentBookings(?int $branchId, int $limit = 5): Collection
    {
        return PackageBooking::query()
            ->withoutGlobalScope(BranchScope::class)
            ->with(['quotation.lead', 'quotation.package'])
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Get pending payment verifications.
     */
    public function getPendingVerifications(?int $branchId, int $limit = 5): Collection
    {
        return Payment::query()
            ->withoutGlobalScope(BranchScope::class)
            ->with(['booking.quotation.lead'])
            ->where('status', Payment::STATUS_PENDING)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->take($limit)
            ->get();
    }

    /**
     * Get package margin performance breakdown.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPackagePerformance(?int $branchId, ?Carbon $startDate, ?Carbon $endDate): array
    {
        $packages = Package::query()
            ->withoutGlobalScope(BranchScope::class)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->get(['id', 'name']);
        $bookings = PackageBooking::query()
            ->withoutGlobalScope(BranchScope::class)
            ->with(['quotation.package', 'payments', 'refunds', 'vendorPayments', 'quotation.items'])
            ->where('status', '!=', BookingStatus::CANCELLED->value)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($startDate !== null, fn (Builder $q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $q) => $q->where('created_at', '<=', $endDate))
            ->get();

        $marginService = new MarginReportService;
        $performance = [];

        foreach ($packages as $package) {
            $pkgBookings = $bookings->filter(fn ($b) => $b->quotation?->package_id === $package->id);
            if ($pkgBookings->isEmpty()) {
                continue;
            }

            $summary = $marginService->computeOverallSummary($pkgBookings);
            $performance[] = [
                'package_id' => $package->id,
                'package_name' => $package->name,
                'bookings_count' => $pkgBookings->count(),
                'gross_revenue_idr' => $summary['total_gross_idr'],
                'vendor_costs_idr' => $summary['total_vendor_idr'],
                'actual_margin_idr' => $summary['total_margin_idr'],
                'margin_percentage' => $summary['overall_margin_percentage'],
            ];
        }

        usort($performance, fn ($a, $b) => $b['gross_revenue_idr'] <=> $a['gross_revenue_idr']);

        return $performance;
    }

    /**
     * Resolve effective branch ID based on user authorization.
     */
    protected function resolveEffectiveBranchId(?int $branchId): ?int
    {
        $user = Auth::user();

        // If user cannot switch branches, force their active branch
        if (! $user || ! $user->can('branch.switch')) {
            return CurrentBranch::id();
        }

        // If Super Admin passes specific branch_id, honor it; if 0 or null, return null (all branches)
        return ($branchId && $branchId > 0) ? $branchId : null;
    }
}
