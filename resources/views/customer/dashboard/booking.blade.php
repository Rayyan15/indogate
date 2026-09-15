<x-customer-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <div class="mb-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-gold transition-colors text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Back to Dashboard
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Itinerary/Invoice Details -->
            <div class="lg:col-span-2 space-y-8">
                <div class="card-premium rounded-2xl p-8 relative overflow-hidden">
                    <!-- Premium Header Pattern -->
                    <div class="absolute top-0 left-0 w-full h-32 bg-slate-800/80 border-b border-slate-700/50"></div>
                    <div class="absolute top-0 right-0 w-64 h-64 bg-gold opacity-5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4 pointer-events-none"></div>
                    
                    <div class="relative z-10">
                        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-12 pb-8 border-b border-slate-700/50">
                            <div>
                                <h1 class="text-3xl font-light text-white tracking-wide mb-1">
                                    Booking <span class="font-bold">Summary</span>
                                </h1>
                                <p class="text-gold font-mono tracking-wider">REF: {{ strtoupper(substr($booking->booking_number, 0, 8)) }}</p>
                            </div>
                            
                            <div class="text-left sm:text-right">
                                <p class="text-sm text-slate-400 mb-1">Date of Issue</p>
                                <p class="font-medium text-white">{{ $booking->created_at->format('d M Y') }}</p>
                                
                                <div class="mt-4">
                                    @if($booking->status === 'confirmed')
                                        <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_15px_rgba(52,211,153,0.15)]">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Confirmed
                                        </span>
                                    @elseif($booking->status === 'pending_payment')
                                        <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-bold bg-gold/10 text-gold border border-gold/20 shadow-[0_0_15px_rgba(212,175,55,0.15)]">
                                            <span class="w-2 h-2 rounded-full bg-gold"></span> Awaiting Payment
                                        </span>
                                    @elseif(str_contains($booking->status, 'rejected'))
                                        <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span> Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-bold bg-slate-500/10 text-slate-300 border border-slate-500/20">
                                            <span class="w-2 h-2 rounded-full bg-slate-500"></span> {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <h4 class="text-sm font-semibold text-slate-500 uppercase tracking-widest mb-6">Reserved Services</h4>
                        
                        <div class="space-y-6">
                            @foreach($booking->items as $item)
                                <div class="flex flex-col sm:flex-row justify-between gap-4 p-4 rounded-xl bg-slate-800/30 border border-slate-700/30">
                                    <div class="flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-lg bg-slate-800 border border-slate-600 flex items-center justify-center text-gold shrink-0">
                                            @if(str_contains(strtolower($item->bookable_type), 'flight'))
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            @elseif(str_contains(strtolower($item->bookable_type), 'hotel'))
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-white text-lg">{{ class_basename($item->bookable_type) }} Service</p>
                                            <p class="text-sm text-slate-400 mt-1">Ref ID: #{{ $item->bookable_id }} &bull; Qty: {{ $item->quantity }} &times; IDR {{ number_format($item->unit_price) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-left sm:text-right pt-2 sm:pt-0">
                                        <p class="text-xs text-slate-500 mb-1">Subtotal</p>
                                        <p class="font-semibold text-white">IDR {{ number_format($item->subtotal) }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-slate-700/50 flex flex-col items-end">
                            <div class="w-full sm:w-1/2 space-y-3">
                                <div class="flex justify-between text-slate-400">
                                    <span>Subtotal</span>
                                    <span>IDR {{ number_format($booking->total_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-slate-400 pb-4 border-b border-slate-700">
                                    <span>Taxes & Fees</span>
                                    <span>Included</span>
                                </div>
                                <div class="flex justify-between items-end pt-2">
                                    <span class="text-lg text-white font-medium">Grand Total</span>
                                    <span class="text-2xl font-bold text-gold">IDR {{ number_format($booking->total_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action / Payment Area -->
            <div class="space-y-6">
                @if($booking->status === 'pending_payment' || str_contains($booking->status, 'rejected'))
                    <div class="card-premium rounded-2xl p-6 border-gold/30 shadow-[0_0_20px_rgba(212,175,55,0.05)]">
                        <h3 class="font-bold text-lg text-white mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Complete Payment
                        </h3>
                        
                        <div class="bg-slate-900 border border-slate-700 p-4 rounded-xl mb-6">
                            <p class="text-sm text-slate-400 mb-3">Please transfer the exact amount of <span class="font-bold text-gold">IDR {{ number_format($booking->total_amount) }}</span> to our secure bank account:</p>
                            
                            <div class="space-y-2 text-white">
                                <div class="flex justify-between border-b border-slate-800 pb-2">
                                    <span class="text-slate-500">Bank</span>
                                    <span class="font-medium">Bank Mandiri</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-800 pb-2">
                                    <span class="text-slate-500">Account No.</span>
                                    <span class="font-mono font-medium">123-456-7890</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Beneficiary</span>
                                    <span class="font-medium">PT Indogate Travel</span>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('customer.payments.store', $booking) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-slate-400 mb-2">Upload Transfer Receipt</label>
                                <div class="relative border-2 border-dashed border-slate-600 rounded-xl p-6 text-center hover:border-gold transition-colors bg-slate-800/50">
                                    <input type="file" name="proof" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" accept="image/*" required id="file-upload">
                                    <div class="pointer-events-none">
                                        <svg class="w-8 h-8 text-slate-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                        <p class="text-sm text-white font-medium" id="file-name">Click to browse or drag file here</p>
                                        <p class="text-xs text-slate-500 mt-1">JPG, PNG up to 2MB</p>
                                    </div>
                                </div>
                                @error('proof')
                                    <p class="text-rose-400 text-xs mt-2 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            <button type="submit" class="w-full btn-gold py-3 rounded-xl font-bold tracking-wider uppercase shadow-xl text-sm">Submit Payment Proof</button>
                        </form>
                    </div>
                @endif

                @if($booking->payments->isNotEmpty())
                    <div class="card-premium rounded-2xl p-6">
                        <h3 class="font-bold text-lg text-white mb-4">Payment History</h3>
                        <div class="space-y-4">
                            @foreach($booking->payments as $payment)
                                <div class="bg-slate-900 border border-slate-700 rounded-xl p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="font-medium text-white">IDR {{ number_format($payment->amount) }}</span>
                                        @if($payment->status === 'verified')
                                            <span class="text-xs font-bold text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded">Verified</span>
                                        @elseif($payment->status === 'rejected')
                                            <span class="text-xs font-bold text-rose-400 bg-rose-500/10 px-2 py-1 rounded">Rejected</span>
                                        @else
                                            <span class="text-xs font-bold text-gold bg-gold/10 px-2 py-1 rounded">In Review</span>
                                        @endif
                                    </div>
                                    <p class="text-slate-500 text-xs flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Submitted on {{ $payment->created_at->format('d M Y, H:i') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <div class="card-premium rounded-2xl p-6 text-center">
                    <div class="w-12 h-12 bg-slate-800 rounded-full flex items-center justify-center border border-slate-600 mx-auto mb-4 text-gold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h4 class="text-white font-medium mb-2">Need Assistance?</h4>
                    <p class="text-slate-400 text-sm mb-4">Our premium support team is available 24/7 to assist with your itinerary.</p>
                    <a href="mailto:concierge@indogate.com" class="text-gold font-medium text-sm hover:underline">Contact Concierge</a>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
        document.getElementById('file-upload').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'Click to browse or drag file here';
            document.getElementById('file-name').textContent = fileName;
            if(e.target.files[0]) {
                document.getElementById('file-name').classList.add('text-gold');
            } else {
                document.getElementById('file-name').classList.remove('text-gold');
            }
        });
    </script>
</x-customer-layout>
