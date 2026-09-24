<?php

namespace App\Livewire\Admin\Reporting;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Services\MarginReportService;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Lead\Models\Lead;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Domain\Reporting\Services\ReportExportService;
use App\Models\Branch;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Support\Branch\BranchScope;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsCenter extends Component
{
    use WithPagination;

    public string $activeTab = 'sales_margin'; // 'sales_margin', 'lead_conversion', 'operations'

    public ?int $branchId = null;

    public string $period = DashboardMetricsService::PERIOD_THIS_MONTH;

    public ?string $customStart = null;

    public ?string $customEnd = null;

    public string $search = '';

    protected $queryString = [
        'activeTab' => ['except' => 'sales_margin'],
        'period' => ['except' => DashboardMetricsService::PERIOD_THIS_MONTH],
        'branchId' => ['except' => null],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();
        } else {
            $this->branchId = session('active_branch_id', CurrentBranch::id());
        }

        // If CS Admin doesn't have report.margin.view, default to lead_conversion tab
        if (! $user?->can('report.margin.view') && ! $user?->can('payment.verify')) {
            $this->activeTab = 'lead_conversion';
        }
    }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['sales_margin', 'lead_conversion', 'operations'], true), 404);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    protected function resolveEffectiveBranchId(): ?int
    {
        if (! Auth::user()?->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();

            return CurrentBranch::id();
        }

        return $this->branchId;
    }

    public function boot(): void
    {
        if (! Auth::user()?->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();
        }
    }

    public function updatingPeriod(): void
    {
        $this->resetPage();
    }

    public function updatingBranchId($value): void
    {
        if (! Auth::user()?->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();

            return;
        }
        $this->resetPage();
    }

    public function updatedBranchId($value): void
    {
        if (! Auth::user()?->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function export(): StreamedResponse
    {
        $effectiveBranchId = $this->resolveEffectiveBranchId();
        $service = new DashboardMetricsService;
        [$startDate, $endDate] = $service->resolveDateRange($this->period, $this->customStart, $this->customEnd);
        $exportService = new ReportExportService;

        $branchQuery = function ($query) use ($effectiveBranchId) {
            $query->withoutGlobalScope(BranchScope::class)
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId));
        };

        $dateRange = now()->format('Ymd');
        $branchTag = $effectiveBranchId ? "cabang-{$effectiveBranchId}" : 'gabungan';

        if ($this->activeTab === 'lead_conversion') {
            abort_unless(Auth::user()?->can('lead.manage'), 403);
            $leads = Lead::query()->with('branch')
                ->tap($branchQuery)
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->lazyByIdDesc(500);

            $stream = fn () => $exportService->streamLeadConversion($leads);
            $filename = "laporan-konversi-lead-{$branchTag}-{$dateRange}.csv";
        } elseif ($this->activeTab === 'operations') {
            abort_unless(Auth::user()?->can('booking.manage') || Auth::user()?->can('report.view'), 403);
            $bookings = PackageBooking::query()
                ->with(['branch', 'quotation.lead', 'quotation.package', 'payments'])
                ->tap($branchQuery)
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->lazyByIdDesc(500);

            $stream = fn () => $exportService->streamBookings($bookings);
            $filename = "laporan-operasional-{$branchTag}-{$dateRange}.csv";
        } else {
            abort_unless(Auth::user()?->can('report.margin.view') || Auth::user()?->can('payment.verify'), 403);

            $bookings = PackageBooking::query()
                ->with(['payments', 'vendorPayments', 'quotation.items', 'quotation.lead', 'quotation.package', 'branch'])
                ->tap($branchQuery)
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->lazyByIdDesc(500);

            $stream = fn () => $exportService->streamSalesMargin($bookings);
            $filename = "laporan-penjualan-margin-{$branchTag}-{$dateRange}.csv";
        }

        return response()->streamDownload(function () use ($stream) {
            $stream();
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render(): View
    {
        $metricsService = new DashboardMetricsService;
        $effectiveBranchId = $this->resolveEffectiveBranchId();
        [$startDate, $endDate] = $metricsService->resolveDateRange($this->period, $this->customStart, $this->customEnd);

        $branches = Branch::where('is_active', true)->get(['id', 'name', 'code']);
        $canViewFinancials = Auth::user()?->can('report.margin.view') || Auth::user()?->can('payment.verify');
        $canSwitchBranch = (bool) Auth::user()?->can('branch.switch');

        $data = [];

        if ($this->activeTab === 'sales_margin' && $canViewFinancials) {
            $bookingsQuery = PackageBooking::query()
                ->withoutGlobalScope(BranchScope::class)
                ->with(['quotation.package', 'quotation.lead', 'payments', 'vendorPayments', 'quotation.items', 'branch'])
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->when($this->search !== '', function ($q) {
                    $escaped = addcslashes($this->search, '%_\\');
                    $q->where(function ($sq) use ($escaped) {
                        $sq->where('code', 'like', "%{$escaped}%")
                            ->orWhereHas('quotation.lead', fn ($lq) => $lq->where('name', 'like', "%{$escaped}%"));
                    });
                })
                ->latest('departure_date');

            $allBookings = (clone $bookingsQuery)->lazyById(500);
            $data['bookings'] = $bookingsQuery->paginate(15);
            $marginService = new MarginReportService;
            $data['summary'] = $marginService->computeOverallSummary($allBookings);
            $data['package_performance'] = $metricsService->getPackagePerformance($effectiveBranchId, $startDate, $endDate);
        } elseif ($this->activeTab === 'lead_conversion') {
            $leadsQuery = Lead::query()
                ->withoutGlobalScope(BranchScope::class)
                ->with(['assignee', 'quotations'])
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->when($this->search !== '', function ($q) {
                    $escaped = addcslashes($this->search, '%_\\');
                    $q->where(function ($sq) use ($escaped) {
                        $sq->where('name', 'like', "%{$escaped}%")
                            ->orWhere('phone', 'like', "%{$escaped}%");
                    });
                })
                ->latest();

            $data['leads'] = $leadsQuery->paginate(15);
            $data['funnel'] = $metricsService->getLeadFunnelMetrics($effectiveBranchId, $startDate, $endDate);
        } elseif ($this->activeTab === 'operations') {
            $data['operations_stats'] = $metricsService->getOperationalStats($effectiveBranchId, $startDate, $endDate);

            $assignmentsQuery = DriverAssignment::query()
                ->withoutGlobalScope(BranchScope::class)
                ->with(['driver', 'vehicle', 'booking.quotation.lead', 'branch'])
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->when($startDate, fn ($q) => $q->where('date_to', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('date_from', '<=', $endDate))
                ->latest('date_from');

            $data['assignments'] = $assignmentsQuery->paginate(15);
            $data['drivers'] = Driver::query()
                ->withoutGlobalScope(BranchScope::class)
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->get();
            $data['vehicles'] = Vehicle::query()
                ->withoutGlobalScope(BranchScope::class)
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->get();
            $data['hotel_partners'] = Partner::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('type', 'hotel')
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->get();
        }

        return view('livewire.admin.reporting.reports-center', [
            'branches' => $branches,
            'canViewFinancials' => $canViewFinancials,
            'canSwitchBranch' => $canSwitchBranch,
            'data' => $data,
        ]);
    }
}
