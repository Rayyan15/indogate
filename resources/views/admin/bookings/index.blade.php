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
            <x-ui.th>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($bookings as $booking)
        <x-ui.tr>
            <x-ui.td class="font-mono font-semibold text-neutral-900">{{ substr($booking->booking_number, 0, 10) }}</x-ui.td>
            <x-ui.td>{{ $booking->customer->user->name ?? ($booking->customer->full_name ?? 'N/A') }}</x-ui.td>
            <x-ui.td numeric>IDR {{ number_format($booking->total_amount) }}</x-ui.td>
            <x-ui.td><x-ui.status :status="$booking->status">{{ __('admin.common.booking_status.' . $booking->status) }}</x-ui.status></x-ui.td>
            <x-ui.td class="font-mono text-neutral-500">{{ $booking->created_at->format('d M Y') }}</x-ui.td>
            <x-ui.td>
                <div class="flex items-center justify-center">
                    <x-ui.icon-button :href="route('admin.bookings.show', $booking)" :title="__('admin.common.view')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </x-ui.icon-button>
                </div>
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
