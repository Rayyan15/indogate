<div>
    <x-ui.page-header :eyebrow="'M9 · '.__('finance.finance')" :title="__('finance.margin_report')" :lede="__('finance.margin_lede', ['branch' => \App\Support\Branch\CurrentBranch::model()?->name])">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" :placeholder="__('finance.booking_search_ph')" class="w-56 shrink-0" />

            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[140px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                <option value="">{{ __('finance.margin_all_status') }}</option>
                <option value="confirmed">Confirmed</option>
                <option value="partially_paid">Partially Paid</option>
                <option value="paid">Paid</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
            </select>
        </x-slot>
    </x-ui.page-header>

    {{-- Financial Summary KPIs --}}
    <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded border border-neutral-200 bg-neutral-0 p-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-neutral-500">{{ __('finance.gross_revenue') }}</div>
            <div class="mt-1 text-lg font-bold text-neutral-900">IDR {{ number_format($summary['total_gross_idr'], 0, ',', '.') }}</div>
        </div>

        <div class="rounded border border-rose-200 bg-rose-50 p-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">{{ __('finance.channel_fees') }}</div>
            <div class="mt-1 text-lg font-bold text-rose-900">- IDR {{ number_format($summary['total_fees_idr'], 0, ',', '.') }}</div>
        </div>

        <div class="rounded border border-amber-200 bg-amber-50 p-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">{{ __('finance.vendor_costs') }}</div>
            <div class="mt-1 text-lg font-bold text-amber-900">- IDR {{ number_format($summary['total_vendor_idr'], 0, ',', '.') }}</div>
        </div>

        <div class="rounded border border-emerald-200 bg-emerald-50 p-3 shadow-sm">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">{{ __('finance.actual_margin') }} ({{ $summary['overall_margin_percentage'] }}%)</div>
            <div class="mt-1 text-lg font-bold text-emerald-900">IDR {{ number_format($summary['total_margin_idr'], 0, ',', '.') }}</div>
        </div>
    </div>

    @if(empty($rows))
        <x-ui.empty :title="__('finance.margin_report')" :text="__('finance.margin_empty')" />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('finance.booking_code') }}</x-ui.th>
                <x-ui.th>{{ __('finance.guest_lead') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.gross_revenue') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.channel_fees') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.net_revenue') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.vendor_costs') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.actual_margin') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.margin_percentage') }}</x-ui.th>
            </x-slot>
            @foreach($rows as $row)
                @php $b = $row['booking']; @endphp
                <x-ui.tr wire:key="margin-{{ $b->id }}">
                    <x-ui.td>
                        <a href="{{ route('admin.package-bookings.show', $b->id) }}" class="font-mono font-bold text-red-600 hover:text-red-700 hover:underline">
                            {{ $b->code }}
                        </a>
                        <div class="text-[10px] text-neutral-500">
                            {{ strtoupper($b->status->value) }}
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="font-medium text-neutral-900">{{ $b->quotation?->lead?->name ?? '—' }}</div>
                        <div class="text-xs text-neutral-500">{{ $b->currency }} ({{ number_format($b->total_minor) }})</div>
                    </x-ui.td>
                    <x-ui.td numeric class="font-medium text-neutral-900">
                        {{ number_format($row['gross_revenue_idr'], 0, ',', '.') }}
                    </x-ui.td>
                    <x-ui.td numeric class="text-xs font-semibold text-rose-600">
                        @if($row['channel_fees_idr'] > 0)
                            -{{ number_format($row['channel_fees_idr'], 0, ',', '.') }}
                        @else
                            <span class="text-neutral-400">0</span>
                        @endif
                    </x-ui.td>
                    <x-ui.td numeric class="font-medium text-neutral-800">
                        {{ number_format($row['net_revenue_idr'], 0, ',', '.') }}
                    </x-ui.td>
                    <x-ui.td numeric class="text-xs font-medium text-amber-700">
                        {{ number_format($row['vendor_costs_idr'], 0, ',', '.') }}
                    </x-ui.td>
                    <x-ui.td numeric>
                        <span class="font-bold {{ $row['actual_margin_idr'] >= 0 ? 'text-emerald-700' : 'text-red-600' }}">
                            {{ number_format($row['actual_margin_idr'], 0, ',', '.') }}
                        </span>
                    </x-ui.td>
                    <x-ui.td numeric>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold {{ $row['margin_percentage'] >= 15 ? 'bg-emerald-50 text-emerald-700' : ($row['margin_percentage'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                            {{ number_format($row['margin_percentage'], 1) }}%
                        </span>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
