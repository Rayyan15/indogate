<x-customer-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Welcome Banner -->
        <div class="card-premium rounded-2xl p-8 mb-8 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="absolute top-0 right-0 w-64 h-64 bg-gold opacity-10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
            
            <div class="relative z-10 flex items-center gap-6">
                <div class="w-20 h-20 rounded-full bg-slate-800 border-2 border-gold flex items-center justify-center text-3xl font-bold text-gold shadow-lg shadow-gold/20">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-3xl font-light text-white tracking-wide">
                        Welcome back, <span class="text-gold font-bold">{{ Auth::user()->name }}</span>
                    </h2>
                    <p class="text-slate-400 mt-1">Manage your luxury itineraries and reservations</p>
                </div>
            </div>
            
            <div class="relative z-10 grid grid-cols-2 gap-4 w-full md:w-auto">
                <div class="bg-slate-800/80 rounded-xl p-4 text-center border border-slate-700 min-w-[120px]">
                    <p class="text-3xl font-bold text-white mb-1">{{ $bookings->total() }}</p>
                    <p class="text-xs text-slate-500 uppercase tracking-widest">Total Bookings</p>
                </div>
                <div class="bg-slate-800/80 rounded-xl p-4 text-center border border-slate-700 min-w-[120px]">
                    <p class="text-3xl font-bold text-gold mb-1">
                        {{ collect($bookings->items())->where('status', 'pending_payment')->count() }}
                    </p>
                    <p class="text-xs text-slate-500 uppercase tracking-widest">Pending</p>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="mb-6 flex justify-between items-end">
            <h3 class="text-xl font-bold text-white">Recent Itineraries</h3>
            @if($bookings->isNotEmpty())
                <a href="{{ route('search.index') }}" class="text-gold hover:text-white transition-colors text-sm font-medium flex items-center gap-1">
                    New Booking
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            @endif
        </div>
        
        @if($bookings->isEmpty())
            <div class="flex flex-col items-center justify-center py-20 bg-slate-800/30 rounded-2xl border border-slate-700/50">
                <div class="w-24 h-24 bg-slate-800 rounded-full flex items-center justify-center border-2 border-slate-700 mb-6 text-slate-500">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <p class="text-slate-400 text-lg mb-8">You have no booking history.</p>
                <a href="{{ route('search.index') }}" class="btn-gold px-8 py-3 rounded-full font-semibold tracking-wide">Start Planning</a>
            </div>
        @else
            <div class="card-premium rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-900/50 border-b border-slate-700">
                                <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-widest">Booking Ref</th>
                                <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-widest">Date</th>
                                <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-widest">Total Amount</th>
                                <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="py-4 px-6 text-xs font-semibold text-slate-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            @foreach($bookings as $booking)
                                <tr class="hover:bg-slate-800/50 transition-colors group">
                                    <td class="py-5 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-slate-800 border border-slate-600 flex items-center justify-center text-slate-400 group-hover:text-gold group-hover:border-gold/50 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                            </div>
                                            <span class="font-mono font-medium text-white">{{ substr($booking->booking_number, 0, 8) }}</span>
                                        </div>
                                    </td>
                                    <td class="py-5 px-6">
                                        <span class="text-slate-300">{{ $booking->created_at->format('d M Y') }}</span>
                                    </td>
                                    <td class="py-5 px-6">
                                        <span class="font-medium text-white">IDR {{ number_format($booking->total_amount) }}</span>
                                    </td>
                                    <td class="py-5 px-6">
                                        @if($booking->status === 'confirmed')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-[0_0_10px_rgba(52,211,153,0.1)]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                Confirmed
                                            </span>
                                        @elseif($booking->status === 'pending_payment')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gold/10 text-gold border border-gold/20 shadow-[0_0_10px_rgba(212,175,55,0.1)]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gold"></span>
                                                Pending Payment
                                            </span>
                                        @elseif(str_contains($booking->status, 'rejected'))
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                                {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-5 px-6 text-right">
                                        <a href="{{ route('customer.bookings.show', $booking) }}" class="inline-flex items-center justify-center p-2 rounded-lg bg-slate-800 text-slate-300 hover:bg-gold hover:text-slate-900 transition-all shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-6">
                {{ $bookings->links() }}
            </div>
        @endif
        
    </div>
</x-customer-layout>
