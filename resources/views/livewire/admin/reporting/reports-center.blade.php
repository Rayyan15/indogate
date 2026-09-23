<div class="space-y-6">
    {{-- Header with Tab Navigation & Controls --}}
    <x-ui.page-header :eyebrow="__('report.reports')" :title="__('report.reports')" :lede="__('report.dashboard_lede')">
        <x-slot name="actions">
            <div class="flex flex-wrap items-center gap-2">
                {{-- Branch Selector --}}
                @if($canSwitchBranch)
                <select wire:model.live="branchId" class="h-9 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                    <option value="">{{ __('report.all_branches') }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>
                    @endforeach
                </select>
                @endif

                {{-- Period Selector --}}
                <select wire:model.live="period" class="h-9 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                    <option value="today">{{ __('report.today') }}</option>
                    <option value="last_7_days">{{ __('report.last_7_days') }}</option>
                    <option value="this_month">{{ __('report.this_month') }}</option>
                    <option value="last_month">{{ __('report.last_month') }}</option>
                    <option value="this_quarter">{{ __('report.this_quarter') }}</option>
                    <option value="this_year">{{ __('report.this_year') }}</option>
                    <option value="all_time">{{ __('report.all_time') }}</option>
                    <option value="custom">{{ __('report.custom') }}</option>
                </select>

                {{-- Single Red-600 Action Button: Export --}}
                <button wire:click="export" type="button" class="inline-flex h-9 items-center gap-1.5 rounded bg-red-600 px-3 text-xs font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>{{ __('report.export_csv') }}</span>
                </button>
            </div>
        </x-slot>
    </x-ui.page-header>

    {{-- Tabs --}}
    <div class="border-b border-neutral-200">
        <nav class="-mb-px flex space-x-6 rtl:space-x-reverse text-xs font-medium">
            @if($canViewFinancials)
            <button wire:click="setTab('sales_margin')" type="button" class="border-b-2 py-3 px-1 transition-colors {{ $activeTab === 'sales_margin' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }}">
                {{ __('report.tab_sales_margin') }}
            </button>
            @endif
            <button wire:click="setTab('lead_conversion')" type="button" class="border-b-2 py-3 px-1 transition-colors {{ $activeTab === 'lead_conversion' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }}">
                {{ __('report.tab_lead_conversion') }}
            </button>
            <button wire:click="setTab('operations')" type="button" class="border-b-2 py-3 px-1 transition-colors {{ $activeTab === 'operations' ? 'border-red-600 text-red-600 font-semibold' : 'border-transparent text-neutral-500 hover:border-neutral-300 hover:text-neutral-700' }}">
                {{ __('report.tab_operations') }}
            </button>
        </nav>
    </div>

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

    {{-- TAB 1: SALES & MARGIN --}}
    @if($activeTab === 'sales_margin' && $canViewFinancials)
    <div class="space-y-6">
        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('report.gross_revenue') }}</div>
                <div class="mt-2 text-xl font-bold font-mono text-neutral-900">
                    IDR {{ number_format($data['summary']['total_gross_idr'], 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">{{ __('report.channel_fees') }}</div>
                <div class="mt-2 text-xl font-bold font-mono text-rose-950">
                    - IDR {{ number_format($data['summary']['total_fees_idr'], 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded border border-amber-200 bg-amber-50/50 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">{{ __('report.vendor_costs') }}</div>
                <div class="mt-2 text-xl font-bold font-mono text-amber-950">
                    - IDR {{ number_format($data['summary']['total_vendor_idr'], 0, ',', '.') }}
                </div>
            </div>
            <div class="rounded border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">
                    {{ __('report.net_margin') }} ({{ $data['summary']['overall_margin_percentage'] }}%)
                </div>
                <div class="mt-2 text-xl font-bold font-mono text-emerald-950">
                    IDR {{ number_format($data['summary']['total_margin_idr'], 0, ',', '.') }}
                </div>
            </div>
        </div>

        {{-- Search Toolbar --}}
        <div class="flex items-center justify-between">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode booking atau nama tamu..." class="h-9 w-72 rounded border border-neutral-300 px-3 text-xs placeholder:text-neutral-400 focus:border-red-600 focus:ring-1 focus:ring-red-600">
        </div>

        {{-- Bookings Margin Table --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-center">
                    <thead class="bg-neutral-50 text-neutral-600 border-b border-neutral-200">
                        <tr>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.booking_code') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.guest_name') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.gross_revenue') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.channel_fees') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.net_revenue') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.vendor_costs') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.net_margin') }}</th>
                            <th class="px-4 py-2.5 text-center font-semibold">{{ __('report.margin_percentage') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-neutral-800">
                        @php $marginService = new \App\Domain\Finance\Services\MarginReportService(); @endphp
                        @forelse($data['bookings'] as $booking)
                        @php $m = $marginService->computeBookingMargin($booking); @endphp
                        <tr wire:key="margin-booking-{{ $booking->id }}" class="hover:bg-neutral-50/50">
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.package-bookings.show', $booking->id) }}" class="font-mono font-bold text-red-600 hover:underline">
                                    {{ $booking->code }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="font-medium text-neutral-900">{{ $booking->quotation?->lead?->name ?? '-' }}</div>
                                <div class="text-[11px] text-neutral-500">{{ $booking->quotation?->package?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center font-mono">IDR {{ number_format($m['gross_revenue_idr'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-mono text-rose-700">- IDR {{ number_format($m['channel_fees_idr'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-mono">IDR {{ number_format($m['net_revenue_idr'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-mono text-amber-700">- IDR {{ number_format($m['vendor_costs_idr'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-mono font-bold text-emerald-700">IDR {{ number_format($m['actual_margin_idr'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center font-mono font-semibold">{{ number_format($m['margin_percentage'], 1) }}%</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="p-5 text-center text-xs text-neutral-500">{{ __('report.no_data') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data['bookings']->hasPages())
            <div class="border-t border-neutral-100 p-3">
                {{ $data['bookings']->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- TAB 2: LEAD CONVERSION --}}
    @if($activeTab === 'lead_conversion')
    <div class="space-y-6">
        {{-- Funnel Cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('report.total_leads') }}</div>
                <div class="mt-2 text-2xl font-bold font-mono text-neutral-900">{{ $data['funnel']['total_leads'] }}</div>
            </div>
            <div class="rounded border border-blue-200 bg-blue-50/50 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Penawaran Diterbitkan</div>
                <div class="mt-2 text-2xl font-bold font-mono text-blue-950">{{ $data['funnel']['quotations_count'] }}</div>
            </div>
            <div class="rounded border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-800">{{ __('report.conversion_rate') }}</div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold font-mono text-emerald-950">{{ $data['funnel']['conversion_rate'] }}%</span>
                    <span class="text-xs text-emerald-700 font-semibold">{{ $data['funnel']['won_count'] }} Berhasil</span>
                </div>
            </div>
            <div class="rounded border border-rose-200 bg-rose-50/50 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">{{ __('report.total_lost') }}</div>
                <div class="mt-2 text-2xl font-bold font-mono text-rose-950">{{ $data['funnel']['lost_count'] }}</div>
            </div>
        </div>

        {{-- Funnel Stages & Reasons --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded border border-neutral-200 bg-neutral-0 p-5 shadow-sm lg:col-span-2">
                <h3 class="text-sm font-semibold text-neutral-900 mb-4">{{ __('report.lead_funnel') }}</h3>
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-6 text-center text-xs">
                    <div class="rounded border border-neutral-200 p-2.5">
                        <span class="text-[10px] uppercase font-semibold text-neutral-500">Baru</span>
                        <p class="mt-1 font-mono text-lg font-bold text-neutral-800">{{ $data['funnel']['by_status']['new'] }}</p>
                    </div>
                    <div class="rounded border border-neutral-200 p-2.5">
                        <span class="text-[10px] uppercase font-semibold text-neutral-500">Dihubungi</span>
                        <p class="mt-1 font-mono text-lg font-bold text-neutral-800">{{ $data['funnel']['by_status']['contacted'] }}</p>
                    </div>
                    <div class="rounded border border-neutral-200 p-2.5">
                        <span class="text-[10px] uppercase font-semibold text-neutral-500">Kualifikasi</span>
                        <p class="mt-1 font-mono text-lg font-bold text-neutral-800">{{ $data['funnel']['by_status']['qualified'] }}</p>
                    </div>
                    <div class="rounded border border-blue-200 bg-blue-50/50 p-2.5">
                        <span class="text-[10px] uppercase font-semibold text-blue-700">Penawaran</span>
                        <p class="mt-1 font-mono text-lg font-bold text-blue-900">{{ $data['funnel']['by_status']['quoted'] }}</p>
                    </div>
                    <div class="rounded border border-emerald-200 bg-emerald-50/50 p-2.5">
                        <span class="text-[10px] uppercase font-semibold text-emerald-700">Berhasil</span>
                        <p class="mt-1 font-mono text-lg font-bold text-emerald-900">{{ $data['funnel']['won_count'] }}</p>
                    </div>
                    <div class="rounded border border-rose-200 bg-rose-50/50 p-2.5">
                        <span class="text-[10px] uppercase font-semibold text-rose-700">Gagal</span>
                        <p class="mt-1 font-mono text-lg font-bold text-rose-900">{{ $data['funnel']['lost_count'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded border border-neutral-200 bg-neutral-0 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-neutral-900 mb-3">{{ __('report.lost_reasons') }}</h3>
                @if(empty($data['funnel']['lost_reasons']))
                <p class="text-xs text-neutral-500 py-3 text-center">Tidak ada catatan alasan penolakan pada periode ini.</p>
                @else
                <ul class="divide-y divide-neutral-100 text-xs">
                    @foreach($data['funnel']['lost_reasons'] as $reason => $count)
                    <li class="flex items-center justify-between py-2">
                        <span class="text-neutral-700 truncate pe-2">{{ $reason }}</span>
                        <span class="font-mono font-semibold text-neutral-900">{{ $count }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
        </div>

        {{-- Leads Table --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-center">
                    <thead class="bg-neutral-50 text-neutral-600 border-b border-neutral-200">
                        <tr>
                            <th class="px-4 py-2.5 text-center font-semibold">Tamu / Prospek</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Kontak</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Sumber</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Status</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Alasan Kalah / Catatan</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Penanggung Jawab</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-neutral-800">
                        @forelse($data['leads'] as $lead)
                        <tr wire:key="lead-{{ $lead->id }}" class="hover:bg-neutral-50/50">
                            <td class="px-4 py-3 text-center font-medium text-neutral-900">{{ $lead->name }}</td>
                            <td class="px-4 py-3 text-center text-neutral-600 font-mono">{{ $lead->phone }}</td>
                            <td class="px-4 py-3 text-center uppercase text-[11px] text-neutral-500">{{ $lead->source?->value ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold
                                    @if($lead->status->value === 'won') bg-emerald-100 text-emerald-800
                                    @elseif($lead->status->value === 'lost') bg-rose-100 text-rose-800
                                    @elseif($lead->status->value === 'quoted') bg-blue-100 text-blue-800
                                    @else bg-neutral-100 text-neutral-700 @endif">
                                    {{ $lead->status->value }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-neutral-600">{{ $lead->lost_reason ?? '-' }}</td>
                            <td class="px-4 py-3 text-center text-neutral-500">{{ $lead->assignee?->name ?? 'Belum Ditugaskan' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-5 text-center text-xs text-neutral-500">{{ __('report.no_data') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data['leads']->hasPages())
            <div class="border-t border-neutral-100 p-3">
                {{ $data['leads']->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- TAB 3: OPERATIONS & FLEET --}}
    @if($activeTab === 'operations')
    <div class="space-y-6">
        {{-- Fleet Utilization KPI Cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('report.active_drivers') }}</div>
                <div class="mt-2 text-2xl font-bold font-mono text-neutral-900">{{ $data['operations_stats']['total_drivers'] }}</div>
                <div class="mt-1 text-[11px] text-neutral-500">{{ $data['operations_stats']['active_assignments'] }} sedang bertugas</div>
            </div>
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('report.total_vehicles') }}</div>
                <div class="mt-2 text-2xl font-bold font-mono text-neutral-900">{{ $data['operations_stats']['total_vehicles'] }}</div>
                <div class="mt-1 text-[11px] text-neutral-500">{{ $data['operations_stats']['vehicles_in_use'] }} armada berjalan</div>
            </div>
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Penugasan Berjalan</div>
                <div class="mt-2 text-2xl font-bold font-mono text-neutral-900">{{ $data['operations_stats']['active_assignments'] }}</div>
            </div>
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">Mitra Hotel Aktif</div>
                <div class="mt-2 text-2xl font-bold font-mono text-neutral-900">{{ $data['operations_stats']['hotel_partners'] }}</div>
            </div>
        </div>

        {{-- Driver Assignments Table --}}
        <div class="rounded border border-neutral-200 bg-neutral-0 shadow-sm overflow-hidden">
            <div class="border-b border-neutral-100 px-5 py-3.5 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-neutral-900">Jadwal Penugasan Armada</h3>
                <a href="{{ route('admin.fleet.calendar') }}" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">
                    Buka Kalender
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-center">
                    <thead class="bg-neutral-50 text-neutral-600 border-b border-neutral-200">
                        <tr>
                            <th class="px-4 py-2.5 text-center font-semibold">Driver</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Kendaraan</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Kode Booking / Tamu</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Rentang Tanggal</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 text-neutral-800">
                        @forelse($data['assignments'] as $assign)
                        <tr wire:key="assignment-{{ $assign->id }}" class="hover:bg-neutral-50/50">
                            <td class="px-4 py-3 text-center font-medium text-neutral-900">{{ $assign->driver?->name ?? 'Belum Ditugaskan' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-mono text-neutral-900">{{ $assign->vehicle?->plate ?? '-' }}</span>
                                <span class="text-neutral-500 text-[11px]">({{ $assign->vehicle?->type ?? '-' }})</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-mono font-bold text-red-600">{{ $assign->booking?->code ?? '-' }}</span>
                                <span class="text-neutral-600">· {{ $assign->booking?->quotation?->lead?->name ?? 'Tamu' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center font-mono text-neutral-600">
                                {{ $assign->date_from?->format('d M') }} - {{ $assign->date_to?->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded px-1.5 py-0.5 text-[10px] font-semibold
                                    @if($assign->status === 'completed') bg-emerald-100 text-emerald-800
                                    @elseif($assign->status === 'in_progress') bg-blue-100 text-blue-800
                                    @else bg-amber-100 text-amber-800 @endif">
                                    {{ $assign->status }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-5 text-center text-xs text-neutral-500">{{ __('report.no_data') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data['assignments']->hasPages())
            <div class="border-t border-neutral-100 p-3">
                {{ $data['assignments']->links() }}
            </div>
            @endif
        </div>
    </div>
    @endif
</div>
