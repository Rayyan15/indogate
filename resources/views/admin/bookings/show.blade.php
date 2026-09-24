<x-admin-layout>
    <x-slot name="header">{{ __('nav.online_orders') }}</x-slot>

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
                        <dd class="mt-1 font-mono text-sm text-neutral-700">{{ \App\Support\Branch\CurrentBranch::local($booking->created_at)->format('d M Y, H:i') }}</dd>
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
            <x-ui.panel :eyebrow="__('admin.bookings.finance_eyebrow')" :title="__('admin.bookings.payment_history')">
                @if(session('error'))
                    <p class="mb-3 text-xs text-danger">{{ session('error') }}</p>
                @endif

                @if($booking->payment_proof_path)
                    <p class="text-xs text-neutral-500">{{ __('admin.bookings.proof_submitted_at', ['date' => \App\Support\Branch\CurrentBranch::local($booking->payment_submitted_at)?->format('d M Y, H:i')]) }}</p>
                    <a href="{{ route('admin.bookings.payment-proof', $booking) }}" target="_blank" class="mt-2 block">
                        <img src="{{ route('admin.bookings.payment-proof', $booking) }}" alt="{{ __('admin.bookings.payment_proof') }}" class="max-h-72 w-full rounded border border-neutral-200 object-contain">
                    </a>

                    @if($booking->status === \App\Models\Booking::STATUS_CONFIRMED && $booking->payment_verified_at)
                        <p class="mt-3 text-xs text-neutral-600">{{ __('admin.bookings.verified_by', ['name' => $booking->paymentVerifier?->name ?? '—', 'date' => \App\Support\Branch\CurrentBranch::local($booking->payment_verified_at)->format('d M Y, H:i')]) }}</p>
                    @elseif($booking->status === \App\Models\Booking::STATUS_PAYMENT_REJECTED)
                        <p class="mt-3 text-xs text-danger">{{ __('admin.bookings.rejected_reason', ['reason' => $booking->payment_rejection_reason]) }}</p>
                    @endif

                    @if($booking->status === \App\Models\Booking::STATUS_PAYMENT_SUBMITTED)
                        @can('verifyPayment', $booking)
                        <form action="{{ route('admin.bookings.verify-payment', $booking) }}" method="POST" class="mt-4">
                            @csrf
                            <x-ui.button variant="primary" type="submit" class="w-full">{{ __('admin.bookings.verify_payment') }}</x-ui.button>
                        </form>
                        <form action="{{ route('admin.bookings.reject-payment', $booking) }}" method="POST" class="mt-3 space-y-2">
                            @csrf
                            <textarea name="reason" rows="2" required maxlength="500" class="admin-input w-full" placeholder="{{ __('admin.bookings.reject_reason_placeholder') }}"></textarea>
                            @error('reason') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                            <x-ui.button variant="secondary" type="submit" class="w-full">{{ __('admin.bookings.reject_payment') }}</x-ui.button>
                        </form>
                        @endcan
                    @endif
                @else
                    <x-ui.empty :title="__('admin.bookings.no_payments_yet')" />
                @endif
            </x-ui.panel>
        </aside>
    </div>
</x-admin-layout>
