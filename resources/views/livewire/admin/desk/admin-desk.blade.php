<div class="space-y-6">
    <x-ui.page-header
        :eyebrow="now()->translatedFormat('l, j F Y')"
        :title="__('desk.admin_title')"
        :lede="__('desk.admin_lede')" />

    @if($kpi)
        <div class="grid grid-cols-2 gap-y-6 rounded border border-neutral-200 bg-neutral-0 py-5 lg:grid-cols-4">
            <x-ui.stat :label="__('desk.kpi_bookings')" :value="$kpi['bookings']" :href="route('admin.package-bookings.index')" />
            <x-ui.stat :label="__('desk.kpi_revenue')" :value="\App\Domain\Finance\Fx::format($kpi['revenue'], 'IDR')" />
            <x-ui.stat :label="__('desk.kpi_leads')" :value="$kpi['funnel']['total_leads']" :href="route('admin.leads.index')" />
            <x-ui.stat :label="__('desk.kpi_conversion')" :value="$kpi['funnel']['conversion_rate'].'%'" />
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.panel :title="__('desk.departing_title')" flush>
            <ul class="divide-y divide-neutral-100">
                @forelse($departures as $booking)
                    <li wire:key="dep-{{ $booking->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                        <a href="{{ route('admin.package-bookings.show', $booking) }}" class="min-w-0 truncate text-start text-sm font-semibold text-neutral-900 hover:text-red-600">
                            <span class="font-mono">{{ $booking->code }}</span> · {{ $booking->quotation?->lead?->name }}
                        </a>
                        <span class="flex shrink-0 items-center gap-2 text-xs text-neutral-500">
                            {{ $booking->departure_date->isToday() ? __('desk.today') : __('desk.tomorrow') }}
                            <x-ui.status :status="$booking->status->value">{{ __('booking.status.'.$booking->status->value) }}</x-ui.status>
                        </span>
                    </li>
                @empty
                    <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.departing_empty')" /></li>
                @endforelse
            </ul>
        </x-ui.panel>

        <x-ui.panel :title="__('desk.no_driver_title')" flush>
            <ul class="divide-y divide-neutral-100">
                @forelse($withoutDriver as $booking)
                    <li wire:key="nodrv-{{ $booking->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                        <a href="{{ route('admin.package-bookings.show', $booking) }}" class="min-w-0 truncate text-start text-sm font-semibold text-neutral-900 hover:text-red-600">
                            <span class="font-mono">{{ $booking->code }}</span> · {{ $booking->quotation?->lead?->name }}
                        </a>
                        <span class="flex shrink-0 items-center gap-2 text-xs text-neutral-500">
                            {{ $booking->departure_date->translatedFormat('j M') }}
                            @can('driver.assign')
                                <a href="{{ route('admin.fleet.calendar') }}" class="rounded border border-neutral-300 px-2 py-1 font-semibold text-neutral-700 hover:bg-neutral-50">{{ __('desk.open_calendar') }}</a>
                            @endcan
                        </span>
                    </li>
                @empty
                    <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.no_driver_empty')" /></li>
                @endforelse
            </ul>
        </x-ui.panel>

        <x-ui.panel :title="__('desk.unpaid_title')" flush>
            <ul class="divide-y divide-neutral-100">
                @forelse($unpaid as $booking)
                    <li wire:key="unpaid-{{ $booking->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                        <a href="{{ route('admin.package-bookings.show', $booking) }}" class="min-w-0 truncate text-start text-sm font-semibold text-neutral-900 hover:text-red-600">
                            <span class="font-mono">{{ $booking->code }}</span> · {{ $booking->quotation?->lead?->name }}
                        </a>
                        <span class="shrink-0 text-xs text-neutral-500">
                            {{ $booking->departure_date->translatedFormat('j M') }} ·
                            <span class="font-mono tabular-nums" dir="ltr">{{ \App\Domain\Finance\Fx::format($booking->remainingBalanceMinor(), $booking->currency) }}</span>
                        </span>
                    </li>
                @empty
                    <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.unpaid_empty')" /></li>
                @endforelse
            </ul>
        </x-ui.panel>

        <x-ui.panel :title="__('desk.cancellations_title')" flush>
            <ul class="divide-y divide-neutral-100">
                @forelse($cancellations as $entry)
                    <li wire:key="cxl-{{ $entry->id }}" class="px-5 py-3 text-start">
                        <a href="{{ route('admin.package-bookings.show', $entry->booking) }}" class="block truncate text-sm font-semibold text-neutral-900 hover:text-red-600">
                            <span class="font-mono">{{ $entry->booking->code }}</span> · {{ $entry->booking->quotation?->lead?->name }}
                        </a>
                        <p class="text-xs text-neutral-500">
                            {{ $entry->user?->name ?? '—' }} · {{ $entry->created_at->diffForHumans() }}
                            @if($entry->reason) · {{ $entry->reason }} @endif
                        </p>
                    </li>
                @empty
                    <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.cancellations_empty')" /></li>
                @endforelse
            </ul>
        </x-ui.panel>
    </div>

    <x-ui.panel :title="__('desk.team_title')" flush>
        <x-ui.table class="border-0">
            <x-slot name="head">
                <x-ui.th>{{ __('desk.team_cs') }}</x-ui.th>
                <x-ui.th>{{ __('desk.team_active') }}</x-ui.th>
                <x-ui.th>{{ __('desk.team_overdue') }}</x-ui.th>
                <x-ui.th>{{ __('desk.team_bookings') }}</x-ui.th>
            </x-slot>
            @forelse($team as $cs)
                <x-ui.tr wire:key="cs-{{ $cs->id }}">
                    <x-ui.td class="text-start">{{ $cs->name }}</x-ui.td>
                    <x-ui.td :numeric="true">{{ $cs->active_leads }}</x-ui.td>
                    <x-ui.td :numeric="true" @class(['text-danger' => $cs->overdue_follow_ups > 0])>{{ $cs->overdue_follow_ups }}</x-ui.td>
                    <x-ui.td :numeric="true">{{ $cs->month_bookings }}</x-ui.td>
                </x-ui.tr>
            @empty
                <tr><td colspan="4" class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.team_empty')" /></td></tr>
            @endforelse
        </x-ui.table>
    </x-ui.panel>

    @can('lead.manage')
        <x-ui.panel :title="__('desk.unassigned_title')" flush>
            <form wire:submit="assign">
                <ul class="divide-y divide-neutral-100">
                    @forelse($unassigned as $lead)
                        <li wire:key="un-{{ $lead->id }}" class="flex items-center gap-3 px-5 py-3">
                            <input type="checkbox" wire:model="selectedLeads" value="{{ $lead->id }}" id="ul-{{ $lead->id }}" class="rounded border-neutral-300 text-red-600 focus:ring-red-600">
                            <label for="ul-{{ $lead->id }}" class="min-w-0 flex-1 truncate text-start text-sm text-neutral-900">
                                {{ $lead->name }} <span class="text-xs text-neutral-500">{{ __('lead.source.'.$lead->source->value) }} · {{ $lead->created_at->diffForHumans() }}</span>
                            </label>
                            <x-ui.status :status="$lead->status->value">{{ __('lead.status.'.$lead->status->value) }}</x-ui.status>
                        </li>
                    @empty
                        <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.unassigned_empty')" /></li>
                    @endforelse
                </ul>
                @if($unassigned->isNotEmpty())
                    <div class="flex flex-wrap items-center gap-3 border-t border-neutral-200 px-5 py-4">
                        <select wire:model="assignTo" aria-label="{{ __('desk.assign_to') }}" class="h-9 min-w-[160px] rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                            <option value="">{{ __('desk.assign_to') }}</option>
                            @foreach($assignable as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                        <x-ui.button type="submit" variant="primary">{{ __('desk.assign') }}</x-ui.button>
                        @error('selectedLeads') <span class="text-xs text-danger">{{ __('desk.select_leads_first') }}</span> @enderror
                        @error('assignTo') <span class="text-xs text-danger">{{ __('desk.select_cs_first') }}</span> @enderror
                    </div>
                @endif
            </form>
        </x-ui.panel>
    @endcan

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.panel :title="__('desk.catalog_title')" flush>
            <ul class="divide-y divide-neutral-100">
                @foreach($noPrice as $package)
                    <li wire:key="np-{{ $package->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                        <span class="truncate text-start text-sm text-neutral-900">{{ $package->name }}</span>
                        <span class="shrink-0 text-xs font-semibold text-danger">{{ __('desk.catalog_no_price') }}</span>
                    </li>
                @endforeach
                @foreach($expiringRates as $package)
                    <li wire:key="er-{{ $package->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                        <span class="truncate text-start text-sm text-neutral-900">{{ $package->name }}</span>
                        <span class="shrink-0 text-xs font-semibold text-amber-700">{{ __('desk.catalog_rates_expiring') }}</span>
                    </li>
                @endforeach
                @if($noPrice->isEmpty() && $expiringRates->isEmpty())
                    <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.catalog_empty')" /></li>
                @endif
            </ul>
        </x-ui.panel>

        <x-ui.panel :title="__('desk.payments_title')">
            <x-ui.stat :label="__('desk.payments_pending')" :value="$pendingPayments" :href="auth()->user()->can('payment.verify') ? route('admin.finance.payments') : null" />
            <p class="mt-3 text-xs text-neutral-500">{{ __('desk.payments_note') }}</p>
        </x-ui.panel>
    </div>
</div>
