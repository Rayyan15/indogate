<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Indogate — Indonesia's Premier Service for Arab Travelers</title>
    <meta name="description" content="Indogate offers premium, culturally-tailored travel services in Indonesia for Middle Eastern tourists. Flights, hotels, and private drivers — all in one place.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;700;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --gold: #C9A84C;
            --gold-light: #E8C97A;
            --charcoal: #1A1A2E;
            --charcoal-mid: #16213E;
            --charcoal-light: #0F3460;
            --cream: #F5F0E8;
        }
        body { font-family: 'Inter', sans-serif; background: var(--charcoal); color: var(--cream); }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .text-gold { color: var(--gold); }
        .bg-gold { background-color: var(--gold); }
        .border-gold { border-color: var(--gold); }
        .gradient-hero {
            background: linear-gradient(135deg, #1A1A2E 0%, #16213E 40%, #0F3460 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .card-glass {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(201,168,76,0.2);
        }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: #1A1A2E;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201,168,76,0.4);
        }
        .section-divider {
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
            height: 1px;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .float-anim { animation: float 6s ease-in-out infinite; }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in-up { animation: fadeInUp 0.8s ease forwards; }
        .fade-in-up-delay-1 { animation-delay: 0.2s; opacity: 0; }
        .fade-in-up-delay-2 { animation-delay: 0.4s; opacity: 0; }
        .fade-in-up-delay-3 { animation-delay: 0.6s; opacity: 0; }
        .service-card:hover { transform: translateY(-8px); }
        .service-card { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-black/30 backdrop-blur-md border-b border-gold/10">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-2xl font-bold font-playfair gradient-text">Indogate</span>
        </div>
        <div class="hidden md:flex items-center gap-8">
            <a href="#services" class="text-sm text-cream/70 hover:text-gold transition-colors">Services</a>
            <a href="#why-us" class="text-sm text-cream/70 hover:text-gold transition-colors">Why Us</a>
            <a href="#how-it-works" class="text-sm text-cream/70 hover:text-gold transition-colors">How It Works</a>
        </div>
        <div class="flex items-center gap-3">
            @guest
            <a href="{{ route('login') }}" class="text-sm text-cream/70 hover:text-gold transition-colors">Sign In</a>
            <a href="{{ route('register') }}" class="btn-gold px-5 py-2 rounded-full text-sm">Get Started</a>
            @else
            <a href="{{ route('admin.dashboard') }}" class="btn-gold px-5 py-2 rounded-full text-sm">Dashboard</a>
            @endguest
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="gradient-hero min-h-screen flex items-center relative overflow-hidden pt-20">
    <!-- Background decorative elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 -right-32 w-96 h-96 rounded-full bg-gold/5 blur-3xl"></div>
        <div class="absolute bottom-1/4 -left-32 w-96 h-96 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full border border-gold/5"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 py-20 grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
        <div>
            <div class="inline-flex items-center gap-2 bg-gold/10 border border-gold/30 rounded-full px-4 py-2 mb-8 fade-in-up">
                <span class="w-2 h-2 rounded-full bg-gold animate-pulse"></span>
                <span class="text-gold text-sm font-medium">Premium Travel Experience</span>
            </div>

            <h1 class="font-playfair text-5xl md:text-7xl font-bold leading-tight mb-6 fade-in-up fade-in-up-delay-1">
                Indonesia,<br>
                <span class="gradient-text">Perfectly</span><br>
                Curated
            </h1>

            <p class="text-cream/60 text-lg leading-relaxed mb-10 max-w-lg fade-in-up fade-in-up-delay-2">
                We bridge the gap for Arab travelers — offering Halal-certified experiences, Arabic-speaking drivers, and culturally-tailored services across Indonesia's most beautiful destinations.
            </p>

            <div class="flex flex-wrap gap-4 fade-in-up fade-in-up-delay-3">
                @guest
                <a href="{{ route('register') }}" class="btn-gold px-8 py-4 rounded-full text-base font-bold">
                    Start Your Journey
                </a>
                @else
                <a href="{{ route('admin.dashboard') }}" class="btn-gold px-8 py-4 rounded-full text-base font-bold">
                    Go to Dashboard
                </a>
                @endguest
                <a href="#services" class="border border-gold/40 text-gold px-8 py-4 rounded-full text-base font-medium hover:bg-gold/10 transition-all">
                    Explore Services
                </a>
            </div>

            <div class="flex items-center gap-8 mt-12 fade-in-up fade-in-up-delay-3">
                <div>
                    <div class="text-3xl font-bold text-gold">500+</div>
                    <div class="text-sm text-cream/50 mt-1">Happy Travelers</div>
                </div>
                <div class="w-px h-12 bg-gold/20"></div>
                <div>
                    <div class="text-3xl font-bold text-gold">15+</div>
                    <div class="text-sm text-cream/50 mt-1">Destinations</div>
                </div>
                <div class="w-px h-12 bg-gold/20"></div>
                <div>
                    <div class="text-3xl font-bold text-gold">100%</div>
                    <div class="text-sm text-cream/50 mt-1">Halal Certified</div>
                </div>
            </div>
        </div>

        <div class="relative float-anim">
            <div class="relative z-10 grid grid-cols-2 gap-4">
                <div class="card-glass rounded-2xl p-6 col-span-2">
                    <div class="text-gold text-4xl mb-3">✈</div>
                    <div class="font-semibold text-lg">Bali → Jakarta</div>
                    <div class="text-cream/50 text-sm">Garuda Indonesia · Dep 08:00</div>
                    <div class="mt-3 flex justify-between items-center">
                        <span class="text-gold font-bold text-xl">IDR 1,200,000</span>
                        <span class="text-xs bg-green-500/20 text-green-400 px-3 py-1 rounded-full">Available</span>
                    </div>
                </div>
                <div class="card-glass rounded-2xl p-5">
                    <div class="text-gold text-3xl mb-2">🏨</div>
                    <div class="font-semibold">The Mulia Bali</div>
                    <div class="text-cream/50 text-xs mt-1">★★★★★</div>
                    <div class="text-gold font-bold mt-2">$450/night</div>
                </div>
                <div class="card-glass rounded-2xl p-5">
                    <div class="text-gold text-3xl mb-2">🚗</div>
                    <div class="font-semibold">Private Driver</div>
                    <div class="text-cream/50 text-xs mt-1">Arabic Speaking</div>
                    <div class="text-gold font-bold mt-2">From $80/day</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="py-24 bg-charcoal-mid" style="background: var(--charcoal-mid)">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <div class="text-gold text-sm font-semibold tracking-widest uppercase mb-4">Our Services</div>
            <h2 class="font-playfair text-4xl md:text-5xl font-bold">Everything You Need,<br><span class="gradient-text">In One Place</span></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="service-card card-glass rounded-3xl p-8">
                <div class="w-16 h-16 rounded-2xl bg-gold/20 flex items-center justify-center text-3xl mb-6">✈</div>
                <h3 class="text-xl font-bold mb-3">Flight Booking</h3>
                <p class="text-cream/50 text-sm leading-relaxed">Hand-curated flight routes across Indonesia with the best carriers. Transparent pricing, no hidden fees.</p>
                <div class="mt-6 flex items-center gap-2 text-gold text-sm font-medium">
                    <span>Browse Flights</span>
                    <span>&rarr;</span>
                </div>
            </div>

            <div class="service-card card-glass rounded-3xl p-8 border-gold/40" style="border-color: rgba(201,168,76,0.4)">
                <div class="w-16 h-16 rounded-2xl bg-gold/20 flex items-center justify-center text-3xl mb-6">🏨</div>
                <h3 class="text-xl font-bold mb-3">Premium Hotels</h3>
                <p class="text-cream/50 text-sm leading-relaxed">Halal-certified accommodations from 3 to 5-star properties, vetted for quality and cultural compatibility.</p>
                <div class="mt-6 flex items-center gap-2 text-gold text-sm font-medium">
                    <span>Explore Hotels</span>
                    <span>&rarr;</span>
                </div>
            </div>

            <div class="service-card card-glass rounded-3xl p-8">
                <div class="w-16 h-16 rounded-2xl bg-gold/20 flex items-center justify-center text-3xl mb-6">🚗</div>
                <h3 class="text-xl font-bold mb-3">Private Drivers</h3>
                <p class="text-cream/50 text-sm leading-relaxed">Professional, Arabic-speaking drivers available. Female drivers available upon request for families.</p>
                <div class="mt-6 flex items-center gap-2 text-gold text-sm font-medium">
                    <span>Book a Driver</span>
                    <span>&rarr;</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Us Section -->
<section id="why-us" class="py-24 gradient-hero relative overflow-hidden">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="font-playfair text-4xl md:text-5xl font-bold">Why <span class="gradient-text">Indogate?</span></h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
            $features = [
                ['icon' => '🌙', 'title' => 'Halal-First', 'desc' => 'Every service vetted for Halal compliance and Islamic values.'],
                ['icon' => '🗣', 'title' => 'Arabic Support', 'desc' => 'Full Arabic-language support on platform and with our drivers.'],
                ['icon' => '🔒', 'title' => 'Secure Payments', 'desc' => 'Manual bank transfer with admin verification for peace of mind.'],
                ['icon' => '⭐', 'title' => 'Premium Quality', 'desc' => 'Every hotel and service is personally vetted by our team.'],
            ];
            @endphp
            @foreach($features as $feature)
            <div class="card-glass rounded-2xl p-6 text-center">
                <div class="text-4xl mb-4">{{ $feature['icon'] }}</div>
                <h3 class="font-bold text-lg mb-2">{{ $feature['title'] }}</h3>
                <p class="text-cream/50 text-sm">{{ $feature['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="how-it-works" class="py-24" style="background: var(--charcoal)">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="font-playfair text-4xl md:text-5xl font-bold">How It <span class="gradient-text">Works</span></h2>
            <p class="text-cream/50 mt-4 max-w-xl mx-auto">Book your entire trip in 4 simple steps.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            @php
            $steps = [
                ['num' => '01', 'title' => 'Create Account', 'desc' => 'Register and set up your traveler profile securely.'],
                ['num' => '02', 'title' => 'Choose Services', 'desc' => 'Browse flights, hotels, and drivers. Add to your cart.'],
                ['num' => '03', 'title' => 'Transfer Payment', 'desc' => 'Pay via manual bank transfer to our secure account.'],
                ['num' => '04', 'title' => 'Enjoy Indonesia', 'desc' => 'Your itinerary is confirmed and drivers are assigned.'],
            ];
            @endphp
            @foreach($steps as $i => $step)
            <div class="relative">
                @if($i < 3)
                <div class="hidden md:block absolute top-8 left-1/2 w-full h-px" style="background: linear-gradient(90deg, var(--gold), transparent); opacity: 0.3;"></div>
                @endif
                <div class="text-center relative">
                    <div class="w-16 h-16 rounded-full bg-gold/10 border border-gold/40 flex items-center justify-center mx-auto mb-4">
                        <span class="gradient-text font-bold text-lg">{{ $step['num'] }}</span>
                    </div>
                    <h3 class="font-bold text-lg mb-2">{{ $step['title'] }}</h3>
                    <p class="text-cream/50 text-sm">{{ $step['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-24 relative overflow-hidden" style="background: var(--charcoal-mid)">
    <div class="max-w-3xl mx-auto px-6 text-center">
        <h2 class="font-playfair text-4xl md:text-6xl font-bold mb-6">
            Ready to Explore<br><span class="gradient-text">Indonesia?</span>
        </h2>
        <p class="text-cream/50 text-lg mb-10">Join hundreds of Arab travelers who've discovered Indonesia with Indogate.</p>
        @guest
        <a href="{{ route('register') }}" class="btn-gold inline-block px-10 py-5 rounded-full text-lg font-bold">
            Create Your Free Account
        </a>
        @else
        <a href="{{ route('admin.dashboard') }}" class="btn-gold inline-block px-10 py-5 rounded-full text-lg font-bold">
            Go to Dashboard
        </a>
        @endguest
    </div>
</section>

<!-- Footer -->
<footer class="border-t border-gold/10 py-12" style="background: var(--charcoal)">
    <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <span class="font-playfair text-2xl font-bold gradient-text">Indogate</span>
        <p class="text-cream/30 text-sm">&copy; {{ date('Y') }} Indogate. All rights reserved.</p>
        <div class="flex gap-6">
            <a href="#" class="text-cream/30 hover:text-gold text-sm transition-colors">Privacy</a>
            <a href="#" class="text-cream/30 hover:text-gold text-sm transition-colors">Terms</a>
        </div>
    </div>
</footer>

</body>
</html>
