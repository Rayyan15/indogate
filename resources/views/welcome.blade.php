<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Indogate — Unlock your premium experiences</title>
    <meta name="description" content="Indogate offers premium, culturally-tailored travel services in Indonesia.">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">
    
    <!-- Alpine.js & Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
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
        .accent-red { color: #e11d48; } /* Rose-600 */
        .border-accent-red { border-color: #e11d48; }
        .bg-accent-red { background-color: #e11d48; }
        
        /* Hide scrollbar for the cards container */
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
            <a href="#profile" @click="menuOpen = false" class="text-xl font-medium text-white/80 hover:text-white">Our Profile</a>
            <a href="#catalog" @click="menuOpen = false" class="text-xl font-medium text-white/80 hover:text-white">Explore Packages</a>
            <a href="{{ route('register') }}" class="text-xl font-medium text-white/80 hover:text-white">Sign Up</a>
            <a href="{{ route('login') }}" class="text-xl font-medium text-white/80 hover:text-white">Sign In</a>
        </nav>
    </div>

    <!-- Hero Section -->
    <section class="relative h-screen w-full flex flex-col z-10">
        
        <div class="relative z-10 flex flex-col h-full">
            <!-- Header -->
            <header class="w-full px-6 py-8 flex items-center justify-between">
                <div class="flex items-center">
                    <span class="text-2xl font-bold tracking-tight text-white">Indogate<span class="text-xs align-top font-normal opacity-70 ml-0.5">®</span></span>
                </div>
                
                <nav class="hidden lg:flex items-center gap-10">
                    <a href="#profile" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors">Our Profile</a>
                    <a href="#catalog" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors">Packages & Tours</a>
                    <a href="#private-service" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors">Private service</a>
                </nav>

                <div class="flex items-center gap-4">
                    <button @click="menuOpen = true" class="w-11 h-11 rounded-full glass-panel flex flex-col justify-center items-center gap-1.5 hover:bg-white/10 transition-colors cursor-pointer lg:hidden">
                        <span class="w-4 h-[1.5px] bg-white rounded-full"></span>
                        <span class="w-4 h-[1.5px] bg-white rounded-full"></span>
                        <span class="w-4 h-[1.5px] bg-white rounded-full"></span>
                    </button>
                    @guest
                    <a href="{{ route('register') }}" class="bg-white text-[#030b14] px-6 py-3 rounded-full text-[15px] font-semibold hover:bg-gray-100 transition-colors shadow-lg hidden sm:inline-block">
                        Start choosing a tour
                    </a>
                    <a href="{{ route('login') }}" class="text-[15px] font-medium text-white/80 hover:text-white transition-colors ml-4 hidden lg:inline-block">Sign in</a>
                    @else
                    <a href="{{ route('admin.dashboard') }}" class="bg-white text-[#030b14] px-6 py-3 rounded-full text-[15px] font-semibold hover:bg-gray-100 transition-colors shadow-lg">
                        Go to Dashboard
                    </a>
                    @endguest
                </div>
            </header>

            <!-- Hero Main Content -->
            <div class="flex-grow flex flex-col items-center justify-center px-6 mt-[-8vh]" x-data="{ showTitle: false }" x-init="setTimeout(() => showTitle = true, 300)">
                <h1 :class="showTitle ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-12'" class="transition-all duration-1000 ease-out text-[3.25rem] md:text-7xl lg:text-[7.5rem] leading-[1] font-bold text-center tracking-tight text-white text-shadow-lg max-w-6xl mx-auto">
                    Unlock your <br />
                    <span class="italic font-medium">premium</span> experiences
                </h1>
            </div>

            <!-- Hero Bottom Overview -->
            <div class="w-full px-8 pb-10 flex flex-col lg:flex-row justify-between items-end gap-8">
                <div class="w-full lg:w-1/3">
                    <p class="text-xl md:text-2xl text-white/95 font-medium max-w-[340px] leading-[1.3] text-shadow-sm mb-10 lg:mb-14">
                        Unforgettable impressions, unusual locations, unique routes tailored to your wishes.
                    </p>
                    <div class="flex items-center gap-8 md:gap-12 border-b border-white/20 pb-[17px]">
                        <a href="#catalog" class="flex items-end gap-2 border-b-[3px] border-accent-red pb-4 -mb-[19px]">
                            <span class="text-white text-[15px] font-medium">Explore tours</span>
                            <span class="text-xs text-white/50 mb-0.5">(43)</span>
                        </a>
                        <a href="#private-service" class="flex items-end gap-2 pb-4 -mb-[19px] hover:text-white text-white/60 transition-colors">
                            <span class="text-[15px] font-medium">Private service</span>
                            <span class="text-xs opacity-50 mb-0.5">(10)</span>
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
                <h2 class="text-accent-red text-sm font-bold tracking-widest uppercase mb-4">About Indogate</h2>
                <h3 class="text-4xl md:text-5xl font-bold text-white mb-6 leading-tight tracking-tight">Curating Excellence for Discerning Travelers</h3>
                <p class="text-white/70 text-lg leading-relaxed mb-8">
                    We bridge the gap for travelers seeking luxury, cultural alignment, and seamless experiences across Indonesia's most breathtaking destinations. Every route, villa, and private driver is meticulously vetted to meet our uncompromising standards.
                </p>
                <ul class="space-y-4 mb-10">
                    <li class="flex items-center gap-4 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-accent-red">✓</div>
                        <span>Halal-certified premium accommodations</span>
                    </li>
                    <li class="flex items-center gap-4 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-accent-red">✓</div>
                        <span>Professional Arabic-speaking private drivers</span>
                    </li>
                    <li class="flex items-center gap-4 text-white/90">
                        <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 text-accent-red">✓</div>
                        <span>24/7 dedicated concierge service</span>
                    </li>
                </ul>
                <a href="{{ route('register') }}" class="inline-block bg-white text-[#030b14] px-8 py-4 rounded-full font-bold hover:bg-gray-200 transition-colors">
                    Join our exclusive network
                </a>
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

    <!-- Catalog Section -->
    <section id="catalog" class="w-full bg-[#051121] py-24 md:py-32 relative z-10 border-t border-white/5" x-data="{ shown: false }" x-intersect.once.margin.-150px="shown = true">
        <div :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-16'" class="transition-all duration-1000 ease-out max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-end mb-16 gap-6">
                <div>
                    <h2 class="text-accent-red text-sm font-bold tracking-widest uppercase mb-4">Our Catalog</h2>
                    <h3 class="text-4xl md:text-5xl font-bold text-white tracking-tight">Signature Experiences</h3>
                </div>
                <a href="{{ route('register') }}" class="group flex items-center gap-2 text-white/80 hover:text-white font-medium transition-colors">
                    View all packages
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                
                <!-- Package Card 1 -->
                <a href="{{ route('register') }}" class="group relative w-full h-[450px] rounded-[2rem] overflow-hidden bg-white p-2 flex flex-col block transition-transform hover:-translate-y-2 duration-500">
                    <div class="w-full h-[65%] rounded-t-[1.5rem] rounded-b-[1rem] overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1544644181-1484b3fdfc62?q=80&w=800&auto=format&fit=crop" alt="Bali Villa" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute top-3 right-3 flex gap-2">
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">2 Days</span>
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">$252</span>
                        </div>
                    </div>
                    <div class="p-6 h-[35%] flex flex-col justify-between">
                        <h3 class="text-[#030b14] font-bold text-2xl leading-tight">Nusa Penida &<br>Uluwatu Cliffs</h3>
                        <div class="flex justify-between items-end">
                            <span class="text-[#030b14]/60 font-medium">Bali Area</span>
                            <div class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center group-hover:bg-[#030b14] group-hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Package Card 2 -->
                <a href="{{ route('register') }}" class="group relative w-full h-[450px] rounded-[2rem] overflow-hidden bg-white p-2 flex flex-col block transition-transform hover:-translate-y-2 duration-500">
                    <div class="w-full h-[65%] rounded-t-[1.5rem] rounded-b-[1rem] overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?q=80&w=800&auto=format&fit=crop" alt="Komodo Yacht" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute top-3 right-3 flex gap-2">
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">3 Days</span>
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">$850</span>
                        </div>
                    </div>
                    <div class="p-6 h-[35%] flex flex-col justify-between">
                        <h3 class="text-[#030b14] font-bold text-2xl leading-tight">Private Yacht trip to<br>Komodo Island</h3>
                        <div class="flex justify-between items-end">
                            <span class="text-[#030b14]/60 font-medium">Labuan Bajo</span>
                            <div class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center group-hover:bg-[#030b14] group-hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Package Card 3 -->
                <a href="{{ route('register') }}" class="group relative w-full h-[450px] rounded-[2rem] overflow-hidden bg-white p-2 flex flex-col block transition-transform hover:-translate-y-2 duration-500">
                    <div class="w-full h-[65%] rounded-t-[1.5rem] rounded-b-[1rem] overflow-hidden relative">
                        <img src="https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?q=80&w=800&auto=format&fit=crop" alt="Raja Ampat" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                        <div class="absolute top-3 right-3 flex gap-2">
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">5 Days</span>
                            <span class="glass-panel px-3 py-1.5 rounded-full text-xs font-semibold text-white bg-black/30 backdrop-blur-md">$1,200</span>
                        </div>
                    </div>
                    <div class="p-6 h-[35%] flex flex-col justify-between">
                        <h3 class="text-[#030b14] font-bold text-2xl leading-tight">Exclusive Retreat at<br>Raja Ampat</h3>
                        <div class="flex justify-between items-end">
                            <span class="text-[#030b14]/60 font-medium">Papua</span>
                            <div class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center group-hover:bg-[#030b14] group-hover:text-white transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Private Service Section -->
    <section id="private-service" class="w-full py-24 md:py-40 relative z-10 border-t border-white/5 overflow-hidden" x-data="{ shown: false }" x-intersect.once.margin.-150px="shown = true">
        <!-- Section Background Image -->
        <img src="https://images.unsplash.com/photo-1540946485063-a40da27545f8?q=80&w=2000&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover opacity-20" alt="Private Yacht">
        <!-- Deep Blue Gradient Overlays -->
        <div class="absolute inset-0 bg-gradient-to-t from-[#030b14] via-[#030b14]/70 to-[#030b14]"></div>
        
        <div :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'" class="transition-all duration-1000 ease-out max-w-7xl mx-auto px-6 text-center relative z-20">
            <h2 class="text-accent-red text-sm font-bold tracking-widest uppercase mb-4">Exclusively Yours</h2>
            <h3 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 tracking-tight">Private Service & Tailored Itineraries</h3>
            <p class="text-white/70 text-lg md:text-xl max-w-3xl mx-auto leading-relaxed mb-10">
                Beyond our signature packages, we offer fully bespoke travel planning. Whether you require a private yacht charter, elite villa sourcing, or a dedicated security and concierge team throughout your stay, our private service division will architect an itinerary limited only by your imagination.
            </p>
            <a href="{{ route('register') }}" class="inline-block bg-white border-2 border-white text-[#030b14] px-8 py-4 rounded-full font-bold hover:bg-transparent hover:text-white transition-colors duration-300 shadow-[0_0_30px_rgba(255,255,255,0.15)]">
                Request a Consultation
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="w-full bg-[#030b14] border-t border-white/5 py-12 relative z-10">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-6">
            <span class="text-2xl font-bold tracking-tight text-white">Indogate<span class="text-xs align-top font-normal opacity-70 ml-0.5">®</span></span>
            <p class="text-white/40 text-sm text-center md:text-left">&copy; {{ date('Y') }} Indogate Travel. All rights reserved.</p>
            <div class="flex gap-6">
                <a href="#" class="text-sm font-medium text-white/50 hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="text-sm font-medium text-white/50 hover:text-white transition-colors">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>
