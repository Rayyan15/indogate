<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Indogate — ' . __('storefront.brand_tagline') }}</title>
    <meta name="description" content="{{ __('storefront.hero_subtitle') }}">

    <!-- Google Fonts: Inter and Cairo for Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body {
            font-family: {{ in_array(app()->getLocale(), ['ar']) ? "'Cairo', sans-serif" : "'Inter', sans-serif" }};
        }
    </style>
</head>
<body class="bg-[#030b14] text-white antialiased min-h-screen flex flex-col selection:bg-accent-red selection:text-white" x-data="{ mobileMenuOpen: false }">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 bg-[#030b14]/90 backdrop-blur-md border-b border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Brand Logo -->
                <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}" class="flex items-center gap-2 text-decoration-none group">
                    <span class="text-2xl font-black tracking-tight text-white font-sans">
                        INDO<span class="text-red-600">GATE</span>
                    </span>
                    <span class="hidden sm:inline-block text-[11px] font-semibold uppercase tracking-wider text-white/50 border-s border-white/20 ps-2">
                        Premium Travel
                    </span>
                </a>

                <!-- Desktop Nav Links -->
                <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-white/80">
                    <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}" class="hover:text-red-600 transition-colors {{ request()->routeIs('public.home') ? 'text-red-600 font-semibold' : '' }}">
                        {{ __('storefront.nav_home') }}
                    </a>
                    <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="hover:text-red-600 transition-colors {{ request()->routeIs('public.catalog*') ? 'text-red-600 font-semibold' : '' }}">
                        {{ __('storefront.nav_packages') }}
                    </a>
                    <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}#why-us" class="hover:text-red-600 transition-colors">
                        {{ __('storefront.nav_why_us') }}
                    </a>
                    <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}#contact" class="hover:text-red-600 transition-colors">
                        {{ __('storefront.nav_contact') }}
                    </a>
                </nav>

                <!-- Header Actions (Currency, Language, WhatsApp CTA) -->
                <div class="hidden lg:flex items-center gap-4">
                    <!-- Currency Switcher Form -->
                    <form action="{{ route('public.currency.switch', ['locale' => app()->getLocale()]) }}" method="POST" class="inline-block">
                        @csrf
                        <div class="flex items-center text-xs font-semibold text-white/70 bg-black/40 rounded-full border border-white/20 p-1 backdrop-blur-md shadow-lg">
                            @foreach(\App\Support\Storefront\StorefrontCurrency::supported() as $curr)
                                <button
                                    type="submit"
                                    name="currency"
                                    value="{{ $curr }}"
                                    class="px-3 py-1.5 rounded-full transition-colors {{ \App\Support\Storefront\StorefrontCurrency::current() === $curr ? 'bg-white text-[#030b14] font-bold' : 'hover:text-white hover:bg-white/10' }}"
                                >
                                    {{ $curr }}
                                </button>
                            @endforeach
                        </div>
                    </form>

                    <!-- Language Switcher -->
                    <div class="flex items-center text-xs font-semibold text-white/70 bg-black/40 rounded-full border border-white/20 overflow-hidden backdrop-blur-md shadow-lg">
                        <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'id'])) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'id' ? 'bg-red-600 text-white font-bold' : 'hover:bg-white/10 hover:text-white' }}">ID</a>
                        <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'en'])) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'en' ? 'bg-red-600 text-white font-bold' : 'hover:bg-white/10 hover:text-white' }}">EN</a>
                        <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'ar'])) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'ar' ? 'bg-red-600 text-white font-bold' : 'hover:bg-white/10 hover:text-white' }}">عربي</a>
                    </div>

                    <!-- WhatsApp Consultation Button -->
                    <a
                        href="https://wa.me/628111111111?text={{ urlencode(__('storefront.whatsapp_greeting')) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        <span>WhatsApp</span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden items-center gap-3">
                    <button
                        type="button"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="p-2 rounded text-white hover:bg-white/10"
                        aria-label="Toggle Navigation Menu"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden border-t border-white/10 bg-[#030b14] p-4 space-y-4">
            <nav class="flex flex-col gap-3 font-medium text-white/90">
                <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}" class="py-2 hover:text-red-600">
                    {{ __('storefront.nav_home') }}
                </a>
                <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="py-2 hover:text-red-600">
                    {{ __('storefront.nav_packages') }}
                </a>
                <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}#why-us" class="py-2 hover:text-red-600">
                    {{ __('storefront.nav_why_us') }}
                </a>
                <a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}#contact" class="py-2 hover:text-red-600">
                    {{ __('storefront.nav_contact') }}
                </a>
            </nav>

            <div class="pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                <!-- Currency Form Mobile -->
                <form action="{{ route('public.currency.switch', ['locale' => app()->getLocale()]) }}" method="POST" class="flex gap-1 text-xs">
                    @csrf
                    @foreach(\App\Support\Storefront\StorefrontCurrency::supported() as $curr)
                        <button type="submit" name="currency" value="{{ $curr }}" class="px-2.5 py-1 rounded-full border {{ \App\Support\Storefront\StorefrontCurrency::current() === $curr ? 'bg-red-600 text-white font-bold border-red-600' : 'border-white/30 text-white' }}">
                            {{ $curr }}
                        </button>
                    @endforeach
                </form>

                <!-- Language Switcher Mobile -->
                <div class="flex text-xs font-semibold border border-white/30 rounded-full overflow-hidden text-white">
                    <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'id'])) }}" class="px-2 py-1 {{ app()->getLocale() === 'id' ? 'bg-red-600 font-bold' : '' }}">ID</a>
                    <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'en'])) }}" class="px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-red-600 font-bold' : '' }}">EN</a>
                    <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'ar'])) }}" class="px-2 py-1 {{ app()->getLocale() === 'ar' ? 'bg-red-600 font-bold' : '' }}">عربي</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Page Content -->
    <main class="flex-grow">
        {{ $slot }}
    </main>

    <!-- Floating Sticky WhatsApp Button -->
    <a
        href="https://wa.me/628111111111?text={{ urlencode(__('storefront.whatsapp_greeting')) }}"
        target="_blank"
        rel="noopener noreferrer"
        class="fixed bottom-6 {{ in_array(app()->getLocale(), ['ar']) ? 'left-6' : 'right-6' }} z-50 flex items-center gap-2.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-full shadow-2xl hover:scale-105 transition-all duration-200 group"
        aria-label="WhatsApp Contact"
    >
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
        <span class="text-xs font-bold tracking-wide">WhatsApp</span>
    </a>

    <!-- Footer -->
    <footer class="bg-[#030b14] text-neutral-300 pt-16 pb-12 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-neutral-800">
                <!-- Brand Info -->
                <div class="space-y-4 md:col-span-1">
                    <div class="text-2xl font-black tracking-tight text-white font-sans">
                        INDO<span class="text-red-500">GATE</span>
                    </div>
                    <p class="text-xs text-neutral-400 leading-relaxed">
                        {{ __('storefront.brand_tagline') }}. Menghadirkan kenyamanan, privasi, dan kehangatan keramahan nusantara untuk Anda dan keluarga.
                    </p>
                </div>

                <!-- Navigation -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-4">{{ __('storefront.nav_packages') }}</h4>
                    <ul class="space-y-2.5 text-xs text-neutral-400">
                        <li><a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="hover:text-white transition-colors">{{ __('storefront.catalog_title') }}</a></li>
                        <li><a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}#why-us" class="hover:text-white transition-colors">{{ __('storefront.nav_why_us') }}</a></li>
                        <li><a href="{{ route('public.home', ['locale' => app()->getLocale()]) }}#contact" class="hover:text-white transition-colors">{{ __('storefront.nav_contact') }}</a></li>
                    </ul>
                </div>

                <!-- Branch Offices -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-4">Kantor Operasional</h4>
                    <ul class="space-y-3 text-xs text-neutral-400">
                        <li>
                            <strong class="text-white block">Bali Headquarters</strong>
                            Sunset Road, Seminyak, Kuta, Bali
                        </li>
                        <li>
                            <strong class="text-white block">Jakarta Representative</strong>
                            SCBD Sudirman, Jakarta Selatan
                        </li>
                    </ul>
                </div>

                <!-- Security & Certifications -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-white mb-4">Jaminan Layanan</h4>
                    <p class="text-xs text-neutral-400 leading-relaxed">
                        Biro perjalanan wisata resmi berizin Kementerian Pariwisata RI. Menjamin kepatuhan UU Perlindungan Data Pribadi (UU PDP No. 27/2022).
                    </p>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-neutral-500 gap-4">
                <div>
                    &copy; {{ date('Y') }} PT Indogate Travel Indonesia. {{ __('storefront.copyright') }}
                </div>
                <div class="flex items-center gap-6">
                    @if(auth()->user()?->isStaff())
                    <a href="{{ route('admin.dashboard', ['locale' => app()->getLocale()]) }}" class="hover:text-neutral-400">
                        {{ __('storefront.nav_admin') }}
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
