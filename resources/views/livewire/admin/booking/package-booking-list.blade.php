<div
    x-data="{ viewMode: @js($viewMode) }"
    x-init="viewMode = localStorage.getItem('bookings.view_mode') || viewMode"
>
    <x-ui.page-header :eyebrow="__('booking.eyebrow')" :title="__('booking.list.index_title')" :lede="__('booking.list.index_lede')">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" :placeholder="__('booking.list.code').'…'" class="w-48 shrink-0" />
            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 px-2 text-sm">
                <option value="">{{ __('booking.list.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ __('booking.status.'.$status->value) }}</option>
                @endforeach
            </select>
            <div class="flex shrink-0 overflow-hidden rounded border border-neutral-300">
                <button type="button"
                    x-on:click="viewMode = 'list'; localStorage.setItem('bookings.view_mode', 'list')"
                    title="{{ __('booking.list.view_list') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center border-e border-neutral-300 transition-colors duration-150"
                    :class="viewMode === 'list' ? 'bg-neutral-900 text-white' : 'bg-neutral-0 text-neutral-500 hover:bg-neutral-100'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <button type="button"
                    x-on:click="viewMode = 'calendar'; localStorage.setItem('bookings.view_mode', 'calendar')"
                    title="{{ __('booking.list.view_calendar') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center transition-colors duration-150"
                    :class="viewMode === 'calendar' ? 'bg-neutral-900 text-white' : 'bg-neutral-0 text-neutral-500 hover:bg-neutral-100'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </button>
            </div>
        </x-slot>
    </x-ui.page-header>

    <div x-cloak x-show="viewMode === 'list'">
        @if($bookings->isEmpty())
            <x-ui.empty :title="__('booking.list.no_data')" text="" />
        @else
            <x-ui.table>
                <x-slot name="head">
                    <x-ui.th sortable field="code" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('booking.list.code') }}</x-ui.th>
                    <x-ui.th>{{ __('booking.list.lead') }}</x-ui.th>
                    <x-ui.th sortable field="departure_date" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('booking.list.departure') }}</x-ui.th>
                    <x-ui.th sortable field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('booking.list.status') }}</x-ui.th>
                    <x-ui.th numeric>{{ __('booking.list.total') }}</x-ui.th>
                    <x-ui.th numeric>{{ __('catalog.common.actions') }}</x-ui.th>
                </x-slot>
                @foreach ($bookings as $booking)
                    <x-ui.tr wire:key="booking-{{ $booking->id }}">
                        <x-ui.td class="font-mono font-medium text-neutral-900">{{ $booking->code }}</x-ui.td>
                        <x-ui.td>{{ $booking->quotation->lead->name }}</x-ui.td>
                        <x-ui.td>{{ $booking->departure_date->format('d M Y') }}</x-ui.td>
                        <x-ui.td><x-ui.status :status="$booking->status->value">{{ __('booking.status.'.$booking->status->value) }}</x-ui.status></x-ui.td>
                        <x-ui.td numeric class="font-mono">{{ $booking->currency }} {{ \App\Domain\Finance\Fx::format((int) $booking->total_minor, $booking->currency) }}</x-ui.td>
                        <x-ui.td numeric>
                            <x-ui.icon-button :href="route('admin.package-bookings.show', $booking)" :title="__('booking.list.view')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </x-ui.icon-button>
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
            </x-ui.table>
            <div class="mt-5">{{ $bookings->links() }}</div>
        @endif
    </div>

    <div x-cloak x-show="viewMode === 'calendar'">
        <div class="mb-3 flex items-center justify-between">
            <button type="button" wire:click="previousMonth" class="rounded border border-neutral-300 px-2 py-1 text-xs">‹</button>
            <span class="text-sm font-medium text-neutral-900">{{ $monthStart->translatedFormat('F Y') }}</span>
            <button type="button" wire:click="nextMonth" class="rounded border border-neutral-300 px-2 py-1 text-xs">›</button>
        </div>
        <div class="grid grid-cols-7 gap-px overflow-hidden rounded border border-neutral-200 bg-neutral-200 text-xs">
            @foreach(__('booking.list.calendar_days') as $dow)
                <div class="bg-neutral-100 p-1.5 text-center font-medium text-neutral-500">{{ $dow }}</div>
            @endforeach
            @php $startOffset = $monthStart->dayOfWeekIso - 1; @endphp
            @for($i = 0; $i < $startOffset; $i++)
                <div class="min-h-[80px] bg-neutral-50"></div>
            @endfor
            @for($day = 1; $day <= $monthEnd->day; $day++)
                @php $dateKey = $monthStart->copy()->day($day)->format('Y-m-d'); @endphp
                <div class="min-h-[80px] bg-neutral-0 p-1.5">
                    <div class="text-neutral-400">{{ $day }}</div>
                    @foreach($calendarBookings->get($dateKey, collect()) as $b)
                        <a wire:key="booking-{{ $b->id }}" href="{{ route('admin.package-bookings.show', $b) }}" class="mt-0.5 block truncate rounded px-1 py-0.5 text-[10px] font-medium" style="background:color-mix(in srgb, currentColor 10%, transparent)">
                            <x-ui.status :status="$b->status->value">{{ $b->code }}</x-ui.status>
                        </a>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
</div>
