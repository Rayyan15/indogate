<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('storefront.brand_tagline') ?? 'Unlock your premium experiences' }} — Indogate</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Inter:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    
    <!-- Alpine.js & Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { 
            font-family: {{ in_array(app()->getLocale(), ['ar']) ? "'Cairo', sans-serif" : "'Inter', sans-serif" }};
            background-color: #030b14; 
            color: #ffffff; 
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .text-shadow-sm { text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
        .text-shadow-lg { text-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        
        /* Subtle zoom animation for the background */
        @keyframes bgZoom {
            0% { transform: scale(1); }
            100% { transform: scale(1.05); }
        }
        .animate-bg-zoom { animation: bgZoom 20s ease-in-out infinite alternate; }
        
        /* Red accent custom color */
        .accent-red { color: #e11d48; }
        .border-accent-red { border-color: #e11d48; }
        .bg-accent-red { background-color: #e11d48; }
        
        /* Hide scrollbar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="antialiased min-h-screen selection:bg-accent-red selection:text-white relative text-[#030b14]" x-data="{ menuOpen: false }">

    <!-- Background Image with Motion -->
    <div class="fixed inset-0 z-0 bg-[#030b14] pointer-events-none overflow-hidden">
        <img src="https://images.unsplash.com/photo-1552733407-5d5c46c3bb3b?q=80&w=2560&auto=format&fit=crop" 
             alt="Premium Villa in Indonesia" 
             class="w-full h-full object-cover animate-bg-zoom opacity-80" />
        <div class="absolute inset-0 bg-gradient-to-b from-[#030b14]/80 via-[#030b14]/50 to-[#030b14] mix-blend-multiply"></div>
        <div class="absolute inset-0 bg-[#06101e]/40"></div>
    </div>

    <!-- Mobile Menu Drawer -->
    <div x-show="menuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="fixed inset-y-0 right-0 z-[90] w-full max-w-sm bg-[#030b14] border-l border-white/10 p-6 flex flex-col" style="display: none;">
        <div class="flex justify-between items-center mb-12">
            <span class="text-xl font-bold text-white">Menu</span>
            <button @click="menuOpen = false" class="w-10 h-10 flex items-center justify-center rounded-full glass-panel text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <nav class="flex flex-col gap-6">
            <a href="#profile" @click="menuOpen = false" class="text-xl font-medium text-white/80 hover:text-white">{{ __('storefront.nav_why_us') ?? 'Our Profile' }}</a>
            <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" @click="menuOpen = false" class="text-xl font-medium text-white/80 hover:text-white">{{ __('storefront.nav_packages') ?? 'Explore Packages' }}</a>
            <a href="#quick-inquiry" @click="menuOpen = false" class="text-xl font-medium text-white/80 hover:text-white">{{ __('storefront.nav_contact') ?? 'Contact' }}</a>
        </nav>
        
        <div class="pt-8 mt-auto flex flex-col gap-4">
            <!-- Currency Switcher Mobile -->
            <form action="{{ route('public.currency.switch', ['locale' => app()->getLocale()]) }}" method="POST" class="flex gap-2 text-sm text-white">
                @csrf
                @foreach(\App\Support\Storefront\StorefrontCurrency::SUPPORTED_CURRENCIES as $curr)
                    <button type="submit" name="currency" value="{{ $curr }}" class="px-3 py-1.5 rounded-full border {{ \App\Support\Storefront\StorefrontCurrency::current() === $curr ? 'bg-red-600 border-red-600 font-bold' : 'border-white/30' }}">
                        {{ $curr }}
                    </button>
                @endforeach
            </form>
            <!-- Language Switcher Mobile -->
            <div class="flex text-sm text-white border border-white/30 rounded-full overflow-hidden w-fit">
                <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'id'])) }}" class="px-3 py-1.5 {{ app()->getLocale() === 'id' ? 'bg-red-600 font-bold' : 'hover:bg-white/10' }}">ID</a>
                <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'en'])) }}" class="px-3 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-red-600 font-bold' : 'hover:bg-white/10' }}">EN</a>
                <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'ar'])) }}" class="px-3 py-1.5 {{ app()->getLocale() === 'ar' ? 'bg-red-600 font-bold' : 'hover:bg-white/10' }}">عربي</a>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="relative h-screen w-full flex flex-col z-10">
        <div class="relative z-10 flex flex-col h-full">
            <!-- Header -->
            <header class="w-full px-6 py-8 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-2xl font-black tracking-tight text-white font-sans">
                        INDO<span class="text-accent-red">GATE</span>
                    </span>
                    <span class="hidden sm:inline-block text-[11px] font-semibold uppercase tracking-wider text-white/50 border-s border-white/30 ps-2">
                        Premium Travel
                    </span>
                </div>
                
                <nav class="hidden lg:flex items-center gap-10">
                    <a href="#profile" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors">{{ __('storefront.nav_why_us') ?? 'Our Profile' }}</a>
                    <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors">{{ __('storefront.nav_packages') ?? 'Packages & Tours' }}</a>
                    <a href="#private-service" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors">Private service</a>
                </nav>

                <div class="flex items-center gap-4">
                    <button @click="menuOpen = true" class="w-11 h-11 rounded-full glass-panel flex flex-col justify-center items-center gap-1.5 hover:bg-white/10 transition-colors cursor-pointer lg:hidden">
                        <span class="w-4 h-[1.5px] bg-white rounded-full"></span>
                        <span class="w-4 h-[1.5px] bg-white rounded-full"></span>
                        <span class="w-4 h-[1.5px] bg-white rounded-full"></span>
                    </button>
                    
                    <div class="hidden lg:flex items-center gap-3 mr-4 relative z-50">
                        <!-- Currency Switcher -->
                        <form action="{{ route('public.currency.switch', ['locale' => app()->getLocale()]) }}" method="POST" class="relative z-50">
                            @csrf
                            <div class="flex items-center text-xs font-semibold text-white/70 bg-black/40 rounded-full border border-white/20 p-1 backdrop-blur-md shadow-lg">
                                @foreach(\App\Support\Storefront\StorefrontCurrency::SUPPORTED_CURRENCIES as $curr)
                                    <button type="submit" name="currency" value="{{ $curr }}" class="px-3 py-1.5 rounded-full transition-colors {{ \App\Support\Storefront\StorefrontCurrency::current() === $curr ? 'bg-white text-[#030b14] font-bold' : 'hover:text-white hover:bg-white/10' }}">
                                        {{ $curr }}
                                    </button>
                                @endforeach
                            </div>
                        </form>
                        
                        <!-- Language Switcher -->
                        <div class="flex items-center text-xs font-semibold text-white/70 bg-black/40 rounded-full border border-white/20 overflow-hidden backdrop-blur-md shadow-lg relative z-50">
                            <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'id'])) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'id' ? 'bg-accent-red text-white font-bold' : 'hover:bg-white/10 hover:text-white' }}">ID</a>
                            <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'en'])) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'en' ? 'bg-accent-red text-white font-bold' : 'hover:bg-white/10 hover:text-white' }}">EN</a>
                            <a href="{{ route(Route::currentRouteName() ?? 'public.home', array_merge((array) request()->route()->parameters(), ['locale' => 'ar'])) }}" class="px-3 py-1.5 transition-colors {{ app()->getLocale() === 'ar' ? 'bg-accent-red text-white font-bold' : 'hover:bg-white/10 hover:text-white' }}">AR</a>
                        </div>
                    </div>
                    
                    @guest
                    <a href="{{ route('login') }}" class="bg-white text-[#030b14] px-6 py-3 rounded-full text-[15px] font-semibold hover:bg-gray-100 transition-colors shadow-lg hidden sm:inline-block">
                        Sign In
                    </a>
                    @else
                    <a href="{{ auth()->user()->hasAnyRole(['Super Admin', 'Finance Admin', 'CS Admin']) ? route('admin.dashboard') : route('dashboard') }}" class="bg-white text-[#030b14] px-6 py-3 rounded-full text-[15px] font-semibold hover:bg-gray-100 transition-colors shadow-lg">
                        Dashboard
                    </a>
                    @endguest
                </div>
            </header>

            <!-- Hero Main Content -->
            <div class="flex-grow flex flex-col items-center justify-center px-6 mt-[-8vh] pointer-events-none" x-data="{ showTitle: false }" x-init="setTimeout(() => showTitle = true, 300)">
                <div :class="showTitle ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'" class="transition-all duration-1000 ease-out text-center pointer-events-auto">
                    <h1 class="text-[3.25rem] md:text-7xl lg:text-[7.5rem] leading-[1] font-bold text-center tracking-tight text-white text-shadow-lg max-w-6xl mx-auto">
                        Unlock your <br />
                        <span class="italic font-medium text-white/90">premium</span> experiences
                    </h1>
                </div>
            </div>

            <!-- Hero Bottom Overview -->
            <div class="w-full px-8 pb-10 flex flex-col lg:flex-row justify-between items-end gap-8">
                <div class="w-full lg:w-1/3">
                    <p class="text-xl md:text-2xl text-white/95 font-medium max-w-[340px] leading-[1.3] text-shadow-sm mb-10 lg:mb-14">
                        {{ __('storefront.hero_subtitle') ?? 'Unforgettable impressions, unusual locations, unique routes tailored to your wishes.' }}
                    </p>
                    <div class="flex flex-wrap items-center gap-8 md:gap-12 border-b border-white/20 pb-[17px]">
                        <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="flex items-end gap-2 border-b-[3px] border-accent-red pb-4 -mb-[19px]">
                            <span class="text-white text-[15px] font-medium">{{ __('storefront.hero_cta_packages') ?? 'Explore tours' }}</span>
                        </a>
                        <a href="#quick-inquiry" class="flex items-end gap-2 pb-4 -mb-[19px] hover:text-white text-white/60 transition-colors">
                            <span class="text-[15px] font-medium">{{ __('storefront.hero_cta_inquiry') ?? 'Inquiry' }}</span>
                        </a>
                    </div>
                </div>

                <div class="hidden lg:flex w-full lg:w-2/3 justify-end pb-4">
                    <p class="text-white/60 text-sm max-w-xs text-right">
                        Scroll down to discover our exclusive catalog and learn more about what makes us the premier choice for Arab travelers in Indonesia.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Profile Section -->
    <section id="profile" class="w-full bg-[#030b14] py-24 md:py-32 relative z-10" x-data="{ shown: false }" x-intersect.once.margin.-150px="shown = true">
        <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'" class="transition-all duration-1000 ease-out max-w-7xl mx-auto px-6 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="text-accent-red text-sm font-bold tracking-widest uppercase mb-4">{{ __('storefront.why_choose_us') ?? 'About Indogate' }}</h2>
                <h3 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight tracking-tight">Curating Excellence for Discerning Travelers</h3>
                <p class="text-white/70 text-lg leading-relaxed mb-8">
                    {{ __('storefront.why_choose_us_sub') ?? 'We bridge the gap for travelers seeking luxury, cultural alignment, and seamless experiences across Indonesia\'s most breathtaking destinations.' }}
                </p>
                <ul class="space-y-4 mb-10">
                    <li class="flex items-center gap-4 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-accent-red">✓</div>
                        <span>{{ __('storefront.trust_halal_dining') ?? 'Halal-certified premium accommodations' }}</span>
                    </li>
                    <li class="flex items-center gap-4 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-accent-red">✓</div>
                        <span>{{ __('storefront.trust_arabic_guide') ?? 'Professional Arabic-speaking private drivers' }}</span>
                    </li>
                    <li class="flex items-center gap-4 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-accent-red">✓</div>
                        <span>{{ __('storefront.trust_female_driver') ?? 'Female drivers available upon request' }}</span>
                    </li>
                </ul>
            </div>
            <div class="relative">
                <div class="absolute -inset-4 bg-accent-red/20 blur-2xl rounded-full"></div>
                <img src="https://images.unsplash.com/photo-1577717903315-1691ae25ab3f?q=80&w=1200&auto=format&fit=crop" alt="Luxury Travel" class="relative z-10 w-full h-[500px] object-cover rounded-3xl shadow-2xl border border-white/10">
                
                <div class="absolute -bottom-8 -left-8 z-20 glass-panel p-6 rounded-2xl max-w-[240px]">
                    <div class="text-4xl font-bold text-white mb-1">10+</div>
                    <div class="text-white/70 text-sm">Years of delivering perfection in luxury travel.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Catalog Section (Dynamic Integration) -->
    <section id="catalog" class="w-full bg-[#051121] py-24 md:py-32 relative z-10 border-t border-white/5" x-data="{ shown: false }" x-intersect.once.margin.-150px="shown = true">
        <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'" class="transition-all duration-1000 ease-out max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <h2 class="text-accent-red text-sm font-bold tracking-widest uppercase mb-4">{{ __('storefront.featured_subtitle') ?? 'Our Catalog' }}</h2>
                    <h3 class="text-4xl md:text-5xl font-bold text-white tracking-tight">{{ __('storefront.featured_title') ?? 'Signature Experiences' }}</h3>
                </div>
                <a href="{{ route('public.catalog', ['locale' => app()->getLocale()]) }}" class="group flex items-center gap-2 text-white/80 hover:text-white font-medium transition-colors">
                    {{ __('storefront.view_all_packages') ?? 'View all packages' }}
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($featuredPackages as $index => $package)
                <a href="{{ route('public.package.show', ['locale' => app()->getLocale(), 'package' => $package->id]) }}" class="group relative w-full h-[450px] rounded-[2rem] overflow-hidden bg-white p-2 flex flex-col block transition-transform hover:-translate-y-2 duration-500">
                    <div class="w-full h-[65%] rounded-t-[1.5rem] rounded-b-[1rem] overflow-hidden relative bg-gray-900">
                        @if($package->cover_image)
                            <img src="{{ $package->cover_image }}" alt="{{ $package->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        @endif
                        <div class="absolute top-3 right-3 flex gap-2">
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">{{ $package->duration_days }} {{ __('storefront.days_unit') ?? 'Days' }}</span>
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">{{ \App\Support\Storefront\StorefrontCurrency::format($package->starting_price_idr) }}</span>
                        </div>
                    </div>
                    <div class="p-6 h-[35%] flex flex-col justify-between">
                        <h3 class="text-[#030b14] font-bold text-2xl leading-tight truncate">{{ $package->name }}</h3>
                        <div class="flex justify-between items-end mt-4">
                            <span class="text-[#030b14]/60 font-medium truncate">{{ $package->branch?->name ?? 'Indonesia' }}</span>
                            <div class="w-10 h-10 shrink-0 rounded-full border border-gray-200 flex items-center justify-center group-hover:bg-[#030b14] group-hover:text-white transition-colors">
                                <svg class="w-4 h-4 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </div>
                    </div>
                </a>
                @empty
                    <div class="col-span-3 text-center py-12 text-white/50 text-sm">
                        {{ __('storefront.no_packages_found') ?? 'No packages found.' }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Private Service Section -->
    <section id="private-service" class="w-full py-24 md:py-32 relative z-10 border-t border-white/5 overflow-hidden" x-data="{ shown: false }" x-intersect.once.margin.-150px="shown = true">
        <!-- Background Elements -->
        <div class="absolute inset-0 z-0">
            <img src="https://images.unsplash.com/photo-1540946485063-a40da27545f8?q=80&w=2000&auto=format&fit=crop" class="w-full h-full object-cover opacity-10" alt="Private Yacht">
            <div class="absolute inset-0 bg-gradient-to-b from-[#030b14] via-[#030b14]/80 to-[#030b14]"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-[#030b14] via-transparent to-[#030b14]"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-6 relative z-20">
            <div class="flex flex-col lg:flex-row items-center gap-16">
                <!-- Text Content -->
                <div :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-12'" class="transition-all duration-1000 ease-out lg:w-1/2">
                    <h2 class="text-accent-red text-sm font-bold tracking-widest uppercase mb-4 flex items-center gap-4">
                        <span class="w-8 h-[2px] bg-accent-red inline-block"></span>
                        Exclusively Yours
                    </h2>
                    <h3 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 tracking-tight leading-[1.1]">
                        Private Service & <br/>Tailored Itineraries
                    </h3>
                    <p class="text-white/60 text-lg leading-relaxed mb-10 max-w-xl">
                        Beyond our signature packages, we offer fully bespoke travel planning. We architect itineraries limited only by your imagination, ensuring total privacy and flawless execution throughout your stay.
                    </p>
                    
                    <a href="#quick-inquiry" class="inline-flex items-center gap-3 px-8 py-4 rounded-full bg-white text-[#030b14] font-bold hover:bg-gray-100 transition-colors shadow-[0_0_20px_rgba(255,255,255,0.15)]">
                        Request Private Consultation
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>

                <!-- Feature Grid -->
                <div :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-12'" class="transition-all duration-1000 delay-300 ease-out lg:w-1/2 grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                    <!-- Feature 1 -->
                    <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/5 hover:-translate-y-2 transition-transform duration-300 group bg-white/[0.02]">
                        <div class="w-12 h-12 rounded-full bg-accent-red/10 flex items-center justify-center text-accent-red mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">Private Yachts & Aviation</h4>
                        <p class="text-sm text-white/50 leading-relaxed">Exclusive charters for seamless island-hopping and absolute privacy.</p>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/5 hover:-translate-y-2 transition-transform duration-300 group bg-white/[0.02] sm:translate-y-8">
                        <div class="w-12 h-12 rounded-full bg-accent-red/10 flex items-center justify-center text-accent-red mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">Elite Villa Sourcing</h4>
                        <p class="text-sm text-white/50 leading-relaxed">Off-market private estates selected for uncompromised seclusion.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/5 hover:-translate-y-2 transition-transform duration-300 group bg-white/[0.02]">
                        <div class="w-12 h-12 rounded-full bg-accent-red/10 flex items-center justify-center text-accent-red mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">VIP Security Details</h4>
                        <p class="text-sm text-white/50 leading-relaxed">Discreet, professional close protection and specialized escorts.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="glass-panel p-6 sm:p-8 rounded-3xl border border-white/5 hover:-translate-y-2 transition-transform duration-300 group bg-white/[0.02] sm:translate-y-8">
                        <div class="w-12 h-12 rounded-full bg-accent-red/10 flex items-center justify-center text-accent-red mb-6 group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path></svg>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">Bespoke Concierge</h4>
                        <p class="text-sm text-white/50 leading-relaxed">24/7 dedicated lifestyle managers orchestrating every detail.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Quick Inquiry / Form Section -->
    <section id="quick-inquiry" class="py-24 bg-[#030b14] border-t border-white/5 relative z-10" x-data="{ shown: false }" x-intersect.once.margin.-150px="shown = true">
        <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'" class="transition-all duration-1000 ease-out max-w-4xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-white tracking-tight">
                    {{ __('storefront.inquiry_title') ?? 'Send an Inquiry' }}
                </h2>
                <p class="text-sm text-white/50 mt-2 max-w-xl mx-auto">
                    {{ __('storefront.inquiry_subtitle') ?? 'Let us know your requirements.' }}
                </p>
            </div>

            @if(session('status'))
                <div class="mb-8 p-4 rounded bg-emerald-900/40 border border-emerald-600/60 text-emerald-200 text-sm text-center">
                    {{ session('status') }}
                </div>
            @endif

            <div class="glass-panel p-8 sm:p-10 rounded-2xl shadow-2xl">
                <form action="{{ route('leads.public-store', ['locale' => app()->getLocale()]) }}" method="POST" class="space-y-6">
                    @csrf
                    <!-- Anti-bot honeypot field -->
                    <div style="display: none;" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-xs font-semibold text-white/70 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_name') ?? 'Name' }} <span class="text-accent-red">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-3 text-sm bg-black/40 border border-white/10 rounded-lg text-white focus:ring-1 focus:ring-accent-red focus:border-accent-red"
                                placeholder="{{ __('storefront.form_name_placeholder') ?? 'Your full name' }}">
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-semibold text-white/70 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_phone') ?? 'WhatsApp / Phone' }} <span class="text-accent-red">*</span>
                            </label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required
                                class="w-full px-4 py-3 text-sm bg-black/40 border border-white/10 rounded-lg text-white focus:ring-1 focus:ring-accent-red focus:border-accent-red"
                                placeholder="{{ __('storefront.form_phone_placeholder') ?? '+971...' }}">
                        </div>
                        <div>
                            <label for="country" class="block text-xs font-semibold text-white/70 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_country') ?? 'Country' }}
                            </label>
                            <input type="text" name="country" id="country" value="{{ old('country') }}"
                                class="w-full px-4 py-3 text-sm bg-black/40 border border-white/10 rounded-lg text-white focus:ring-1 focus:ring-accent-red focus:border-accent-red">
                        </div>
                        <div>
                            <label for="pax" class="block text-xs font-semibold text-white/70 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_pax') ?? 'Number of Pax' }}
                            </label>
                            <input type="number" name="pax" id="pax" min="1" max="100" value="{{ old('pax', 2) }}"
                                class="w-full px-4 py-3 text-sm bg-black/40 border border-white/10 rounded-lg text-white focus:ring-1 focus:ring-accent-red focus:border-accent-red">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="travel_date" class="block text-xs font-semibold text-white/70 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_travel_date') ?? 'Expected Travel Date' }}
                            </label>
                            <input type="date" name="travel_date" id="travel_date" value="{{ old('travel_date') }}"
                                class="w-full px-4 py-3 text-sm bg-black/40 border border-white/10 rounded-lg text-white focus:ring-1 focus:ring-accent-red focus:border-accent-red"
                                style="color-scheme: dark;">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="notes" class="block text-xs font-semibold text-white/70 uppercase tracking-wider mb-2">
                                {{ __('storefront.form_notes') ?? 'Additional Notes' }}
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full px-4 py-3 text-sm bg-black/40 border border-white/10 rounded-lg text-white focus:ring-1 focus:ring-accent-red focus:border-accent-red"
                                placeholder="{{ __('storefront.form_notes_placeholder') ?? 'Any specific requests?' }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                    <div class="text-center pt-4">
                        <button type="submit"
                            class="w-full sm:w-auto px-10 py-4 text-sm font-bold text-white bg-accent-red hover:bg-[#c91840] rounded-full transition-all duration-300 shadow-[0_0_20px_rgba(225,29,72,0.4)]">
                            {{ __('storefront.form_submit') ?? 'Send Inquiry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Floating WhatsApp -->
    <a href="https://wa.me/628111111111?text={{ urlencode(__('storefront.whatsapp_greeting') ?? 'Hello Indogate') }}"
       target="_blank" rel="noopener noreferrer"
       class="fixed bottom-6 {{ in_array(app()->getLocale(), ['ar']) ? 'left-6' : 'right-6' }} z-50 flex items-center gap-2.5 bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-3 rounded-full shadow-2xl hover:scale-105 transition-all duration-200 group">
        <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
        <span class="text-xs font-bold tracking-wide">WhatsApp</span>
    </a>

    <!-- Footer -->
    <footer class="w-full bg-[#030b14] border-t border-white/5 py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-2xl font-black tracking-tight text-white font-sans">
                    INDO<span class="text-accent-red">GATE</span>
                </span>
                <span class="hidden sm:inline-block text-[11px] font-semibold uppercase tracking-wider text-white/50 border-s border-white/30 ps-2">
                    Premium Travel
                </span>
            </div>
            <p class="text-white/40 text-sm text-center md:text-left">&copy; {{ date('Y') }} PT Indogate Travel Indonesia. {{ __('storefront.copyright') ?? 'All rights reserved.' }}</p>
            <div class="flex gap-6">
                <a href="#" class="text-sm font-medium text-white/50 hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="text-sm font-medium text-white/50 hover:text-white transition-colors">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>
