<div class="space-y-6">
    <x-ui.page-header
        :eyebrow="now()->translatedFormat('l, j F Y')"
        :title="__('desk.greeting', ['name' => auth()->user()->name])"
        :lede="__('desk.lede')">
        <x-slot name="actions">
            @if($canLeads)
                <x-ui.button variant="primary" :href="route('admin.leads.create')">{{ __('desk.new_lead') }}</x-ui.button>
                <x-ui.button :href="route('admin.leads.index')">{{ __('desk.all_leads') }}</x-ui.button>
            @endif
            @if($canBookings)
                <x-ui.button :href="route('admin.package-bookings.index')">{{ __('desk.all_bookings') }}</x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    {{-- One box for "customer X is asking about their booking" --}}
    <x-ui.panel>
        <label for="desk-search" class="mb-2 block text-xs font-semibold text-neutral-700">{{ __('desk.search_label') }}</label>
        <x-ui.search-input id="desk-search" wire:model.live.debounce.300ms="search" :placeholder="__('desk.search_placeholder')" class="w-full" />

        @if($results)
            <div class="mt-4 grid gap-4 md:grid-cols-2" wire:loading.class="opacity-50">
                @if($canLeads)
                    <div>
                        <x-ui.eyebrow>{{ __('desk.results_leads') }}</x-ui.eyebrow>
                        <ul class="mt-2 divide-y divide-neutral-100">
                            @forelse($results['leads'] as $lead)
                                <li wire:key="sr-lead-{{ $lead->id }}">
                                    <a href="{{ route('admin.leads.edit', $lead) }}" class="flex items-center justify-between gap-3 py-2 text-sm hover:text-red-600">
                                        <span class="truncate text-start">{{ $lead->name }} <span class="text-xs text-neutral-500" dir="ltr">{{ $lead->phone }}</span></span>
                                        <x-ui.status :status="$lead->status->value">{{ __('lead.status.'.$lead->status->value) }}</x-ui.status>
                                    </a>
                                </li>
                            @empty
                                <li class="py-2 text-xs text-neutral-500">{{ __('desk.no_results') }}</li>
                            @endforelse
                        </ul>
                    </div>
                @endif
                @if($canBookings)
                    <div>
                        <x-ui.eyebrow>{{ __('desk.results_bookings') }}</x-ui.eyebrow>
                        <ul class="mt-2 divide-y divide-neutral-100">
                            @forelse($results['bookings'] as $booking)
                                <li wire:key="sr-booking-{{ $booking->id }}">
                                    <a href="{{ route('admin.package-bookings.show', $booking) }}" class="flex items-center justify-between gap-3 py-2 text-sm hover:text-red-600">
                                        <span class="truncate text-start"><span class="font-mono">{{ $booking->code }}</span> · {{ $booking->quotation?->lead?->name }}</span>
                                        <x-ui.status :status="$booking->status->value">{{ __('booking.status.'.$booking->status->value) }}</x-ui.status>
                                    </a>
                                </li>
                            @empty
                                <li class="py-2 text-xs text-neutral-500">{{ __('desk.no_results') }}</li>
                            @endforelse
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    </x-ui.panel>

    {{-- Counters: what is waiting on me --}}
    <div class="grid grid-cols-2 gap-y-6 rounded border border-neutral-200 bg-neutral-0 py-5 lg:grid-cols-4">
        @if($canLeads)
            <x-ui.stat :label="__('desk.stat_follow_up')" :value="$leads['count']" :href="route('admin.leads.index')" />
            <x-ui.stat :label="__('desk.stat_quotations')" :value="$quotations['count']" />
        @endif
        @if($canBookings)
            <x-ui.stat :label="__('desk.stat_awaiting_payment')" :value="$awaitingPayment['count']" :href="route('admin.package-bookings.index')" />
            <x-ui.stat :label="__('desk.stat_online_orders')" :value="$onlineOrders" :href="route('admin.bookings.index')" />
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @if($canLeads)
            <x-ui.panel :eyebrow="__('desk.step_1')" :title="__('desk.follow_up_title')" flush>
                <ul class="divide-y divide-neutral-100">
                    @forelse($leads['items'] as $lead)
                        <li wire:key="lead-{{ $lead->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0 text-start">
                                <a href="{{ route('admin.leads.edit', $lead) }}" class="block truncate text-sm font-semibold text-neutral-900 hover:text-red-600">{{ $lead->name }}</a>
                                <p class="text-xs text-neutral-500">
                                    {{ __('lead.source.'.$lead->source->value) }} ·
                                    @if($lead->isFollowUpDue())
                                        <span class="text-danger">{{ __('desk.follow_up_due', ['time' => $lead->follow_up_at->diffForHumans()]) }}</span>
                                    @else
                                        {{ __('desk.came_in', ['time' => $lead->created_at->diffForHumans()]) }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <x-ui.status :status="$lead->status->value">{{ __('lead.status.'.$lead->status->value) }}</x-ui.status>
                                @if($digits = preg_replace('/\D/', '', (string) $lead->phone))
                                    <a href="https://wa.me/{{ $digits }}" target="_blank" rel="noopener" class="rounded border border-neutral-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">{{ __('desk.whatsapp') }}</a>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.follow_up_empty')" /></li>
                    @endforelse
                </ul>
            </x-ui.panel>

            <x-ui.panel :eyebrow="__('desk.step_2')" :title="__('desk.quotations_title')" flush>
                <ul class="divide-y divide-neutral-100">
                    @forelse($quotations['items'] as $quotation)
                        @php $expiringSoon = $quotation->valid_until->lte(now()->addHours(48)); @endphp
                        <li wire:key="quotation-{{ $quotation->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0 text-start">
                                <a href="{{ $quotation->lead ? route('admin.leads.edit', $quotation->lead) : '#' }}" class="block truncate text-sm font-semibold text-neutral-900 hover:text-red-600">{{ $quotation->lead?->name }}</a>
                                <p class="truncate text-xs text-neutral-500">{{ $quotation->package?->name }}</p>
                            </div>
                            <span @class(['shrink-0 text-xs', 'font-semibold text-danger' => $expiringSoon, 'text-neutral-500' => ! $expiringSoon])>
                                {{ __('desk.valid_until', ['time' => $quotation->valid_until->diffForHumans()]) }}
                            </span>
                        </li>
                    @empty
                        <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.quotations_empty')" /></li>
                    @endforelse
                </ul>
            </x-ui.panel>
        @endif

        @if($canBookings)
            @php $gatewayOn = app(\App\Domain\Finance\Services\GatewayCheckout::class)->isEnabled(); @endphp
            <x-ui.panel :eyebrow="__('desk.step_3')" :title="__('desk.awaiting_payment_title')" flush>
                <ul class="divide-y divide-neutral-100">
                    @forelse($awaitingPayment['items'] as $booking)
                        <li wire:key="pay-{{ $booking->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0 text-start">
                                <a href="{{ route('admin.package-bookings.show', $booking) }}" class="block truncate text-sm font-semibold text-neutral-900 hover:text-red-600"><span class="font-mono">{{ $booking->code }}</span> · {{ $booking->quotation?->lead?->name }}</a>
                                <p class="text-xs text-neutral-500">{{ __('desk.remaining') }} <span class="font-mono tabular-nums" dir="ltr">{{ \App\Domain\Finance\Fx::format($booking->remainingBalanceMinor(), $booking->currency) }}</span></p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if($gatewayOn)
                                    <a href="{{ \App\Support\PaymentLinkMessage::whatsappUrl($booking) }}" target="_blank" rel="noopener"
                                       class="rounded border border-success/30 bg-success/10 px-2 py-1 text-[11px] font-medium text-success hover:bg-success/20">{{ __('payment.admin.send_link') }}</a>
                                @endif
                                <x-ui.status :status="$booking->status->value">{{ __('booking.status.'.$booking->status->value) }}</x-ui.status>
                            </div>
                        </li>
                    @empty
                        <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.awaiting_payment_empty')" /></li>
                    @endforelse
                </ul>
            </x-ui.panel>

            <x-ui.panel :eyebrow="__('desk.step_4')" :title="__('desk.departing_title')" flush>
                <ul class="divide-y divide-neutral-100">
                    @forelse($departing['items'] as $booking)
                        <li wire:key="dep-{{ $booking->id }}" class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="min-w-0 text-start">
                                <a href="{{ route('admin.package-bookings.show', $booking) }}" class="block truncate text-sm font-semibold text-neutral-900 hover:text-red-600"><span class="font-mono">{{ $booking->code }}</span> · {{ $booking->quotation?->lead?->name }}</a>
                                <p class="text-xs text-neutral-500">{{ $booking->departure_date->isToday() ? __('desk.today') : __('desk.tomorrow') }}</p>
                            </div>
                            <x-ui.status :status="$booking->status->value">{{ __('booking.status.'.$booking->status->value) }}</x-ui.status>
                        </li>
                    @empty
                        <li class="p-5"><x-ui.empty :title="__('desk.all_clear')" :text="__('desk.departing_empty')" /></li>
                    @endforelse
                </ul>
            </x-ui.panel>
        @endif
    </div>
</div>
