<div>
    <!-- Catalog Header & Toolbar -->
    <div class="bg-[#030b14] border-b border-white/5 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-extrabold text-white tracking-tight">
                        {{ __('storefront.catalog_title') }}
                    </h1>
                    <p class="text-sm text-white/60 mt-1">
                        {{ __('storefront.catalog_subtitle') }}
                    </p>
                </div>

                <!-- Currency Quick Switcher inside Catalog (Real-time live reactive) -->
                <div class="flex items-center gap-2 self-start md:self-auto bg-black/40 p-1 rounded-full border border-white/10 text-xs font-semibold backdrop-blur-md">
                    <span class="text-white/50 px-2">{{ __('storefront.currency_label') }}:</span>
                    @foreach(\App\Support\Storefront\StorefrontCurrency::SUPPORTED_CURRENCIES as $curr)
                        <button
                            wire:key="curr-btn-{{ $curr }}"
                            type="button"
                            wire:click="setCurrency('{{ $curr }}')"
<<<<<<< HEAD
                            wire:loading.attr="disabled"
                            wire:target="setCurrency('{{ $curr }}')"
                            class="px-3 py-1.5 rounded transition-all {{ $currency === $curr ? 'bg-red-600 text-white shadow-sm font-bold' : 'text-neutral-700 hover:text-neutral-900' }} disabled:opacity-50"
=======
                            class="px-3 py-1.5 rounded-full transition-colors {{ $currency === $curr ? 'bg-accent-red text-white shadow-sm font-bold' : 'text-white/70 hover:text-white hover:bg-white/10' }}"
>>>>>>> fe6fbe57887a861055f6cfb4140d0ddb4f7a14c5
                        >
                            {{ $curr }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search Input -->
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-white/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('storefront.search_placeholder') }}"
                        class="w-full ps-9 pe-3 py-2.5 text-sm bg-black/40 border border-white/10 rounded focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white placeholder-white/40"
                    >
                </div>

                <!-- Destination / Branch Filter -->
                <div>
                    <select
                        wire:model.live="branchId"
                        class="w-full py-2.5 px-3 text-sm bg-black/40 border border-white/10 rounded focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white"
                        style="color-scheme: dark;"
                    >
                        <option value="">{{ __('storefront.filter_destination_all') ?? 'All Destinations' }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Duration Filter -->
                <div>
                    <select
                        wire:model.live="duration"
                        class="w-full py-2.5 px-3 text-sm bg-black/40 border border-white/10 rounded focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white"
                        style="color-scheme: dark;"
                    >
                        <option value="">{{ __('storefront.filter_duration_all') }}</option>
                        <option value="1-3">{{ __('storefront.duration_short') }} (1-3 {{ __('storefront.days_unit') }})</option>
                        <option value="4-7">{{ __('storefront.duration_medium') }} (4-7 {{ __('storefront.days_unit') }})</option>
                        <option value="8+">{{ __('storefront.duration_long') }} (8+ {{ __('storefront.days_unit') }})</option>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div>
                    <select
                        wire:model.live="sort"
                        class="w-full py-2.5 px-3 text-sm bg-black/40 border border-white/10 rounded focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white"
                        style="color-scheme: dark;"
                    >
                        <option value="latest">{{ __('storefront.sort_latest') }}</option>
                        <option value="price_asc">{{ __('storefront.sort_price_asc') }}</option>
                        <option value="price_desc">{{ __('storefront.sort_price_desc') }}</option>
                        <option value="duration_asc">{{ __('storefront.sort_duration_asc') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Package Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div wire:loading.flex class="justify-center items-center py-12 text-white/50 gap-2">
            <svg class="animate-spin h-5 w-5 text-accent-red" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span class="text-sm font-medium">{{ __('storefront.loading') }}</span>
        </div>

        <div wire:loading.remove>
            @if($packages->isEmpty())
                <div class="text-center py-16 bg-[#051121] rounded-2xl border border-white/5 p-8 max-w-lg mx-auto shadow-2xl">
                    <svg class="w-12 h-12 text-white/20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <h3 class="text-lg font-bold text-white">{{ __('storefront.no_packages_found') }}</h3>
                    <p class="text-xs text-white/50 mt-1 mb-4">{{ __('storefront.try_different_filter') }}</p>
                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="px-5 py-2.5 text-xs font-semibold text-white bg-white/10 hover:bg-white/20 rounded-full transition-colors"
                    >
                        {{ __('storefront.reset_filters') }}
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($packages as $package)
<<<<<<< HEAD
                        <article wire:key="package-{{ $package->id }}" class="bg-neutral-0 rounded border {{ $package->is_featured ? 'border-2 border-red-600' : 'border-neutral-200' }} overflow-hidden flex flex-col hover:shadow-lg transition-shadow duration-300">
=======
                        <article class="bg-[#051121] rounded-3xl border {{ $package->is_featured ? 'border-2 border-accent-red' : 'border-white/5' }} overflow-hidden flex flex-col hover:-translate-y-2 transition-all duration-500 hover:shadow-[0_0_30px_rgba(0,0,0,0.5)]">
>>>>>>> fe6fbe57887a861055f6cfb4140d0ddb4f7a14c5
                            <!-- Package Image -->
                            <div class="relative aspect-[16/10] bg-gray-900 overflow-hidden group">
                                @if($package->cover_image)
                                    <img
                                        src="{{ $package->cover_image }}"
                                        alt="{{ $package->name }}"
                                        loading="lazy"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                    >
                                @else
                                    <div class="w-full h-full bg-gradient-to-tr from-gray-900 to-gray-800 flex items-center justify-center p-6 text-center">
                                        <span class="text-white/80 font-bold text-lg">{{ $package->name }}</span>
                                    </div>
                                @endif

                                <!-- Top Badges -->
                                <div class="absolute top-3 inset-x-3 flex items-center justify-between pointer-events-none">
                                    <span class="px-3 py-1.5 text-xs font-semibold bg-black/40 text-white rounded-full backdrop-blur-sm border border-white/10">
                                        {{ $package->branch?->name ?? 'Indonesia' }}
                                    </span>
                                    @if($package->is_featured)
                                        <span class="px-3 py-1.5 text-xs font-bold bg-accent-red text-white rounded-full shadow-lg tracking-wider">
                                            {{ __('storefront.featured_badge') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="absolute bottom-3 start-3">
                                    <span class="px-3 py-1.5 text-xs font-medium bg-black/40 text-white rounded-full backdrop-blur-sm border border-white/10 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $package->duration_days }} {{ __('storefront.days_unit') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-6 flex-grow flex flex-col justify-between">
                                <div>
                                    <h3 class="text-xl font-bold text-white leading-snug hover:text-accent-red transition-colors">
                                        <a href="{{ route('public.package.show', ['locale' => app()->getLocale(), 'package' => $package->id]) }}">
                                            {{ $package->name }}
                                        </a>
                                    </h3>

                                    <p class="text-sm text-white/60 mt-3 line-clamp-2 leading-relaxed">
                                        {{ $package->description }}
                                    </p>

                                    <!-- Highlights / Cultural guarantees -->
                                    @if(!empty($package->highlights) && is_array($package->highlights))
                                        <ul class="mt-4 space-y-2 text-xs text-white/70 border-t border-white/5 pt-4">
                                            @foreach(array_slice($package->highlights, 0, 3) as $highlight)
                                                <li class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-accent-red flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span class="line-clamp-1">{{ $highlight }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <!-- Pricing and Action -->
                                <div class="mt-6 pt-4 border-t border-white/5 flex items-end justify-between gap-4">
                                    <div>
                                        <span class="block text-[11px] font-medium text-white/50">
                                            {{ __('storefront.starting_from') }}
                                        </span>
                                        <span class="text-lg font-extrabold text-white tracking-tight">
                                            {{ \App\Support\Storefront\StorefrontCurrency::format($package->starting_price_idr, $currency) }}
                                        </span>
                                    </div>

                                    <a
                                        href="{{ route('public.package.show', ['locale' => app()->getLocale(), 'package' => $package->id]) }}"
                                        class="inline-flex items-center gap-1.5 px-4 py-2.5 text-xs font-bold rounded-full {{ $package->is_featured ? 'bg-accent-red text-white hover:bg-[#c91840]' : 'bg-white/10 text-white hover:bg-white/20' }} transition-colors shadow-lg"
                                    >
                                        <span>{{ __('storefront.view_details') }}</span>
                                        <svg class="w-3.5 h-3.5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12 dark-pagination">
                    {{ $packages->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
