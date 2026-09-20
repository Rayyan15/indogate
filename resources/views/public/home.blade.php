<x-public-layout :title="__('storefront.brand_tagline') . ' — Indogate'">
    <!-- Hero Section -->
    <section class="relative bg-neutral-900 text-white overflow-hidden py-24 lg:py-36">
        <!-- Editorial Background Image with respectful Indonesian scenic photography -->
        <div class="absolute inset-0 z-0">
            <img
                src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?q=80&w=2000&auto=format&fit=crop"
                alt="Scenic Indonesia"
                class="w-full h-full object-cover opacity-35"
                loading="eager"
            >
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950 via-neutral-900/60 to-neutral-950/80"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center sm:text-start">
            <div class="max-w-3xl">
                <!-- Cultural Assurance Pill -->
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded bg-neutral-800/90 border border-neutral-700 text-xs font-semibold text-neutral-300 mb-6 backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>{{ __('storefront.trust_halal_dining') }} &bull; {{ __('storefront.trust_family_privacy') }}</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-white leading-tight">
                    {{ __('storefront.hero_title') }}
                </h1>

                <p class="mt-6 text-base sm:text-lg text-neutral-300 leading-relaxed max-w-2xl">
                    {{ __('storefront.hero_subtitle') }}
                </p>

                <!-- CTAs: Exactly 1 primary red button -->
                <div class="mt-10 flex flex-wrap items-center gap-4 justify-center sm:justify-start">
                    <a
                        href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}"
                        class="px-6 py-3.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded transition-all duration-200 shadow-lg shadow-red-600/20"
                    >
                        {{ __('storefront.hero_cta_packages') }}
                    </a>
                    <a
                        href="#quick-inquiry"
                        class="px-6 py-3.5 text-sm font-semibold text-neutral-200 hover:text-white bg-neutral-800/80 hover:bg-neutral-800 rounded border border-neutral-700 transition-colors"
                    >
                        {{ __('storefront.hero_cta_inquiry') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Cultural Sensitivity & Trust Pillars -->
    <section id="why-us" class="py-20 bg-neutral-0 border-b border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight">
                    {{ __('storefront.why_choose_us') }}
                </h2>
                <p class="mt-2 text-sm text-neutral-500">
                    {{ __('storefront.why_choose_us_sub') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Pillar 1: Halal Dining -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 transition-colors">
                    <div class="w-12 h-12 rounded bg-neutral-100 flex items-center justify-center text-neutral-800 mb-4 border border-neutral-200">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.trust_halal_dining') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.trust_halal_dining_desc') }}</p>
                </div>

                <!-- Pillar 2: Family Privacy -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 transition-colors">
                    <div class="w-12 h-12 rounded bg-neutral-100 flex items-center justify-center text-neutral-800 mb-4 border border-neutral-200">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.trust_family_privacy') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.trust_family_privacy_desc') }}</p>
                </div>

                <!-- Pillar 3: Female Driver Guarantee -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 transition-colors">
                    <div class="w-12 h-12 rounded bg-neutral-100 flex items-center justify-center text-neutral-800 mb-4 border border-neutral-200">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.trust_female_driver') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.trust_female_driver_desc') }}</p>
                </div>

                <!-- Pillar 4: Multilingual Support -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 transition-colors">
                    <div class="w-12 h-12 rounded bg-neutral-100 flex items-center justify-center text-neutral-800 mb-4 border border-neutral-200">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.trust_arabic_guide') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.trust_arabic_guide_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Packages Section -->
    <section class="py-20 bg-neutral-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-12 gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight">
                        {{ __('storefront.featured_title') }}
                    </h2>
                    <p class="text-sm text-neutral-500 mt-1">
                        {{ __('storefront.featured_subtitle') }}
                    </p>
                </div>

                <a
                    href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}"
                    class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-800 hover:text-red-600 transition-colors"
                >
                    <span>{{ __('storefront.view_all_packages') }}</span>
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($featuredPackages as $index => $package)
                    <!-- PRD Design Guideline: Primary package card has border-2 border-red-600, supporting cards have border-neutral-200 -->
                    <article class="bg-neutral-0 rounded {{ $index === 0 ? 'border-2 border-red-600 shadow-md' : 'border border-neutral-200' }} overflow-hidden flex flex-col hover:shadow-lg transition-shadow">
                        <div class="relative aspect-[16/10] bg-neutral-100 overflow-hidden">
                            @if($package->cover_image)
                                <img
                                    src="{{ $package->cover_image }}"
                                    alt="{{ $package->name }}"
                                    loading="lazy"
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full bg-gradient-to-tr from-neutral-800 to-neutral-700 flex items-center justify-center p-6 text-center">
                                    <span class="text-white font-bold text-base opacity-90">{{ $package->name }}</span>
                                </div>
                            @endif

                            <div class="absolute top-3 inset-x-3 flex items-center justify-between pointer-events-none">
                                <span class="px-2.5 py-1 text-xs font-semibold bg-neutral-900/80 text-white rounded backdrop-blur-sm">
                                    {{ $package->branch?->name ?? 'Indonesia' }}
                                </span>
                                @if($index === 0 || $package->is_featured)
                                    <span class="px-2.5 py-1 text-xs font-bold bg-red-600 text-white rounded shadow-sm uppercase tracking-wider">
                                        {{ __('storefront.featured_badge') }}
                                    </span>
                                @endif
                            </div>

                            <div class="absolute bottom-3 start-3">
                                <span class="px-2.5 py-1 text-xs font-medium bg-neutral-900/80 text-white rounded backdrop-blur-sm flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $package->duration_days }} {{ __('storefront.days_unit') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6 flex-grow flex flex-col justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-neutral-900 leading-snug">
                                    <a href="{{ route('public.package.show', ['locale' => app()->getLocale(), 'package' => $package->id]) }}" class="hover:text-red-600 transition-colors">
                                        {{ $package->name }}
                                    </a>
                                </h3>
                                <p class="text-xs text-neutral-500 mt-2 line-clamp-2 leading-relaxed">
                                    {{ $package->description }}
                                </p>
                            </div>

                            <div class="mt-6 pt-4 border-t border-neutral-100 flex items-end justify-between">
                                <div>
                                    <span class="block text-[11px] font-medium text-neutral-500">{{ __('storefront.starting_from') }}</span>
                                    <span class="text-lg font-extrabold text-neutral-900">
                                        {{ \App\Support\Storefront\StorefrontCurrency::format($package->starting_price_idr) }}
                                    </span>
                                </div>
                                <a
                                    href="{{ route('public.package.show', ['locale' => app()->getLocale(), 'package' => $package->id]) }}"
                                    class="text-xs font-semibold px-3 py-1.5 rounded bg-neutral-100 hover:bg-neutral-200 text-neutral-800 transition-colors"
                                >
                                    {{ __('storefront.view_details') }}
                                </a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="col-span-3 text-center py-12 text-neutral-500 text-sm">
                        {{ __('storefront.no_packages_found') }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-20 bg-neutral-0 border-t border-neutral-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-neutral-900 tracking-tight">
                    {{ __('storefront.how_it_works') }}
                </h2>
                <p class="mt-2 text-sm text-neutral-500">
                    {{ __('storefront.how_it_works_sub') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Step 1 -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50 text-center sm:text-start">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-neutral-900 text-white font-bold text-sm mb-4">1</span>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.step_1_title') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.step_1_desc') }}</p>
                </div>

                <!-- Step 2 -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50 text-center sm:text-start">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-neutral-900 text-white font-bold text-sm mb-4">2</span>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.step_2_title') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.step_2_desc') }}</p>
                </div>

                <!-- Step 3 -->
                <div class="p-6 rounded border border-neutral-200 bg-neutral-50 text-center sm:text-start">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-neutral-900 text-white font-bold text-sm mb-4">3</span>
                    <h3 class="text-base font-bold text-neutral-900 mb-2">{{ __('storefront.step_3_title') }}</h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">{{ __('storefront.step_3_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Inquiry / Lead Request Section -->
    <section id="quick-inquiry" class="py-20 bg-neutral-900 text-white border-t border-neutral-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-white tracking-tight">
                    {{ __('storefront.inquiry_title') }}
                </h2>
                <p class="text-sm text-neutral-400 mt-2 max-w-xl mx-auto">
                    {{ __('storefront.inquiry_subtitle') }}
                </p>
            </div>

            @if(session('status'))
                <div class="mb-8 p-4 rounded bg-emerald-900/40 border border-emerald-600/60 text-emerald-200 text-sm text-center">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-neutral-950 p-8 sm:p-10 rounded border border-neutral-800 shadow-2xl">
                <form action="{{ route('leads.public-store', ['locale' => app()->getLocale()]) }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Anti-bot honeypot field (must remain empty) -->
                    <div style="display: none;" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_name') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-neutral-900 border border-neutral-700 rounded text-white focus:ring-1 focus:ring-red-600 focus:border-red-600"
                                placeholder="{{ __('storefront.form_name_placeholder') }}"
                            >
                            @error('name')
                                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone / WhatsApp -->
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_phone') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                value="{{ old('phone') }}"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-neutral-900 border border-neutral-700 rounded text-white focus:ring-1 focus:ring-red-600 focus:border-red-600"
                                placeholder="{{ __('storefront.form_phone_placeholder') }}"
                            >
                            @error('phone')
                                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Country of Origin -->
                        <div>
                            <label for="country" class="block text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_country') }}
                            </label>
                            <input
                                type="text"
                                name="country"
                                id="country"
                                value="{{ old('country') }}"
                                class="w-full px-3.5 py-2.5 text-sm bg-neutral-900 border border-neutral-700 rounded text-white focus:ring-1 focus:ring-red-600 focus:border-red-600"
                                placeholder="{{ __('storefront.form_country_placeholder') }}"
                            >
                        </div>

                        <!-- Number of Pax -->
                        <div>
                            <label for="pax" class="block text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_pax') }}
                            </label>
                            <input
                                type="number"
                                name="pax"
                                id="pax"
                                min="1"
                                max="100"
                                value="{{ old('pax', 2) }}"
                                class="w-full px-3.5 py-2.5 text-sm bg-neutral-900 border border-neutral-700 rounded text-white focus:ring-1 focus:ring-red-600 focus:border-red-600"
                            >
                        </div>

                        <!-- Travel Date -->
                        <div class="sm:col-span-2">
                            <label for="travel_date" class="block text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_travel_date') }}
                            </label>
                            <input
                                type="date"
                                name="travel_date"
                                id="travel_date"
                                value="{{ old('travel_date') }}"
                                class="w-full px-3.5 py-2.5 text-sm bg-neutral-900 border border-neutral-700 rounded text-white focus:ring-1 focus:ring-red-600 focus:border-red-600"
                            >
                        </div>

                        <!-- Special Notes -->
                        <div class="sm:col-span-2">
                            <label for="notes" class="block text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_notes') }}
                            </label>
                            <textarea
                                name="notes"
                                id="notes"
                                rows="3"
                                class="w-full px-3.5 py-2.5 text-sm bg-neutral-900 border border-neutral-700 rounded text-white focus:ring-1 focus:ring-red-600 focus:border-red-600"
                                placeholder="{{ __('storefront.form_notes_placeholder') }}"
                            >{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="text-center pt-2">
                        <button
                            type="submit"
                            class="w-full sm:w-auto px-8 py-3.5 text-sm font-bold text-white bg-red-600 hover:bg-red-700 rounded transition-all duration-200 shadow-lg shadow-red-600/30"
                        >
                            {{ __('storefront.form_submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</x-public-layout>
