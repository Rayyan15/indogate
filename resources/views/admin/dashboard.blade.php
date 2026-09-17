<x-admin-layout>
    <x-slot name="header">{{ __('nav.dashboard') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.dashboard.eyebrow')" :title="__('admin.dashboard.title')" :lede="__('admin.dashboard.lede')" />

    <div class="mb-10 grid grid-cols-2 gap-x-6 gap-y-8 lg:grid-cols-4">
        <x-ui.stat :label="__('admin.dashboard.total_bookings')" :value="$stats['total_bookings']" :note="__('admin.dashboard.all_time')" :href="route('admin.bookings.index')" />
        <x-ui.stat :label="__('admin.dashboard.pending_payments')" :value="$stats['pending_payments']" :note="__('admin.dashboard.awaiting_verification')" :href="route('admin.payments.index')" />
        <x-ui.stat :label="__('admin.dashboard.confirmed')" :value="$stats['confirmed_bookings']" :note="__('admin.dashboard.bookings_confirmed')" />
        <x-ui.stat :label="__('admin.dashboard.customers')" :value="$stats['total_customers']" :note="__('admin.dashboard.registered_users')" />
    </div>

    <x-ui.panel :eyebrow="__('admin.dashboard.inventory_eyebrow')" :title="__('admin.dashboard.inventory_title')" class="mb-10" flush>
        <div class="grid grid-cols-1 divide-y divide-neutral-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
            <div class="p-5">
                <x-ui.eyebrow>{{ __('admin.dashboard.hotels_listed') }}</x-ui.eyebrow>
                <p class="mt-2 font-mono text-2xl font-medium text-neutral-900">{{ $stats['total_hotels'] }}</p>
            </div>
            <div class="p-5">
                <x-ui.eyebrow>{{ __('admin.dashboard.flight_routes') }}</x-ui.eyebrow>
                <p class="mt-2 font-mono text-2xl font-medium text-neutral-900">{{ $stats['total_flights'] }}</p>
            </div>
            <div class="p-5">
                <x-ui.eyebrow>{{ __('admin.dashboard.active_drivers') }}</x-ui.eyebrow>
                <p class="mt-2 font-mono text-2xl font-medium text-neutral-900">{{ $stats['total_drivers'] }}</p>
            </div>
        </div>
    </x-ui.panel>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <x-ui.panel :eyebrow="__('admin.dashboard.pipeline_eyebrow')" :title="__('admin.dashboard.recent_bookings')" flush>
            <x-slot name="actions"><x-ui.button variant="ghost" :href="route('admin.bookings.index')">{{ __('admin.dashboard.view_all') }}</x-ui.button></x-slot>
            <div class="divide-y divide-neutral-100">
                @forelse($recent_bookings as $booking)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div>
                        <p class="font-mono text-sm font-medium text-neutral-900">{{ substr($booking->booking_number, 0, 8) }}</p>
                        <p class="text-xs text-neutral-500">{{ $booking->customer->user->name ?? 'N/A' }} · {{ $booking->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm font-semibold text-neutral-900">IDR {{ number_format($booking->total_amount) }}</span>
                        <x-ui.status :status="$booking->status">{{ __('admin.common.booking_status.' . $booking->status) }}</x-ui.status>
                    </div>
                </div>
                @empty
                <div class="p-0"><x-ui.empty :title="__('admin.dashboard.no_bookings_yet')" /></div>
                @endforelse
            </div>
        </x-ui.panel>

        <x-ui.panel :eyebrow="__('admin.dashboard.finance_eyebrow')" :title="__('admin.dashboard.pending_verifications')" flush>
            <x-slot name="actions"><x-ui.button variant="ghost" :href="route('admin.payments.index')">{{ __('admin.dashboard.view_all') }}</x-ui.button></x-slot>
            <div class="divide-y divide-neutral-100">
                @forelse($recent_payments as $payment)
                <div class="flex items-center justify-between px-5 py-3.5">
                    <div>
                        <p class="text-sm font-medium text-neutral-900">{{ $payment->booking->customer->user->name ?? 'N/A' }}</p>
                        <p class="text-xs text-neutral-500">{{ $payment->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-sm font-semibold text-neutral-900">IDR {{ number_format($payment->amount) }}</span>
                        <x-ui.button variant="ghost" :href="route('admin.payments.show', $payment)">{{ __('admin.common.review') }}</x-ui.button>
                    </div>
                </div>
                @empty
                <div class="p-0"><x-ui.empty :title="__('admin.dashboard.all_clear')" :text="__('admin.dashboard.no_pending_payments')" /></div>
                @endforelse
            </div>
        </x-ui.panel>
    </div>
</x-admin-layout>
