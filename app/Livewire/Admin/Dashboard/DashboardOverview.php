<?php

namespace App\Livewire\Admin\Dashboard;

use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Fx;
use App\Domain\Lead\Models\Lead;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Domain\Reporting\Services\ReportExportService;
use App\Models\Branch;
use App\Support\Branch\BranchScope;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardOverview extends Component
{
    public ?int $branchId = null;

    public string $period = DashboardMetricsService::PERIOD_THIS_MONTH;

    public ?string $customStart = null;

    public ?string $customEnd = null;

    protected $queryString = [
        'period' => ['except' => DashboardMetricsService::PERIOD_THIS_MONTH],
        'branchId' => ['except' => null],
    ];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();
        } else {
            $this->branchId = session('active_branch_id', CurrentBranch::id());
        }
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

    public function updatingBranchId($value): void
    {
        if (! Auth::user()?->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();
        }
    }

    public function updatedBranchId($value): void
    {
        if (! Auth::user()?->can('branch.switch')) {
            $this->branchId = CurrentBranch::id();
        }
    }

    public function updatedPeriod(): void
    {
        if ($this->period !== DashboardMetricsService::PERIOD_CUSTOM) {
            $this->customStart = null;
            $this->customEnd = null;
        }
    }

    public function setPeriod(string $newPeriod): void
    {
        $this->period = $newPeriod;
    }

    public function setBranch(?int $newBranchId): void
    {
        if (Auth::user()?->can('branch.switch')) {
            $this->branchId = ($newBranchId && $newBranchId > 0) ? $newBranchId : null;
        }
    }

    public function export(string $type = 'sales'): StreamedResponse
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

        if ($type === 'leads') {
            $leads = Lead::query()
                ->tap($branchQuery)
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->latest()
                ->get();

            $content = $exportService->exportLeadConversion($leads);
            $filename = "laporan-konversi-lead-{$branchTag}-{$dateRange}.csv";
        } elseif ($type === 'operations') {
            $bookings = PackageBooking::query()
                ->tap($branchQuery)
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->latest()
                ->get();

            $content = $exportService->exportBookings($bookings);
            $filename = "laporan-operasional-{$branchTag}-{$dateRange}.csv";
        } else {
            abort_unless(Auth::user()?->can('report.margin.view') || Auth::user()?->can('payment.verify'), 403);

            $bookings = PackageBooking::query()
                ->with(['payments', 'vendorPayments', 'quotation.items', 'quotation.lead', 'quotation.package'])
                ->tap($branchQuery)
                ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate))
                ->latest()
                ->get();

            $content = $exportService->exportSalesMargin($bookings);
            $filename = "laporan-penjualan-margin-{$branchTag}-{$dateRange}.csv";
        }

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * IDR per 1 unit of each active currency, for the view-only currency toggle
     * on the revenue card. Currencies without a rate are left out, never 1.0.
     *
     * @return array<string, array{rate: float, d: int}>
     */
    private function displayRates(): array
    {
        $rates = ['IDR' => ['rate' => 1.0, 'd' => 0]];

        foreach (Currency::where('is_active', true)->where('code', '!=', 'IDR')->orderBy('code')->get() as $currency) {
            try {
                $rates[$currency->code] = ['rate' => Fx::rate($currency->code), 'd' => (int) $currency->decimal_places];
            } catch (\Throwable) {
                // no rate yet for this currency: just don't offer it
            }
        }

        return $rates;
    }

    public function render(): View
    {
        $effectiveBranchId = $this->resolveEffectiveBranchId();
        $metricsService = new DashboardMetricsService;
        $metrics = $metricsService->getMetrics(
            $effectiveBranchId,
            $this->period,
            $this->customStart,
            $this->customEnd
        );

        $branches = Branch::where('is_active', true)->get(['id', 'name', 'code']);

        $canViewFinancials = Auth::user()?->can('report.margin.view') || Auth::user()?->can('payment.verify');
        $canSwitchBranch = (bool) Auth::user()?->can('branch.switch');

        return view('livewire.admin.dashboard.dashboard-overview', [
            'fxRates' => $this->displayRates(),
            'metrics' => $metrics,
            'branches' => $branches,
            'canViewFinancials' => $canViewFinancials,
            'canSwitchBranch' => $canSwitchBranch,
        ]);
    }
}
