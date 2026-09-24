<x-customer-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-light tracking-tight text-neutral-900">{{ __('customer.cart.title') }}</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if(empty($cart))
            <x-ui.empty :title="__('customer.cart.empty_title')" :text="__('customer.cart.empty_text')">
                <x-ui.button variant="primary" :href="route('search.index')">{{ __('customer.cart.explore') }}</x-ui.button>
            </x-ui.empty>
        @else
            @php $total = 0; @endphp
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <main class="space-y-4 lg:col-span-8">
                    <div class="divide-y divide-neutral-200 rounded border border-neutral-200 bg-neutral-0">
                        @foreach($cart as $index => $item)
                            @php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; @endphp
                            <x-ui.segment :index="$loop->iteration" :kicker="class_basename($item['bookable_type']) . ' ' . __('customer.cart.service')" :title="$item['name']" :meta="$item['quantity'] . ' × ' . \App\Support\Storefront\StorefrontCurrency::format((int) $item['price'])" :amount="\App\Support\Storefront\StorefrontCurrency::format((int) $subtotal)">
                                <x-slot name="actions">
                                    <form action="{{ route('cart.remove', $index) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <x-ui.button variant="danger" type="submit">{{ __('customer.cart.remove') }}</x-ui.button>
                                    </form>
                                </x-slot>
                            </x-ui.segment>
                        @endforeach
                    </div>
                    <x-ui.button variant="ghost" :href="route('search.index')">{{ __('customer.cart.add_more') }}</x-ui.button>
                </main>

                <aside class="lg:col-span-4">
                    <x-ui.folio :eyebrow="__('customer.cart.summary_eyebrow')" :title="__('customer.cart.summary_title')"
                        :rows="[[__('customer.cart.subtotal'), \App\Support\Storefront\StorefrontCurrency::format((int) $total)], [__('customer.cart.concierge_fee'), __('customer.cart.complimentary')], [__('customer.cart.taxes'), __('customer.cart.calculated_at_checkout')]]"
                        :total-label="__('customer.cart.estimated_total')" :total="\App\Support\Storefront\StorefrontCurrency::format((int) $total)">
                        @if(\App\Support\Storefront\StorefrontCurrency::current() !== 'IDR')
                            <p class="mb-3 text-xs text-neutral-500">{{ __('customer.currency.idr_total', ['amount' => 'IDR ' . number_format($total)]) }} {{ __('customer.currency.indicative_note') }}</p>
                        @endif
                        <x-ui.button variant="primary" :href="route('checkout.index')" class="w-full">{{ __('customer.cart.proceed_checkout') }}</x-ui.button>
                        <p class="mt-3 text-center text-[11px] text-neutral-400">{{ __('customer.cart.secure_note') }}</p>
                    </x-ui.folio>
                </aside>
            </div>
        @endif
    </div>
</x-customer-layout>
