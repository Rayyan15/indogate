<x-customer-layout>
    <x-slot name="header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-light text-white tracking-wide">
                Finalize <span class="text-gold font-bold">Booking</span>
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main Form / Action Area -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Concierge Details -->
                    <div class="card-premium rounded-2xl p-8 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-gold opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4"></div>
                        
                        <div class="relative z-10">
                            <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-800 border border-gold flex items-center justify-center text-gold">1</div>
                                Guest Details
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Primary Guest</p>
                                    <p class="font-medium text-white">{{ Auth::user()->name }}</p>
                                </div>
                                <div class="bg-slate-800/50 p-4 rounded-xl border border-slate-700">
                                    <p class="text-xs text-slate-500 uppercase tracking-wider mb-1">Contact Email</p>
                                    <p class="font-medium text-white">{{ Auth::user()->email }}</p>
                                </div>
                            </div>
                            
                            <p class="text-sm text-slate-400">
                                Need to update details or add special requests? Please <a href="{{ route('profile.edit') }}" class="text-gold hover:underline">edit your profile</a> before finalizing.
                            </p>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="card-premium rounded-2xl p-8">
                        <h3 class="text-xl font-bold text-white mb-6 flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-slate-800 border border-gold flex items-center justify-center text-gold">2</div>
                            Secure Payment
                        </h3>
                        
                        <div class="bg-slate-900 border border-slate-700 rounded-xl p-6 mb-8 flex flex-col md:flex-row gap-6 items-center">
                            <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center border border-slate-600 shrink-0">
                                <svg class="w-8 h-8 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            </div>
                            <div>
                                <h4 class="text-lg font-medium text-white mb-2">Manual Bank Transfer</h4>
                                <p class="text-slate-400 text-sm">
                                    To ensure the highest level of security and personalized service, we process payments via verified bank transfers. 
                                    After completing your booking here, you will receive a Booking ID and instructions to upload your payment proof securely from your dashboard.
                                </p>
                            </div>
                        </div>
                        
                        <form action="{{ route('checkout.store') }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full btn-gold py-4 rounded-xl font-bold tracking-wider uppercase shadow-xl flex items-center justify-center gap-3 text-lg group">
                                Confirm & Reserve Itinerary
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </button>
                            <p class="text-center text-xs text-slate-500 mt-4">By clicking confirm, you agree to our Terms of Service & Cancellation Policy.</p>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Summary -->
                <div>
                    <div class="card-premium rounded-2xl p-6 sticky top-28">
                        <h3 class="text-lg font-bold text-white mb-6">Order Summary</h3>
                        
                        <div class="space-y-4 max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($cart as $item)
                                <div class="flex justify-between items-start gap-4 pb-4 border-b border-slate-700/50 last:border-0 last:pb-0">
                                    <div>
                                        <p class="font-medium text-slate-300 text-sm line-clamp-2">{{ $item['name'] }}</p>
                                        <p class="text-xs text-slate-500 mt-1">Qty: {{ $item['quantity'] }}</p>
                                    </div>
                                    <div class="font-medium text-white whitespace-nowrap">
                                        IDR {{ number_format($item['price'] * $item['quantity']) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6 pt-4 border-t border-slate-700">
                            <div class="flex justify-between items-end">
                                <span class="text-slate-400">Total Amount</span>
                                <span class="font-bold text-2xl text-gold">IDR {{ number_format($totalAmount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(30, 41, 59, 0.5); 
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(212, 175, 55, 0.5); 
            border-radius: 4px;
        }
    </style>
</x-customer-layout>
