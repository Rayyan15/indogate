<x-public-layout :title="$package->name . ' — Indogate'">
    <!-- Top Breadcrumbs & Back -->
    <div class="bg-[#051121] border-b border-white/5 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-white/50">
                <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}" class="hover:text-accent-red transition-colors">
                    {{ __('storefront.nav_home') }}
                </a>
                <span>/</span>
                <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="hover:text-accent-red transition-colors">
                    {{ __('storefront.nav_packages') }}
                </a>
                <span>/</span>
                <span class="text-white font-semibold truncate max-w-xs">{{ $package->name }}</span>
            </nav>
        </div>
    </div>

    <!-- Package Hero Header -->
    <div class="bg-[#030b14] text-white py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                <div class="lg:col-span-2">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-3 py-1 text-xs font-semibold bg-white/5 border border-white/10 text-white/80 rounded-full">
                            {{ $package->branch?->name ?? 'Indonesia' }}
                        </span>
                        <span class="px-3 py-1 text-xs font-semibold bg-white/5 border border-white/10 text-white/80 rounded-full flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $package->duration_days }} {{ __('storefront.days_unit') }}
                        </span>
                        @if($package->base_pax)
                            <span class="px-3 py-1 text-xs font-semibold bg-white/5 border border-white/10 text-white/80 rounded-full flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                {{ $package->base_pax }} Pax
                            </span>
                        @endif
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white leading-tight mb-4">
                        {{ $package->name }}
                    </h1>

                    <p class="text-sm sm:text-base text-white/70 leading-relaxed max-w-2xl">
                        {{ $package->description }}
                    </p>
                </div>

                <!-- Price and Inquiry CTA Card -->
                <div class="bg-[#051121] p-6 rounded-2xl border border-white/10 lg:text-end shadow-xl">
                    <span class="block text-xs font-medium text-white/50">
                        {{ __('storefront.starting_from') }}
                    </span>
                    <div class="text-2xl sm:text-3xl font-black text-white mt-1">
                        {{ \App\Support\Storefront\StorefrontCurrency::format($package->starting_price_idr) }}
                    </div>
                    <span class="text-[11px] text-white/40 block mt-0.5">
                        {{ __('storefront.price_disclaimer') }}
                    </span>

                    <div class="mt-6 flex flex-col gap-3">
                        <a
                            href="#package-inquiry"
                            class="w-full text-center px-6 py-3 text-xs font-bold text-white bg-accent-red hover:bg-[#c91840] rounded-full transition-colors shadow-lg"
                        >
                            {{ __('storefront.book_this_package') }}
                        </a>
                        <a
                            href="https://wa.me/628111111111?text={{ urlencode('Halo Indogate, saya ingin konsultasi mengenai paket: ' . $package->name) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full text-center px-6 py-3 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-full transition-colors shadow-lg"
                        >
                            {{ __('storefront.whatsapp_greeting') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area: Itinerary & Inclusions & Lead Form -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Left 2 Cols: Details, Itinerary, Inclusions -->
            <div class="lg:col-span-2 space-y-12">
                <!-- Cover Image -->
                <div class="aspect-[16/9] bg-gray-900 rounded-2xl overflow-hidden border border-white/5 shadow-2xl">
                    @if($package->cover_image)
                        <img
                            src="{{ $package->cover_image }}"
                            alt="{{ $package->name }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="w-full h-full bg-gradient-to-tr from-gray-900 to-gray-800 flex items-center justify-center p-8 text-center text-white/50 font-bold text-xl">
                            {{ $package->name }}
                        </div>
                    @endif
                </div>

                <!-- Highlights Section -->
                @if(!empty($package->highlights) && is_array($package->highlights))
                    <div class="bg-[#051121] p-6 rounded-2xl border border-white/5 shadow-lg">
                        <h2 class="text-lg font-bold text-white mb-4">{{ __('storefront.highlights_title') }}</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-white/70">
                            @foreach($package->highlights as $highlight)
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $highlight }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Day-by-Day Itinerary -->
                <div class="bg-[#051121] p-6 sm:p-8 rounded-2xl border border-white/5 shadow-lg">
                    <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-accent-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ __('storefront.itinerary_title') }}</span>
                    </h2>

                    @if($package->days->isEmpty())
                        <p class="text-xs text-white/40 italic py-4">
                            {{ __('storefront.itinerary_empty') }}
                        </p>
                    @else
                        <div class="space-y-6 relative before:absolute before:inset-0 before:start-3.5 before:w-0.5 before:bg-white/10">
                            @foreach($package->days as $day)
                                <div class="relative flex items-start gap-4">
                                    <div class="w-7 h-7 rounded-full bg-white/10 border border-white/20 text-white flex items-center justify-center text-xs font-bold flex-shrink-0 z-10">
                                        {{ $day->day_number }}
                                    </div>
                                    <div class="flex-grow bg-white/5 p-4 rounded-xl border border-white/10">
                                        <h3 class="text-sm font-bold text-white">
                                            {{ $day->title ?: __('storefront.day_label', ['day' => $day->day_number]) }}
                                        </h3>
                                        @if($day->notes)
                                            <p class="text-xs text-white/60 mt-2 leading-relaxed">
                                                {{ $day->notes }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Inclusions & Exclusions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="bg-[#051121] p-6 rounded-2xl border border-white/5 shadow-lg">
                        <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('storefront.inclusions_title') }}</span>
                        </h3>
                        <ul class="space-y-3 text-xs text-white/70">
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold">&bull;</span> {{ __('storefront.inclusion_1') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold">&bull;</span> {{ __('storefront.inclusion_2') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold">&bull;</span> {{ __('storefront.inclusion_3') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-emerald-500 font-bold">&bull;</span> {{ __('storefront.inclusion_4') }}
                            </li>
                        </ul>
                    </div>

                    <div class="bg-[#051121] p-6 rounded-2xl border border-white/5 shadow-lg">
                        <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>{{ __('storefront.exclusions_title') }}</span>
                        </h3>
                        <ul class="space-y-3 text-xs text-white/70">
                            <li class="flex items-start gap-2">
                                <span class="text-accent-red font-bold">&bull;</span> {{ __('storefront.exclusion_1') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-accent-red font-bold">&bull;</span> {{ __('storefront.exclusion_2') }}
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-accent-red font-bold">&bull;</span> {{ __('storefront.exclusion_3') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Col: Booking Inquiry Sidebar -->
            <div class="lg:col-span-1">
                <div id="package-inquiry" class="bg-[#051121] p-6 rounded-2xl border border-white/10 shadow-2xl sticky top-28">
                    <h2 class="text-lg font-extrabold text-white mb-2">
                        {{ __('storefront.book_this_package') }}
                    </h2>
                    <p class="text-xs text-white/60 mb-6">
                        {{ __('storefront.inquiry_subtitle') }}
                    </p>

                    @if(session('status'))
                        <div class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs text-center font-medium">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form action="{{ route('leads.public-store', ['locale' => app()->getLocale()]) }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Anti-bot honeypot -->
                        <div style="display: none;" aria-hidden="true">
                            <label for="website">Website</label>
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                        </div>

                        <!-- Package ID hidden field -->
                        <input type="hidden" name="package_id" value="{{ $package->id }}">

                        <div>
                            <label for="name" class="block text-xs font-semibold text-white/80 mb-1">
                                {{ __('storefront.form_name') }} <span class="text-accent-red">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                required
                                value="{{ old('name') }}"
                                class="w-full px-4 py-2.5 text-xs bg-black/40 border border-white/10 rounded-lg focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white placeholder-white/30"
                                placeholder="{{ __('storefront.form_name_placeholder') }}"
                            >
                            @error('name')
                                <p class="text-[11px] text-accent-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold text-white/80 mb-1">
                                {{ __('storefront.form_phone') }} <span class="text-accent-red">*</span>
                            </label>
                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                required
                                value="{{ old('phone') }}"
                                class="w-full px-4 py-2.5 text-xs bg-black/40 border border-white/10 rounded-lg focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white placeholder-white/30"
                                placeholder="{{ __('storefront.form_phone_placeholder') }}"
                            >
                            @error('phone')
                                <p class="text-[11px] text-accent-red mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="country" class="block text-xs font-semibold text-white/80 mb-1">
                                    {{ __('storefront.form_country') }}
                                </label>
                                <input
                                    type="text"
                                    name="country"
                                    id="country"
                                    value="{{ old('country') }}"
                                    class="w-full px-4 py-2.5 text-xs bg-black/40 border border-white/10 rounded-lg focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white placeholder-white/30"
                                    placeholder="SA / UAE"
                                >
                            </div>

                            <div>
                                <label for="pax" class="block text-xs font-semibold text-white/80 mb-1">
                                    {{ __('storefront.form_pax') }}
                                </label>
                                <input
                                    type="number"
                                    name="pax"
                                    id="pax"
                                    min="1"
                                    value="{{ old('pax', $package->base_pax ?: 2) }}"
                                    class="w-full px-4 py-2.5 text-xs bg-black/40 border border-white/10 rounded-lg focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="travel_date" class="block text-xs font-semibold text-white/80 mb-1">
                                {{ __('storefront.form_travel_date') }}
                            </label>
                            <input
                                type="date"
                                name="travel_date"
                                id="travel_date"
                                value="{{ old('travel_date') }}"
                                class="w-full px-4 py-2.5 text-xs bg-black/40 border border-white/10 rounded-lg focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white"
                                style="color-scheme: dark;"
                            >
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-semibold text-white/80 mb-1">
                                {{ __('storefront.form_notes') }}
                            </label>
                            <textarea
                                name="notes"
                                id="notes"
                                rows="3"
                                class="w-full px-4 py-2.5 text-xs bg-black/40 border border-white/10 rounded-lg focus:ring-1 focus:ring-accent-red focus:border-accent-red text-white placeholder-white/30"
                                placeholder="{{ __('storefront.form_notes_placeholder') }}"
                            >{{ old('notes') }}</textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full py-3 text-xs font-bold text-white bg-accent-red hover:bg-[#c91840] rounded-full transition-colors shadow-lg mt-2"
                        >
                            {{ __('storefront.form_submit') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
