<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-light tracking-tight text-neutral-900">{{ __('customer.checkout.title') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <main class="space-y-6 lg:col-span-8">
                <x-ui.panel :eyebrow="__('customer.checkout.step1')" :title="__('customer.checkout.guest_details')">
                    <div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div class="rounded border border-neutral-200 bg-neutral-50 p-4">
                            <x-ui.eyebrow>{{ __('customer.checkout.primary_guest') }}</x-ui.eyebrow>
                            <p class="mt-1 text-sm font-medium text-neutral-900">{{ Auth::user()->name }}</p>
                        </div>
                        <div class="rounded border border-neutral-200 bg-neutral-50 p-4">
                            <x-ui.eyebrow>{{ __('customer.checkout.contact_email') }}</x-ui.eyebrow>
                            <p class="mt-1 text-sm font-medium text-neutral-900">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-neutral-500">{!! str_replace(':link', '<a href="'.route('profile.edit').'" class="text-blue-600 underline-offset-2 hover:text-blue-700 hover:underline">'.__('customer.checkout.edit_profile_link').'</a>', e(__('customer.checkout.edit_profile_note'))) !!}</p>
                </x-ui.panel>

                <x-ui.panel :eyebrow="__('customer.checkout.step2')" :title="__('customer.checkout.secure_payment')">
                    <x-ui.note class="mb-6">
                        <span class="mb-1 block font-semibold text-neutral-900">{{ __('customer.checkout.bank_transfer') }}</span>
                        {{ __('customer.checkout.bank_transfer_note') }}
                    </x-ui.note>

                    <form action="{{ route('checkout.store') }}" method="POST">
                        @csrf
                        <x-ui.button variant="primary" type="submit" class="w-full py-3.5 text-sm">{{ __('customer.checkout.confirm_reserve') }}</x-ui.button>
                        <p class="mt-4 text-center text-[11px] text-neutral-400">{{ __('customer.checkout.terms_note') }}</p>
                    </form>
                </x-ui.panel>
            </main>

            <aside class="lg:col-span-4">
                <x-ui.folio :eyebrow="__('customer.checkout.order_summary')" :title="__('customer.checkout.your_itinerary')"
                    :rows="collect($cart)->map(fn($item) => [$item['name'] . ' (×' . $item['quantity'] . ')', \App\Support\Storefront\StorefrontCurrency::format((int) ($item['price'] * $item['quantity']))])->all()"
                    :total-label="__('customer.checkout.total_amount')" :total="\App\Support\Storefront\StorefrontCurrency::format((int) $totalAmount)">
                    @if(\App\Support\Storefront\StorefrontCurrency::current() !== 'IDR')
                        <p class="text-xs text-neutral-500">{{ __('customer.currency.idr_total', ['amount' => 'IDR ' . number_format($totalAmount)]) }} {{ __('customer.currency.indicative_note') }}</p>
                    @endif
                </x-ui.folio>
            </aside>
        </div>
    </div>
</x-customer-layout>
