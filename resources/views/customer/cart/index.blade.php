<x-customer-layout>
    <x-slot name="header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-light text-white tracking-wide">
                Your <span class="text-gold font-bold">Itinerary</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(empty($cart))
                <div class="flex flex-col items-center justify-center py-20 bg-slate-800/30 rounded-2xl border border-slate-700/50">
                    <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center border-2 border-slate-700 mb-6">
                        <svg class="w-10 h-10 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <p class="text-slate-400 text-lg mb-8">Your itinerary is currently empty.</p>
                    <a href="{{ route('search.index') }}" class="btn-gold px-8 py-3 rounded-full font-semibold tracking-wide">Explore Experiences</a>
                </div>
            @else
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Cart Items -->
                    <div class="lg:w-2/3 space-y-4">
                        @php $total = 0; @endphp
                        @foreach($cart as $index => $item)
                            @php 
                                $subtotal = $item['price'] * $item['quantity'];
                                $total += $subtotal;
                            @endphp
                            <div class="card-premium rounded-2xl p-6 flex flex-col sm:flex-row justify-between items-center gap-6">
                                <div class="flex items-center gap-4 w-full sm:w-auto">
                                    <div class="w-12 h-12 bg-slate-800 rounded-lg flex items-center justify-center border border-slate-700 shrink-0 text-gold">
                                        @if(str_contains(strtolower($item['bookable_type']), 'flight'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        @elseif(str_contains(strtolower($item['bookable_type']), 'hotel'))
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        @else
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-white">{{ $item['name'] }}</h4>
                                        <p class="text-sm text-slate-400 mt-1">{{ class_basename($item['bookable_type']) }} Service</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-8">
                                    <div class="text-right">
                                        <p class="text-xs text-slate-500 mb-1">{{ $item['quantity'] }} &times; IDR {{ number_format($item['price']) }}</p>
                                        <p class="font-bold text-lg text-white">IDR {{ number_format($subtotal) }}</p>
                                    </div>
                                    
                                    <form action="{{ route('cart.remove', $index) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="p-2 text-slate-500 hover:text-red-400 bg-slate-800 hover:bg-slate-700 rounded-lg transition-colors border border-slate-700">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                        
                        <div class="pt-6">
                            <a href="{{ route('search.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-gold transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Add more services
                            </a>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="lg:w-1/3">
                        <div class="card-premium rounded-2xl p-6 sticky top-28">
                            <h3 class="text-xl font-bold text-white mb-6 border-b border-slate-700 pb-4">Itinerary Summary</h3>
                            
                            <div class="space-y-4 mb-6">
                                <div class="flex justify-between text-slate-300">
                                    <span>Subtotal</span>
                                    <span>IDR {{ number_format($total) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-300">
                                    <span>Concierge Fee</span>
                                    <span class="text-emerald-400">Complimentary</span>
                                </div>
                                <div class="flex justify-between text-slate-300">
                                    <span>Taxes</span>
                                    <span class="text-slate-500 text-sm">Calculated at checkout</span>
                                </div>
                            </div>
                            
                            <div class="border-t border-slate-700 pt-4 mb-8">
                                <div class="flex justify-between items-end">
                                    <span class="text-lg text-white">Estimated Total</span>
                                    <span class="text-2xl font-bold text-gold">IDR {{ number_format($total) }}</span>
                                </div>
                            </div>
                            
                            <a href="{{ route('checkout.index') }}" class="block w-full text-center btn-gold py-4 rounded-xl font-bold tracking-wider uppercase text-sm shadow-lg">
                                Proceed to Checkout
                            </a>
                            
                            <div class="mt-6 flex items-center justify-center gap-2 text-slate-500 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                Secure encryption on all transactions
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-customer-layout>
