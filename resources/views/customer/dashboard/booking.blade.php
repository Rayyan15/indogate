<x-customer-layout>
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">

        <x-ui.button variant="ghost" :href="route('dashboard')" class="mb-6">{{ __('customer.booking.back_to_dashboard') }}</x-ui.button>

        <x-ui.page-header :eyebrow="__('customer.booking.ref_eyebrow', ['ref' => strtoupper(substr($booking->booking_number, 0, 8))])" :title="__('customer.booking.summary_title')" :lede="__('customer.booking.issued', ['date' => $booking->created_at->format('d M Y')])">
            <x-slot name="actions"><x-ui.status :status="$booking->status">{{ __('admin.common.booking_status.' . $booking->status) }}</x-ui.status></x-slot>
        </x-ui.page-header>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <main class="space-y-6 lg:col-span-8">
                <x-ui.panel :eyebrow="__('customer.booking.itinerary_eyebrow')" :title="__('customer.booking.reserved_services')" flush>
                    <div class="divide-y divide-neutral-100">
                        @foreach($booking->items as $item)
                            <x-ui.segment :index="$loop->iteration" :kicker="class_basename($item->bookable_type) . ' ' . __('customer.cart.service')" :title="'Ref #' . $item->bookable_id" :meta="'Qty: ' . $item->quantity . ' × IDR ' . number_format($item->unit_price)" :amount="'IDR ' . number_format($item->subtotal)" />
                        @endforeach
                    </div>
                </x-ui.panel>

                @if($booking->payments->isNotEmpty())
                <x-ui.panel :eyebrow="__('customer.booking.finance_eyebrow')" :title="__('customer.booking.payment_history')" flush>
                    <div class="divide-y divide-neutral-100">
                        @foreach($booking->payments as $payment)
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <span class="font-mono text-sm font-medium text-neutral-900">IDR {{ number_format($payment->amount) }}</span>
                                    <p class="mt-0.5 text-xs text-neutral-500">{{ __('customer.booking.submitted', ['date' => $payment->created_at->format('d M Y, H:i')]) }}</p>
                                </div>
                                <x-ui.status :status="$payment->status === 'verified' ? 'paid' : ($payment->status === 'rejected' ? 'cancelled' : 'pending')">
                                    {{ $payment->status === 'pending' ? __('customer.booking.in_review') : __('admin.common.booking_status.' . $payment->status) }}
                                </x-ui.status>
                            </div>
                        @endforeach
                    </div>
                </x-ui.panel>
                @endif
            </main>

            <aside class="space-y-6 lg:col-span-4">
                <x-ui.folio :eyebrow="__('customer.booking.invoice_eyebrow')" :title="__('customer.booking.grand_total')"
                    :rows="[[__('customer.booking.subtotal'), 'IDR ' . number_format($booking->total_amount)], [__('customer.booking.taxes_fees'), __('customer.booking.included')]]"
                    :total-label="__('customer.booking.grand_total')" :total="'IDR ' . number_format($booking->total_amount)">

                    @if($booking->status === 'pending_payment' || str_contains($booking->status, 'rejected'))
                        <div class="mt-6 border-t border-neutral-200 pt-6">
                            <x-ui.eyebrow>{{ __('customer.booking.bank_transfer') }}</x-ui.eyebrow>
                            <dl class="mt-2 space-y-1.5 text-xs">
                                <div class="flex justify-between"><dt class="text-neutral-500">{{ __('customer.booking.bank') }}</dt><dd class="font-medium text-neutral-900">Bank Mandiri</dd></div>
                                <div class="flex justify-between"><dt class="text-neutral-500">{{ __('customer.booking.account_no') }}</dt><dd class="font-mono font-medium text-neutral-900">123-456-7890</dd></div>
                                <div class="flex justify-between"><dt class="text-neutral-500">{{ __('customer.booking.beneficiary') }}</dt><dd class="font-medium text-neutral-900">PT Indogate Travel</dd></div>
                            </dl>

                            <form action="{{ route('customer.payments.store', $booking) }}" method="POST" enctype="multipart/form-data" class="mt-5">
                                @csrf
                                <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-600">{{ __('customer.booking.upload_receipt') }}</label>
                                <div class="relative rounded border-2 border-dashed border-neutral-300 bg-neutral-50 p-5 text-center transition hover:border-red-600">
                                    <input type="file" name="proof" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" accept="image/*" required id="file-upload">
                                    <div class="pointer-events-none">
                                        <p class="text-sm font-medium text-neutral-700" id="file-name">{{ __('customer.booking.upload_hint') }}</p>
                                        <p class="mt-1 text-[11px] text-neutral-400">{{ __('customer.booking.upload_types') }}</p>
                                    </div>
                                </div>
                                @error('proof') <p class="mt-2 text-[11px] text-danger">{{ $message }}</p> @enderror
                                <x-ui.button variant="primary" type="submit" class="mt-4 w-full">{{ __('customer.booking.submit_proof') }}</x-ui.button>
                            </form>
                        </div>
                    @endif
                </x-ui.folio>

                <x-ui.panel class="text-center">
                    <h4 class="font-display text-lg text-neutral-900">{{ __('customer.booking.need_assistance') }}</h4>
                    <p class="mt-2 text-sm text-neutral-500">{{ __('customer.booking.assistance_text') }}</p>
                    <a href="mailto:concierge@indogate.com" class="mt-3 inline-block text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline">{{ __('customer.booking.contact_concierge') }}</a>
                </x-ui.panel>
            </aside>
        </div>
    </div>

    <script>
        document.getElementById('file-upload')?.addEventListener('change', function (e) {
            document.getElementById('file-name').textContent = e.target.files[0] ? e.target.files[0].name : @json(__('customer.booking.upload_hint'));
        });
    </script>
</x-customer-layout>
