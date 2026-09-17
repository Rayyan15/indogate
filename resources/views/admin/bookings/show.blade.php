<x-admin-layout>
    <x-slot name="header">{{ __('nav.bookings') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.bookings.eyebrow')" :title="strtoupper(substr($booking->booking_number, 0, 10))">
        <x-slot name="actions"><x-ui.status :status="$booking->status">{{ __('admin.common.booking_status.' . $booking->status) }}</x-ui.status></x-slot>
    </x-ui.page-header>

    <x-ui.button variant="ghost" :href="route('admin.bookings.index')" class="mb-6">{{ __('admin.bookings.back_to_bookings') }}</x-ui.button>

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <main class="space-y-6 lg:col-span-8">
            <x-ui.panel :title="__('admin.bookings.info_panel')">
                <dl class="grid grid-cols-2 gap-6">
                    <div>
                        <x-ui.eyebrow>{{ __('admin.bookings.customer') }}</x-ui.eyebrow>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $booking->customer->user->name ?? ($booking->customer->full_name ?? 'N/A') }}</dd>
                    </div>
                    <div>
                        <x-ui.eyebrow>{{ __('admin.bookings.total_amount') }}</x-ui.eyebrow>
                        <dd class="mt-1 font-mono text-lg font-semibold text-neutral-900">IDR {{ number_format($booking->total_amount) }}</dd>
                    </div>
                    <div>
                        <x-ui.eyebrow>{{ __('admin.bookings.booking_date') }}</x-ui.eyebrow>
                        <dd class="mt-1 font-mono text-sm text-neutral-700">{{ $booking->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div>
                        <x-ui.eyebrow>{{ __('admin.bookings.assigned_driver') }}</x-ui.eyebrow>
                        <dd class="mt-1 text-sm text-neutral-700">{{ $booking->driver?->full_name ?? __('admin.bookings.not_assigned') }}</dd>
                    </div>
                </dl>
            </x-ui.panel>

            <x-ui.panel :eyebrow="__('admin.bookings.itinerary_eyebrow')" :title="__('admin.bookings.services_booked')" flush>
                <div class="divide-y divide-neutral-100">
                    @forelse($booking->items as $item)
                    <x-ui.segment :index="$loop->iteration" :kicker="class_basename($item->bookable_type)" :title="class_basename($item->bookable_type) . ' #' . $item->bookable_id" :meta="'Qty: ' . $item->quantity . ' × IDR ' . number_format($item->unit_price)" :amount="'IDR ' . number_format($item->subtotal)" />
                    @empty
                    <x-ui.empty :title="__('admin.bookings.no_items')" />
                    @endforelse
                </div>
            </x-ui.panel>
        </main>

        <aside class="space-y-6 lg:col-span-4">
            <x-ui.panel :title="__('admin.bookings.assign_driver')">
                <form action="{{ route('admin.bookings.assign-driver', $booking) }}" method="POST" class="space-y-4">
                    @csrf
                    <select name="driver_id" class="admin-input w-full">
                        <option value="">{{ __('admin.bookings.select_driver') }}</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ $booking->driver_id == $driver->id ? 'selected' : '' }}>{{ $driver->full_name }} ({{ ucfirst($driver->gender) }})</option>
                        @endforeach
                    </select>
                    <x-ui.button variant="primary" type="submit" class="w-full">{{ __('admin.bookings.assign_driver') }}</x-ui.button>
                </form>
            </x-ui.panel>

            <x-ui.panel :eyebrow="__('admin.bookings.finance_eyebrow')" :title="__('admin.bookings.payment_history')" flush>
                <div class="divide-y divide-neutral-100">
                    @forelse($booking->payments as $payment)
                    <div class="px-5 py-3.5">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-sm font-medium text-neutral-900">IDR {{ number_format($payment->amount) }}</span>
                            @if($payment->status === 'pending')
                                <x-ui.button variant="ghost" :href="route('admin.payments.show', $payment)">{{ __('admin.common.review') }}</x-ui.button>
                            @else
                                <x-ui.status :status="$payment->status === 'verified' ? 'paid' : 'cancelled'">{{ __('admin.common.booking_status.' . $payment->status) }}</x-ui.status>
                            @endif
                        </div>
                        <p class="mt-1 text-xs text-neutral-500">{{ $payment->created_at->diffForHumans() }}</p>
                    </div>
                    @empty
                    <x-ui.empty :title="__('admin.bookings.no_payments_yet')" />
                    @endforelse
                </div>
            </x-ui.panel>
        </aside>
    </div>
</x-admin-layout>
