<x-admin-layout>
    <x-slot name="header">{{ __('nav.bookings') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.bookings.eyebrow')" :title="__('admin.bookings.index_title')" :lede="__('admin.bookings.index_lede')" />

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.bookings.booking_ref') }}</x-ui.th>
            <x-ui.th>{{ __('admin.bookings.customer') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.bookings.total') }}</x-ui.th>
            <x-ui.th>{{ __('admin.common.status') }}</x-ui.th>
            <x-ui.th>{{ __('admin.bookings.date') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($bookings as $booking)
        <x-ui.tr>
            <x-ui.td class="font-mono font-semibold text-neutral-900">{{ substr($booking->booking_number, 0, 10) }}</x-ui.td>
            <x-ui.td>{{ $booking->customer->user->name ?? ($booking->customer->full_name ?? 'N/A') }}</x-ui.td>
            <x-ui.td numeric>IDR {{ number_format($booking->total_amount) }}</x-ui.td>
            <x-ui.td><x-ui.status :status="$booking->status">{{ __('admin.common.booking_status.' . $booking->status) }}</x-ui.status></x-ui.td>
            <x-ui.td class="font-mono text-neutral-500">{{ $booking->created_at->format('d M Y') }}</x-ui.td>
            <x-ui.td numeric>
                <x-ui.button variant="ghost" :href="route('admin.bookings.show', $booking)">{{ __('admin.common.view') }}</x-ui.button>
            </x-ui.td>
        </x-ui.tr>
        @empty
        <tr><td colspan="6" class="p-0">
            <x-ui.empty :title="__('admin.bookings.no_bookings_yet')" />
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $bookings->links() }}</div>
</x-admin-layout>
