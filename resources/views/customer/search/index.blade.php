<x-customer-layout>
    <x-slot name="header">
        <div class="text-center">
            <span class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">{{ __('customer.search.eyebrow') }}</span>
            <h2 class="mt-2 font-display text-3xl font-light tracking-tight text-neutral-900">{{ __('customer.search.title') }}</h2>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

        <!-- Tabs -->
        <div class="mb-10 flex justify-center border-b border-neutral-200">
            @foreach(['flights' => __('customer.search.tab_flights'), 'hotels' => __('customer.search.tab_hotels'), 'drivers' => __('customer.search.tab_drivers')] as $key => $label)
                <a href="{{ route('search.index', ['type' => $key]) }}"
                   class="border-b-2 px-6 py-3 text-sm font-medium transition-colors {{ $type === $key ? 'border-red-600 text-neutral-900' : 'border-transparent text-neutral-500 hover:text-neutral-800' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @if($type === 'flights' && $flights)
                @forelse($flights as $flight)
                    <x-ui.panel class="flex flex-col">
                        <div class="mb-5 flex items-start justify-between">
                            <div>
                                <h3 class="font-display text-lg text-neutral-900">{{ $flight->airline }}</h3>
                                <x-ui.eyebrow class="mt-1">{{ __('customer.search.premium_class') }}</x-ui.eyebrow>
                            </div>
                        </div>
                        <div class="mb-6 flex items-center justify-between text-sm">
                            <div class="text-center">
                                <p class="font-mono text-base font-semibold text-neutral-900">{{ $flight->origin }}</p>
                                <p class="text-[11px] text-neutral-400">{{ __('customer.search.origin') }}</p>
                            </div>
                            <div class="mx-3 h-px flex-1 bg-neutral-200"></div>
                            <div class="text-center">
                                <p class="font-mono text-base font-semibold text-neutral-900">{{ $flight->destination }}</p>
                                <p class="text-[11px] text-neutral-400">{{ __('customer.search.destination') }}</p>
                            </div>
                        </div>
                        <p class="mb-6 font-mono text-xs text-neutral-500">{{ $flight->departure_at->format('d M Y, H:i') }}</p>

                        <form action="{{ route('cart.add') }}" method="POST" class="mt-auto space-y-4 border-t border-neutral-200 pt-4">
                            @csrf
                            <input type="hidden" name="bookable_type" value="App\Models\FlightRoute">
                            <input type="hidden" name="bookable_id" value="{{ $flight->id }}">
                            <input type="hidden" name="name" value="Flight: {{ $flight->origin }} to {{ $flight->destination }}">
                            <input type="hidden" name="price" value="{{ $flight->base_price }}">
                            <input type="hidden" name="quantity" value="1">
                            <div>
                                <x-ui.eyebrow>{{ __('customer.search.total_price') }}</x-ui.eyebrow>
                                <p class="mt-1 font-mono text-xl font-semibold text-neutral-900">{{ \App\Support\Storefront\StorefrontCurrency::format((int) $flight->base_price) }}</p>
                            </div>
                            <x-ui.button variant="secondary" type="submit" class="w-full">{{ __('customer.search.select_flight') }}</x-ui.button>
                        </form>
                    </x-ui.panel>
                @empty
                    <x-ui.empty class="col-span-full" :title="__('customer.search.no_flights')" :text="__('customer.search.no_flights_text')" />
                @endforelse
            @endif

            @if($type === 'hotels' && $hotels)
                @forelse($hotels as $hotel)
                    <x-ui.panel class="flex flex-col">
                        <div class="mb-5 flex items-start justify-between">
                            <div>
                                <h3 class="font-display text-lg text-neutral-900">{{ $hotel->name }}</h3>
                                <p class="mt-1 text-xs text-neutral-500">{{ $hotel->location }}</p>
                            </div>
                            <span class="shrink-0 font-mono text-xs font-semibold text-neutral-600">{{ $hotel->star_rating }}.0 ★</span>
                        </div>

                        <form action="{{ route('cart.add') }}" method="POST" class="mt-auto space-y-4 border-t border-neutral-200 pt-4">
                            @csrf
                            <input type="hidden" name="bookable_type" value="App\Models\Hotel">
                            <input type="hidden" name="bookable_id" value="{{ $hotel->id }}">
                            <input type="hidden" name="name" value="Hotel: {{ $hotel->name }}">
                            <input type="hidden" name="price" value="{{ $hotel->base_price_per_night }}">
                            <div class="flex items-end justify-between">
                                <div>
                                    <x-ui.eyebrow>{{ __('customer.search.per_night') }}</x-ui.eyebrow>
                                    <p class="mt-1 font-mono text-xl font-semibold text-neutral-900">{{ \App\Support\Storefront\StorefrontCurrency::format((int) $hotel->base_price_per_night) }}</p>
                                </div>
                                <div class="text-end">
                                    <label class="block text-[11px] text-neutral-400">{{ __('customer.search.nights') }}</label>
                                    <input type="number" name="quantity" value="1" min="1" class="admin-input mt-1 w-20 text-center font-mono">
                                </div>
                            </div>
                            <x-ui.button variant="secondary" type="submit" class="w-full">{{ __('customer.search.reserve_suite') }}</x-ui.button>
                        </form>
                    </x-ui.panel>
                @empty
                    <x-ui.empty class="col-span-full" :title="__('customer.search.no_hotels')" :text="__('customer.search.no_hotels_text')" />
                @endforelse
            @endif

            @if($type === 'drivers' && $drivers)
                @forelse($drivers as $driver)
                    <x-ui.panel class="flex flex-col">
                        <div class="mb-6 flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded border border-neutral-200 bg-neutral-50 font-mono text-sm font-semibold text-neutral-600">{{ substr($driver->full_name, 0, 1) }}</span>
                            <div>
                                <h3 class="font-display text-lg text-neutral-900">{{ $driver->full_name }}</h3>
                                <p class="text-xs capitalize text-neutral-500">{{ __('customer.search.professional_chauffeur') }}</p>
                            </div>
                        </div>

                        <form action="{{ route('cart.add') }}" method="POST" class="mt-auto space-y-4 border-t border-neutral-200 pt-4">
                            @csrf
                            <input type="hidden" name="bookable_type" value="App\Models\Driver">
                            <input type="hidden" name="bookable_id" value="{{ $driver->id }}">
                            <input type="hidden" name="name" value="Driver: {{ $driver->full_name }}">
                            <input type="hidden" name="price" value="{{ $driver->display_price }}">
                            <div class="flex items-end justify-between">
                                <div>
                                    <x-ui.eyebrow>{{ __('customer.search.daily_rate') }}</x-ui.eyebrow>
                                    <p class="mt-1 font-mono text-xl font-semibold text-neutral-900">{{ \App\Support\Storefront\StorefrontCurrency::format((int) $driver->display_price) }}</p>
                                </div>
                                <div class="text-end">
                                    <label class="block text-[11px] text-neutral-400">{{ __('customer.search.days') }}</label>
                                    <input type="number" name="quantity" value="1" min="1" class="admin-input mt-1 w-20 text-center font-mono">
                                </div>
                            </div>
                            <x-ui.button variant="secondary" type="submit" class="w-full">{{ __('customer.search.hire_chauffeur') }}</x-ui.button>
                        </form>
                    </x-ui.panel>
                @empty
                    <x-ui.empty class="col-span-full" :title="__('customer.search.no_drivers')" :text="__('customer.search.no_drivers_text')" />
                @endforelse
            @endif
        </div>
        {{ ($flights ?? $hotels ?? $drivers)?->links() }}
    </div>
</x-customer-layout>
