<x-customer-layout>
    <x-slot name="header">
        <div class="text-center">
            <h2 class="text-3xl font-light text-white tracking-wide mb-2">
                Discover Your Next <span class="text-gold font-bold">Journey</span>
            </h2>
            <p class="text-slate-400">Curated experiences tailored for the discerning traveler.</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Custom Premium Tabs -->
            <div class="flex justify-center mb-12">
                <div class="inline-flex bg-slate-800 rounded-full p-1 border border-slate-700 shadow-xl">
                    <a href="{{ route('search.index', ['type' => 'flights']) }}" 
                       class="px-8 py-3 rounded-full text-sm font-medium transition-all duration-300 {{ $type === 'flights' ? 'bg-gold text-slate-900 shadow-lg' : 'text-slate-400 hover:text-white hover:bg-slate-700' }}">
                       Private Flights
                    </a>
                    <a href="{{ route('search.index', ['type' => 'hotels']) }}" 
                       class="px-8 py-3 rounded-full text-sm font-medium transition-all duration-300 {{ $type === 'hotels' ? 'bg-gold text-slate-900 shadow-lg' : 'text-slate-400 hover:text-white hover:bg-slate-700' }}">
                       Luxury Stays
                    </a>
                    <a href="{{ route('search.index', ['type' => 'drivers']) }}" 
                       class="px-8 py-3 rounded-full text-sm font-medium transition-all duration-300 {{ $type === 'drivers' ? 'bg-gold text-slate-900 shadow-lg' : 'text-slate-400 hover:text-white hover:bg-slate-700' }}">
                       Chauffeurs
                    </a>
                </div>
            </div>

            <!-- Search Results Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @if($type === 'flights' && $flights)
                    @forelse($flights as $flight)
                        <div class="card-premium rounded-2xl p-6 flex flex-col relative overflow-hidden group">
                            <!-- Decorative glow -->
                            <div class="absolute -top-10 -right-10 w-32 h-32 bg-gold opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity"></div>
                            
                            <div class="flex justify-between items-start mb-4 relative z-10">
                                <div>
                                    <h3 class="font-bold text-xl text-white mb-1 tracking-wide">{{ $flight->airline }}</h3>
                                    <p class="text-gold text-xs uppercase tracking-widest font-semibold">Premium Class</p>
                                </div>
                                <div class="bg-slate-800 p-2 rounded-lg border border-slate-700">
                                    <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mb-6 text-slate-300 relative z-10">
                                <div class="text-center">
                                    <p class="font-semibold text-lg text-white">{{ $flight->origin }}</p>
                                    <p class="text-xs text-slate-500">Origin</p>
                                </div>
                                <div class="flex-grow flex items-center justify-center px-4">
                                    <div class="h-px bg-slate-600 w-full relative">
                                        <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/2 w-2 h-2 rounded-full border border-gold bg-slate-900"></div>
                                        <div class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-slate-600"></div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <p class="font-semibold text-lg text-white">{{ $flight->destination }}</p>
                                    <p class="text-xs text-slate-500">Destination</p>
                                </div>
                            </div>
                            
                            <div class="mb-6 relative z-10">
                                <p class="text-slate-400 text-sm mb-1 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ $flight->departure_at->format('d M Y, H:i') }}
                                </p>
                            </div>
                            
                            <div class="mt-auto pt-4 border-t border-slate-700/50 relative z-10">
                                <div class="flex justify-between items-end mb-4">
                                    <div>
                                        <p class="text-xs text-slate-500 mb-1">Total Price</p>
                                        <p class="font-bold text-2xl text-gold">IDR {{ number_format($flight->base_price) }}</p>
                                    </div>
                                </div>
                                
                                <form action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="bookable_type" value="App\Models\FlightRoute">
                                    <input type="hidden" name="bookable_id" value="{{ $flight->id }}">
                                    <input type="hidden" name="name" value="Flight: {{ $flight->origin }} to {{ $flight->destination }}">
                                    <input type="hidden" name="price" value="{{ $flight->base_price }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="w-full btn-gold rounded-lg py-3 font-semibold tracking-wide flex items-center justify-center gap-2">
                                        Select Flight
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-20 bg-slate-800/50 rounded-2xl border border-slate-700 border-dashed">
                            <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-slate-400 text-lg">No private flights available for this route currently.</p>
                        </div>
                    @endforelse
                @endif

                @if($type === 'hotels' && $hotels)
                    @forelse($hotels as $hotel)
                        <div class="card-premium rounded-2xl p-6 flex flex-col relative overflow-hidden group">
                            <!-- Decorative glow -->
                            <div class="absolute -top-10 -left-10 w-32 h-32 bg-gold opacity-10 rounded-full blur-3xl group-hover:opacity-20 transition-opacity"></div>

                            <div class="flex justify-between items-start mb-4 relative z-10">
                                <div>
                                    <h3 class="font-bold text-xl text-white mb-1">{{ $hotel->name }}</h3>
                                    <p class="text-slate-400 text-sm flex items-center gap-1">
                                        <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $hotel->location }}
                                    </p>
                                </div>
                                <div class="bg-slate-800/80 px-2 py-1 rounded border border-slate-700 flex items-center gap-1">
                                    <span class="text-gold text-sm font-bold">{{ $hotel->star_rating }}.0</span>
                                    <svg class="w-4 h-4 text-gold fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                </div>
                            </div>
                            
                            <div class="mt-auto pt-6 relative z-10">
                                <form action="{{ route('cart.add') }}" method="POST" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="bookable_type" value="App\Models\Hotel">
                                    <input type="hidden" name="bookable_id" value="{{ $hotel->id }}">
                                    <input type="hidden" name="name" value="Hotel: {{ $hotel->name }}">
                                    <input type="hidden" name="price" value="{{ $hotel->base_price_per_night }}">
                                    
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <p class="text-xs text-slate-500 mb-1">Per Night</p>
                                            <p class="font-bold text-2xl text-gold">IDR {{ number_format($hotel->base_price_per_night) }}</p>
                                        </div>
                                        
                                        <div class="flex flex-col items-end gap-1">
                                            <label class="text-xs text-slate-400">Duration (Nights)</label>
                                            <input type="number" name="quantity" value="1" min="1" class="w-20 bg-slate-800 border border-slate-600 rounded text-white text-center py-1 focus:ring-gold focus:border-gold">
                                        </div>
                                    </div>
                                    
                                    <button class="w-full btn-gold rounded-lg py-3 font-semibold tracking-wide flex items-center justify-center gap-2 mt-2">
                                        Reserve Suite
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-20 bg-slate-800/50 rounded-2xl border border-slate-700 border-dashed">
                            <p class="text-slate-400 text-lg">No luxury stays available in this region currently.</p>
                        </div>
                    @endforelse
                @endif

                @if($type === 'drivers' && $drivers)
                    @forelse($drivers as $driver)
                        <div class="card-premium rounded-2xl p-6 flex flex-col relative overflow-hidden group">
                            
                            <div class="flex items-center gap-4 mb-6 relative z-10">
                                <div class="w-16 h-16 rounded-full bg-slate-700 flex items-center justify-center border-2 border-gold/50 text-gold shadow-lg overflow-hidden">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-xl text-white">{{ $driver->full_name }}</h3>
                                    <p class="text-slate-400 text-sm capitalize mt-1 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                        Professional Chauffeur
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-auto relative z-10 pt-4 border-t border-slate-700/50">
                                <form action="{{ route('cart.add') }}" method="POST" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="bookable_type" value="App\Models\Driver">
                                    <input type="hidden" name="bookable_id" value="{{ $driver->id }}">
                                    <input type="hidden" name="name" value="Driver: {{ $driver->full_name }}">
                                    <input type="hidden" name="price" value="{{ $driverPrice }}">
                                    
                                    <div class="flex justify-between items-end">
                                        <div>
                                            <p class="text-xs text-slate-500 mb-1">Daily Rate</p>
                                            <p class="font-bold text-2xl text-gold">IDR {{ number_format($driverPrice) }}</p>
                                        </div>
                                        
                                        <div class="flex flex-col items-end gap-1">
                                            <label class="text-xs text-slate-400">Duration (Days)</label>
                                            <input type="number" name="quantity" value="1" min="1" class="w-20 bg-slate-800 border border-slate-600 rounded text-white text-center py-1 focus:ring-gold focus:border-gold">
                                        </div>
                                    </div>
                                    
                                    <button class="w-full btn-gold rounded-lg py-3 font-semibold tracking-wide flex items-center justify-center gap-2 mt-2">
                                        Hire Chauffeur
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-20 bg-slate-800/50 rounded-2xl border border-slate-700 border-dashed">
                            <p class="text-slate-400 text-lg">No chauffeurs available at the moment.</p>
                        </div>
                    @endforelse
                @endif
            </div>
        </div>
    </div>
</x-customer-layout>
