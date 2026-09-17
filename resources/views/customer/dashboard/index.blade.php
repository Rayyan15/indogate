<x-customer-layout>
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

        <div class="mb-10 flex flex-col items-start justify-between gap-6 border-b border-neutral-200 pb-8 md:flex-row md:items-end">
            <div class="flex items-center gap-5">
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded border border-neutral-200 bg-neutral-50 font-display text-2xl font-medium text-neutral-700">{{ substr(Auth::user()->name, 0, 1) }}</span>
                <div>
                    <h2 class="font-display text-3xl font-light tracking-tight text-neutral-900">{{ __('customer.dashboard.welcome', ['name' => Auth::user()->name]) }}</h2>
                    <p class="mt-1 text-sm text-neutral-500">{{ __('customer.dashboard.welcome_lede') }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-x-8">
                <x-ui.stat :label="__('customer.dashboard.total_bookings')" :value="$bookings->total()" />
                <x-ui.stat :label="__('customer.dashboard.pending')" :value="collect($bookings->items())->where('status', 'pending_payment')->count()" />
            </div>
        </div>

        <x-ui.page-header :eyebrow="__('customer.dashboard.overview')" :title="__('customer.dashboard.recent_itineraries')">
            @if($bookings->isNotEmpty())
                <x-slot name="actions"><x-ui.button variant="primary" :href="route('search.index')">{{ __('customer.dashboard.new_booking') }}</x-ui.button></x-slot>
            @endif
        </x-ui.page-header>

        @if($bookings->isEmpty())
            <x-ui.empty :title="__('customer.dashboard.no_history')" :text="__('customer.dashboard.no_history_text')">
                <x-ui.button variant="primary" :href="route('search.index')">{{ __('customer.dashboard.start_planning') }}</x-ui.button>
            </x-ui.empty>
        @else
            <x-ui.table>
                <x-slot name="head">
                    <x-ui.th>{{ __('customer.dashboard.booking_ref') }}</x-ui.th>
                    <x-ui.th>{{ __('customer.dashboard.date') }}</x-ui.th>
                    <x-ui.th numeric>{{ __('customer.dashboard.total_amount') }}</x-ui.th>
                    <x-ui.th>{{ __('customer.dashboard.status') }}</x-ui.th>
                    <x-ui.th numeric>{{ __('customer.dashboard.action') }}</x-ui.th>
                </x-slot>
                @foreach($bookings as $booking)
                    <x-ui.tr>
                        <x-ui.td class="font-mono font-semibold text-neutral-900">{{ substr($booking->booking_number, 0, 8) }}</x-ui.td>
                        <x-ui.td class="font-mono text-neutral-500">{{ $booking->created_at->format('d M Y') }}</x-ui.td>
                        <x-ui.td numeric>IDR {{ number_format($booking->total_amount) }}</x-ui.td>
                        <x-ui.td><x-ui.status :status="$booking->status">{{ __('admin.common.booking_status.' . $booking->status) }}</x-ui.status></x-ui.td>
                        <x-ui.td numeric><x-ui.button variant="ghost" :href="route('customer.bookings.show', $booking)">{{ __('customer.dashboard.view') }}</x-ui.button></x-ui.td>
                    </x-ui.tr>
                @endforeach
            </x-ui.table>
            <div class="mt-5">{{ $bookings->links() }}</div>
        @endif
    </div>
</x-customer-layout>
