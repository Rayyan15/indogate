<div class="space-y-6">
    {{-- Header & Filters --}}
    <x-ui.page-header :eyebrow="__('report.reports')" :title="__('report.dashboard')" :lede="__('report.dashboard_lede')">
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                {{-- Branch Selector (Super Admin) --}}
                @if($canSwitchBranch)
                <select wire:model.live="branchId" class="h-9 rounded border border-neutral-300 bg-neutral-0 px-3 text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                    <option value="">{{ __('report.all_branches') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @endif

                {{-- Period Selector --}}
                <select wire:model.live="period" class="h-9 rounded border border-neutral-300 bg-neutral-0 px-3 text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                    <option value="today">{{ __('report.today') }}</option>
                    <option value="last_7_days">{{ __('report.last_7_days') }}</option>
                    <option value="this_month">{{ __('report.this_month') }}</option>
                    <option value="last_month">{{ __('report.last_month') }}</option>
                    <option value="this_quarter">{{ __('report.this_quarter') }}</option>
                    <option value="this_year">{{ __('report.this_year') }}</option>
                    <option value="all_time">{{ __('report.all_time') }}</option>
                    <option value="custom">{{ __('report.custom') }}</option>
                </select>

                {{-- Export Button (Single red-600 primary action) --}}
                <div x-data="{ open: false }" class="relative inline-block text-start">
                    <button @click="open = !open" type="button" class="inline-flex h-9 items-center gap-1.5 rounded bg-red-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>{{ __('report.export') }}</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition class="absolute end-0 z-20 mt-1 w-48 rounded border border-neutral-200 bg-neutral-0 py-1 shadow-lg text-xs">
                        @if($canViewFinancials)
                        <button wire:click="export('sales')" @click="open = false" class="flex w-full items-center px-3 py-2 text-start text-neutral-700 hover:bg-neutral-50">
                            {{ __('report.tab_sales_margin') }} (CSV)
                        </button>
                        @endif
                        <button wire:click="export('leads')" @click="open = false" class="flex w-full items-center px-3 py-2 text-start text-neutral-700 hover:bg-neutral-50">
                            {{ __('report.tab_lead_conversion') }} (CSV)
                        </button>
                        <button wire:click="export('operations')" @click="open = false" class="flex w-full items-center px-3 py-2 text-start text-neutral-700 hover:bg-neutral-50">
                            {{ __('report.tab_operations') }} (CSV)
                        </button>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-ui.page-header>

    {{-- Custom Date Inputs if selected --}}
    @if($period === 'custom')
    <div class="flex flex-wrap items-center gap-3 rounded border border-neutral-200 bg-neutral-0 p-3 shadow-sm text-xs">
        <div class="flex items-center gap-2">
            <span class="text-neutral-500">{{ __('report.start_date') }}:</span>
            <input type="date" wire:model="customStart" class="h-8 rounded border border-neutral-300 px-2 text-xs">
        </div>
        <div class="flex items-center gap-2">
            <span class="text-neutral-500">{{ __('report.end_date') }}:</span>
            <input type="date" wire:model="customEnd" class="h-8 rounded border border-neutral-300 px-2 text-xs">
        </div>
        <button wire:click="$refresh" type="button" class="h-8 rounded border border-neutral-300 bg-neutral-100 px-3 font-medium text-neutral-800 hover:bg-neutral-200">
            {{ __('report.apply') }}
        </button>
    </div>
    @endif

    {{-- 4 Core KPI Summary Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        {{-- Total Bookings --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('report.total_bookings') }}</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold font-mono text-neutral-900">{{ $metrics['bookings']['total'] }}</span>
                <span class="text-xs text-neutral-500">{{ $metrics['bookings']['confirmed'] + $metrics['bookings']['paid'] }} aktif</span>
            </div>
            <div class="mt-2 text-[11px] text-neutral-500">
                {{ $metrics['bookings']['paid'] }} Lunas · {{ $metrics['bookings']['partially_paid'] }} DP
            </div>
        </div>

        {{-- Gross Revenue --}}
        @if($canViewFinancials)
        <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('report.gross_revenue') }}</div>
            <div class="mt-2 text-xl font-bold font-mono text-neutral-900">
                IDR {{ number_format($metrics['financials']['gross_revenue_idr'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-rose-700">
                - IDR {{ number_format($metrics['financials']['channel_fees_idr'], 0, ',', '.') }} (MDR)
            </div>
        </div>

        {{-- True Net Margin --}}
        <div class="rounded border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">
                {{ __('report.net_margin') }} ({{ $metrics['financials']['overall_margin_percentage'] }}%)
            </div>
            <div class="mt-2 text-xl font-bold font-mono text-emerald-950">
                IDR {{ number_format($metrics['financials']['actual_margin_idr'], 0, ',', '.') }}
            </div>
            <div class="mt-2 text-[11px] text-emerald-700">
                HPP: IDR {{ number_format($metrics['financials']['vendor_costs_idr'], 0, ',', '.') }}
            </div>
        </div>
        @else
        <div class="rounded border border-neutral-200 bg-neutral-50 p-4 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400">{{ __('report.net_margin') }}</div>
            <div class="mt-2 text-sm font-medium text-neutral-500">{{ __('report.restricted_financials') }}</div>
            <div class="mt-1 text-[11px] text-neutral-400">{{ __('report.restricted_financials_note') }}</div>
        </div>
        @endif

        {{-- Lead Conversion Rate --}}
        <div class="rounded border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-blue-800">{{ __('report.conversion_rate') }}</div>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-bold font-mono text-blue-950">{{ $metrics['lead_funnel']['conversion_rate'] }}%</span>
                <span class="text-xs text-blue-800 font-medium">{{ $metrics['lead_funnel']['won_count'] }} Won</span>
            </div>
            <div class="mt-2 text-[11px] text-blue-700">
                Dari {{ $metrics['lead_funnel']['total_leads'] }} prospek ({{ $metrics['lead_funnel']['quotations_count'] }} penawaran)
            </div>
        </div>
    </div>

    {{-- Main Sections Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Recent Bookings --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-3.5">
                <h3 class="text-sm font-semibold text-neutral-900">{{ __('report.recent_bookings') }}</h3>
                <a href="{{ route('admin.package-bookings.index') }}" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">
                    Lihat Semua
                </a>
            </div>
            <div class="divide-y divide-neutral-100">
                @forelse($metrics['recent_bookings'] as $booking)
                <div class="flex items-center justify-between px-5 py-3 text-xs">
                    <div>
                        <a href="{{ route('admin.package-bookings.show', $booking->id) }}" class="font-mono font-bold text-red-600 hover:underline">
                            {{ $booking->code }}
                        </a>
                        <p class="mt-0.5 text-neutral-600">
                            {{ $booking->quotation?->lead?->name ?? 'Tamu' }}
                            @if($booking->departure_date)
                            · {{ $booking->departure_date->format('d M Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="font-mono font-bold text-neutral-900">
                            {{ $booking->currency ?? 'IDR' }} {{ number_format($booking->total_minor, 0, ',', '.') }}
                        </div>
                        <span class="mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold
                            @if($booking->status->value === 'paid') bg-emerald-100 text-emerald-800
                            @elseif($booking->status->value === 'partially_paid') bg-amber-100 text-amber-800
                            @elseif($booking->status->value === 'confirmed') bg-red-100 text-red-800
                            @else bg-neutral-100 text-neutral-700 @endif">
                            {{ $booking->status->value }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="p-5 text-center text-xs text-neutral-500">{{ __('report.no_bookings') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Pending Verifications (Finance / Super Admin) or Operations Overview --}}
        @if($canViewFinancials)
        <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-3.5">
                <h3 class="text-sm font-semibold text-neutral-900">{{ __('report.pending_verifications') }}</h3>
                <a href="{{ route('admin.finance.payments') }}" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">
                    Lihat Semua
                </a>
            </div>
            <div class="divide-y divide-neutral-100">
                @forelse($metrics['pending_verifications'] as $payment)
                <div class="flex items-center justify-between px-5 py-3 text-xs">
                    <div>
                        <span class="font-semibold text-neutral-900">{{ $payment->booking?->quotation?->lead?->name ?? 'Tamu' }}</span>
                        <p class="mt-0.5 font-mono text-[11px] text-neutral-500">
                            {{ $payment->booking?->code }} · {{ $payment->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono font-bold text-neutral-900">
                            {{ $payment->currency }} {{ number_format($payment->amount_minor, 0, ',', '.') }}
                        </span>
                        <a href="{{ route('admin.finance.payments') }}" class="rounded border border-neutral-300 bg-neutral-50 px-2 py-1 text-[11px] font-medium text-neutral-800 hover:bg-neutral-100">
                            {{ __('report.review') }}
                        </a>
                    </div>
                </div>
                @empty
                <div class="p-5 text-center text-xs text-neutral-500">{{ __('report.no_pending_verifications') }}</div>
                @endforelse
            </div>
        </div>
        @else
        {{-- Fleet / Operations Card for CS --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-3.5">
                <h3 class="text-sm font-semibold text-neutral-900">{{ __('report.fleet_utilization') }}</h3>
                <a href="{{ route('admin.fleet.calendar') }}" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">
                    Kalender Armada
                </a>
            </div>
            <div class="grid grid-cols-2 gap-4 p-5 text-xs">
                <div class="rounded border border-neutral-100 bg-neutral-50 p-3">
                    <span class="text-neutral-500">{{ __('report.active_drivers') }}</span>
                    <p class="mt-1 font-mono text-xl font-bold text-neutral-900">{{ $metrics['operations']['total_drivers'] }}</p>
                    <p class="mt-1 text-[11px] text-neutral-500">{{ $metrics['operations']['active_assignments'] }} penugasan berjalan</p>
                </div>
                <div class="rounded border border-neutral-100 bg-neutral-50 p-3">
                    <span class="text-neutral-500">{{ __('report.total_vehicles') }}</span>
                    <p class="mt-1 font-mono text-xl font-bold text-neutral-900">{{ $metrics['operations']['total_vehicles'] }}</p>
                    <p class="mt-1 text-[11px] text-neutral-500">{{ $metrics['operations']['vehicles_in_use'] }} unit sedang bertugas</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Funnel & Lost Reasons Breakdown --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Lead Funnel Stages --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 p-5 shadow-sm lg:col-span-2">
            <h3 class="text-sm font-semibold text-neutral-900 mb-4">{{ __('report.lead_funnel') }}</h3>
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-6 text-center text-xs">
                <div class="rounded border border-neutral-200 p-2.5">
                    <span class="text-[10px] uppercase font-semibold text-neutral-500">Baru</span>
                    <p class="mt-1 font-mono text-lg font-bold text-neutral-800">{{ $metrics['lead_funnel']['by_status']['new'] }}</p>
                </div>
                <div class="rounded border border-neutral-200 p-2.5">
                    <span class="text-[10px] uppercase font-semibold text-neutral-500">Dihubungi</span>
                    <p class="mt-1 font-mono text-lg font-bold text-neutral-800">{{ $metrics['lead_funnel']['by_status']['contacted'] }}</p>
                </div>
                <div class="rounded border border-neutral-200 p-2.5">
                    <span class="text-[10px] uppercase font-semibold text-neutral-500">Kualifikasi</span>
                    <p class="mt-1 font-mono text-lg font-bold text-neutral-800">{{ $metrics['lead_funnel']['by_status']['qualified'] }}</p>
                </div>
                <div class="rounded border border-blue-200 bg-blue-50/50 p-2.5">
                    <span class="text-[10px] uppercase font-semibold text-blue-700">Penawaran</span>
                    <p class="mt-1 font-mono text-lg font-bold text-blue-900">{{ $metrics['lead_funnel']['by_status']['quoted'] }}</p>
                </div>
                <div class="rounded border border-emerald-200 bg-emerald-50/50 p-2.5">
                    <span class="text-[10px] uppercase font-semibold text-emerald-700">Berhasil</span>
                    <p class="mt-1 font-mono text-lg font-bold text-emerald-900">{{ $metrics['lead_funnel']['won_count'] }}</p>
                </div>
                <div class="rounded border border-rose-200 bg-rose-50/50 p-2.5">
                    <span class="text-[10px] uppercase font-semibold text-rose-700">Gagal</span>
                    <p class="mt-1 font-mono text-lg font-bold text-rose-900">{{ $metrics['lead_funnel']['lost_count'] }}</p>
                </div>
            </div>
        </div>

        {{-- Lost Reasons Breakdown --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-neutral-900 mb-3">{{ __('report.lost_reasons') }}</h3>
            @if(empty($metrics['lead_funnel']['lost_reasons']))
            <p class="text-xs text-neutral-500 py-3 text-center">Tidak ada catatan alasan penolakan pada periode ini.</p>
            @else
            <ul class="divide-y divide-neutral-100 text-xs">
                @foreach($metrics['lead_funnel']['lost_reasons'] as $reason => $count)
                <li class="flex items-center justify-between py-2">
                    <span class="text-neutral-700 truncate pe-2">{{ $reason }}</span>
                    <span class="font-mono font-semibold text-neutral-900">{{ $count }}</span>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- Package Performance Table --}}
    @if($canViewFinancials && !empty($metrics['package_performance']))
    <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm overflow-hidden">
        <div class="border-b border-neutral-100 px-5 py-3.5">
            <h3 class="text-sm font-semibold text-neutral-900">{{ __('report.package_performance') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-start">
                <thead class="bg-neutral-50 text-neutral-600 border-b border-neutral-200">
                    <tr>
                        <th class="px-5 py-2.5 text-start font-semibold">{{ __('report.package') }}</th>
                        <th class="px-5 py-2.5 text-end font-semibold">{{ __('report.bookings_count') }}</th>
                        <th class="px-5 py-2.5 text-end font-semibold">{{ __('report.gross_revenue') }}</th>
                        <th class="px-5 py-2.5 text-end font-semibold">{{ __('report.vendor_costs') }}</th>
                        <th class="px-5 py-2.5 text-end font-semibold">{{ __('report.net_margin') }}</th>
                        <th class="px-5 py-2.5 text-end font-semibold">{{ __('report.margin_percentage') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 text-neutral-800">
                    @foreach($metrics['package_performance'] as $pkg)
                    <tr class="hover:bg-neutral-50/50">
                        <td class="px-5 py-3 font-medium text-neutral-900">{{ $pkg['package_name'] }}</td>
                        <td class="px-5 py-3 text-end font-mono">{{ $pkg['bookings_count'] }}</td>
                        <td class="px-5 py-3 text-end font-mono">IDR {{ number_format($pkg['gross_revenue_idr'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-end font-mono text-rose-700">IDR {{ number_format($pkg['vendor_costs_idr'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-end font-mono font-bold text-emerald-700">IDR {{ number_format($pkg['actual_margin_idr'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-end font-mono font-semibold">{{ $pkg['margin_percentage'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
