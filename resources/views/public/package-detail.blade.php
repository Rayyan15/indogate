<x-public-layout :title="$package->name . ' — Indogate'">
    <!-- Top Breadcrumbs & Back -->
    <div class="bg-neutral-100 border-b border-neutral-200 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex items-center gap-2 text-xs text-neutral-500">
                <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}" class="hover:text-red-600 transition-colors">
                    {{ __('storefront.nav_home') }}
                </a>
                <span>/</span>
                <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="hover:text-red-600 transition-colors">
                    {{ __('storefront.nav_packages') }}
                </a>
                <span>/</span>
                <span class="text-neutral-900 font-semibold truncate max-w-xs">{{ $package->name }}</span>
            </nav>
        </div>
    </div>

    <!-- Package Hero Header -->
    <div class="bg-neutral-900 text-white py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-center">
                <div class="lg:col-span-2">
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        <span class="px-2.5 py-1 text-xs font-semibold bg-neutral-800 border border-neutral-700 text-neutral-300 rounded">
                            {{ $package->branch?->name ?? 'Indonesia' }}
                        </span>
                        <span class="px-2.5 py-1 text-xs font-semibold bg-neutral-800 border border-neutral-700 text-neutral-300 rounded flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $package->duration_days }} {{ __('storefront.days_unit') }}
                        </span>
                        @if($package->base_pax)
                            <span class="px-2.5 py-1 text-xs font-semibold bg-neutral-800 border border-neutral-700 text-neutral-300 rounded flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                {{ $package->base_pax }} Pax
                            </span>
                        @endif
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-black tracking-tight text-white leading-tight mb-4">
                        {{ $package->name }}
                    </h1>

                    <p class="text-sm sm:text-base text-neutral-300 leading-relaxed max-w-2xl">
                        {{ $package->description }}
                    </p>
                </div>

                <!-- Price and Inquiry CTA Card -->
                <div class="bg-neutral-950 p-6 rounded border border-neutral-800 lg:text-end">
                    <span class="block text-xs font-medium text-neutral-400">
                        {{ __('storefront.starting_from') }}
                    </span>
                    <div class="text-2xl sm:text-3xl font-black text-white mt-1">
                        {{ \App\Support\Storefront\StorefrontCurrency::format($package->starting_price_idr) }}
                    </div>
                    <span class="text-[11px] text-neutral-500 block mt-0.5">
                        {{ __('storefront.price_disclaimer') }}
                    </span>

                    <div class="mt-6 flex flex-col gap-3">
                        <a
                            href="#package-inquiry"
                            class="w-full text-center px-6 py-3 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded transition-colors shadow-sm"
                        >
                            {{ __('storefront.book_this_package') }}
                        </a>
                        <a
                            href="https://wa.me/628111111111?text={{ urlencode('Halo Indogate, saya ingin konsultasi mengenai paket: ' . $package->name) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full text-center px-6 py-3 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded transition-colors"
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
                <div class="aspect-[16/9] bg-neutral-100 rounded overflow-hidden border border-neutral-200">
                    @if($package->cover_image)
                        <img
                            src="{{ $package->cover_image }}"
                            alt="{{ $package->name }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="w-full h-full bg-gradient-to-tr from-neutral-800 to-neutral-700 flex items-center justify-center p-8 text-center text-white font-bold text-xl">
                            {{ $package->name }}
                        </div>
                    @endif
                </div>

                <!-- Highlights Section -->
                @if(!empty($package->highlights) && is_array($package->highlights))
                    <div class="bg-neutral-0 p-6 rounded border border-neutral-200">
                        <h2 class="text-lg font-bold text-neutral-900 mb-4">{{ __('storefront.highlights_title') }}</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-neutral-700">
                            @foreach($package->highlights as $highlight)
                                <div class="flex items-start gap-2.5">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>{{ $highlight }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Day-by-Day Itinerary -->
                <div class="bg-neutral-0 p-6 sm:p-8 rounded border border-neutral-200">
                    <h2 class="text-xl font-bold text-neutral-900 mb-6 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>{{ __('storefront.itinerary_title') }}</span>
                    </h2>

                    @if($package->days->isEmpty())
                        <p class="text-xs text-neutral-500 italic py-4">
                            {{ __('storefront.itinerary_empty') }}
                        </p>
                    @else
                        <div class="space-y-6 relative before:absolute before:inset-0 before:start-3.5 before:w-0.5 before:bg-neutral-200">
                            @foreach($package->days as $day)
                                <div class="relative flex items-start gap-4">
                                    <div class="w-7 h-7 rounded-full bg-neutral-900 text-white flex items-center justify-center text-xs font-bold flex-shrink-0 z-10">
                                        {{ $day->day_number }}
                                    </div>
                                    <div class="flex-grow bg-neutral-50 p-4 rounded border border-neutral-200">
                                        <h3 class="text-sm font-bold text-neutral-900">
                                            {{ $day->title ?: __('storefront.day_label', ['day' => $day->day_number]) }}
                                        </h3>
                                        @if($day->notes)
                                            <p class="text-xs text-neutral-600 mt-2 leading-relaxed">
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
                    <div class="bg-neutral-0 p-6 rounded border border-neutral-200">
                        <h3 class="text-sm font-bold text-neutral-900 mb-4 text-emerald-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('storefront.inclusions_title') }}</span>
                        </h3>
                        <ul class="space-y-2 text-xs text-neutral-600">
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.inclusion_1') }}</li>
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.inclusion_2') }}</li>
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.inclusion_3') }}</li>
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.inclusion_4') }}</li>
                        </ul>
                    </div>

                    <div class="bg-neutral-0 p-6 rounded border border-neutral-200">
                        <h3 class="text-sm font-bold text-neutral-900 mb-4 text-red-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span>{{ __('storefront.exclusions_title') }}</span>
                        </h3>
                        <ul class="space-y-2 text-xs text-neutral-600">
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.exclusion_1') }}</li>
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.exclusion_2') }}</li>
                            <li class="flex items-center gap-2">&bull; {{ __('storefront.exclusion_3') }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Right Col: Booking Inquiry Sidebar -->
            <div class="lg:col-span-1">
                <div id="package-inquiry" class="bg-neutral-0 p-6 rounded border border-neutral-300 shadow-sm sticky top-28">
                    <h2 class="text-lg font-extrabold text-neutral-900 mb-2">
                        {{ __('storefront.book_this_package') }}
                    </h2>
                    <p class="text-xs text-neutral-500 mb-6">
                        {{ __('storefront.inquiry_subtitle') }}
                    </p>

                    @if(session('status'))
                        <div class="mb-4 p-3 rounded bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs text-center font-medium">
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
                            <label for="name" class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('storefront.form_name') }} <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                required
                                value="{{ old('name') }}"
                                class="w-full px-3 py-2 text-xs bg-neutral-50 border border-neutral-300 rounded focus:ring-1 focus:ring-red-600 focus:border-red-600 text-neutral-900"
                                placeholder="{{ __('storefront.form_name_placeholder') }}"
                            >
                            @error('name')
                                <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('storefront.form_phone') }} <span class="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                name="phone"
                                id="phone"
                                required
                                value="{{ old('phone') }}"
                                class="w-full px-3 py-2 text-xs bg-neutral-50 border border-neutral-300 rounded focus:ring-1 focus:ring-red-600 focus:border-red-600 text-neutral-900"
                                placeholder="{{ __('storefront.form_phone_placeholder') }}"
                            >
                            @error('phone')
                                <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="country" class="block text-xs font-semibold text-neutral-700 mb-1">
                                    {{ __('storefront.form_country') }}
                                </label>
                                <input
                                    type="text"
                                    name="country"
                                    id="country"
                                    value="{{ old('country') }}"
                                    class="w-full px-3 py-2 text-xs bg-neutral-50 border border-neutral-300 rounded focus:ring-1 focus:ring-red-600 focus:border-red-600 text-neutral-900"
                                    placeholder="SA / UAE"
                                >
                            </div>

                            <div>
                                <label for="pax" class="block text-xs font-semibold text-neutral-700 mb-1">
                                    {{ __('storefront.form_pax') }}
                                </label>
                                <input
                                    type="number"
                                    name="pax"
                                    id="pax"
                                    min="1"
                                    value="{{ old('pax', $package->base_pax ?: 2) }}"
                                    class="w-full px-3 py-2 text-xs bg-neutral-50 border border-neutral-300 rounded focus:ring-1 focus:ring-red-600 focus:border-red-600 text-neutral-900"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="travel_date" class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('storefront.form_travel_date') }}
                            </label>
                            <input
                                type="date"
                                name="travel_date"
                                id="travel_date"
                                value="{{ old('travel_date') }}"
                                class="w-full px-3 py-2 text-xs bg-neutral-50 border border-neutral-300 rounded focus:ring-1 focus:ring-red-600 focus:border-red-600 text-neutral-900"
                            >
                        </div>

                        <div>
                            <label for="notes" class="block text-xs font-semibold text-neutral-700 mb-1">
                                {{ __('storefront.form_notes') }}
                            </label>
                            <textarea
                                name="notes"
                                id="notes"
                                rows="3"
                                class="w-full px-3 py-2 text-xs bg-neutral-50 border border-neutral-300 rounded focus:ring-1 focus:ring-red-600 focus:border-red-600 text-neutral-900"
                                placeholder="{{ __('storefront.form_notes_placeholder') }}"
                            >{{ old('notes') }}</textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded transition-colors shadow-sm"
                        >
                            {{ __('storefront.form_submit') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-public-layout>
