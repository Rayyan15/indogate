<x-admin-layout>
    <x-slot name="header">Dashboard</x-slot>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        
        <div class="admin-card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full opacity-5" style="background: #D4AF37;"></div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3">Total Bookings</p>
            <p class="text-4xl font-bold text-white mb-1">{{ $stats['total_bookings'] }}</p>
            <p class="text-xs text-slate-600">All time</p>
        </div>
        
        <div class="admin-card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full opacity-5" style="background: #f59e0b;"></div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3">Pending Payments</p>
            <p class="text-4xl font-bold mb-1" style="color: #fbbf24;">{{ $stats['pending_payments'] }}</p>
            <p class="text-xs text-slate-600">Awaiting verification</p>
        </div>
        
        <div class="admin-card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full opacity-5" style="background: #10b981;"></div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3">Confirmed</p>
            <p class="text-4xl font-bold mb-1" style="color: #34d399;">{{ $stats['confirmed_bookings'] }}</p>
            <p class="text-xs text-slate-600">Bookings confirmed</p>
        </div>
        
        <div class="admin-card p-5 relative overflow-hidden">
            <div class="absolute -right-4 -top-4 w-20 h-20 rounded-full opacity-5" style="background: #6366f1;"></div>
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-3">Customers</p>
            <p class="text-4xl font-bold text-white mb-1">{{ $stats['total_customers'] }}</p>
            <p class="text-xs text-slate-600">Registered users</p>
        </div>
    </div>

    <!-- Inventory Summary -->
    <div class="grid grid-cols-3 gap-5 mb-8">
        <div class="admin-card p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(212,175,55,0.1);">
                <svg class="w-6 h-6" style="color: #D4AF37;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $stats['total_hotels'] }}</p>
                <p class="text-xs text-slate-500">Hotels Listed</p>
            </div>
        </div>
        <div class="admin-card p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(99,102,241,0.1);">
                <svg class="w-6 h-6" style="color: #a5b4fc;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $stats['total_flights'] }}</p>
                <p class="text-xs text-slate-500">Flight Routes</p>
            </div>
        </div>
        <div class="admin-card p-4 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0" style="background: rgba(16,185,129,0.1);">
                <svg class="w-6 h-6" style="color: #34d399;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $stats['total_drivers'] }}</p>
                <p class="text-xs text-slate-500">Active Drivers</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Recent Bookings -->
        <div class="admin-card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                <h3 class="text-sm font-semibold text-white">Recent Bookings</h3>
                <a href="{{ route('admin.bookings.index') }}" class="text-xs font-medium" style="color: #D4AF37;">View all →</a>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($recent_bookings as $booking)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-white font-mono">{{ substr($booking->booking_number, 0, 8) }}</p>
                        <p class="text-xs text-slate-500">{{ $booking->customer->user->name ?? 'N/A' }} · {{ $booking->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold" style="color: #D4AF37;">IDR {{ number_format($booking->total_amount) }}</span>
                        @if($booking->status === 'confirmed')
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">Confirmed</span>
                        @elseif($booking->status === 'pending_payment')
                            <span class="text-xs px-2 py-0.5 rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24;">Pending</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-slate-400">{{ ucfirst($booking->status) }}</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-slate-600">No bookings yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="admin-card">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/5">
                <h3 class="text-sm font-semibold text-white">Pending Verifications</h3>
                <a href="{{ route('admin.payments.index') }}" class="text-xs font-medium" style="color: #D4AF37;">View all →</a>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($recent_payments as $payment)
                <div class="px-5 py-3 flex justify-between items-center">
                    <div>
                        <p class="text-sm font-medium text-white">{{ $payment->booking->customer->user->name ?? 'N/A' }}</p>
                        <p class="text-xs text-slate-500">{{ $payment->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold" style="color: #fbbf24;">IDR {{ number_format($payment->amount) }}</span>
                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn-view">Review</a>
                    </div>
                </div>
                @empty
                <div class="px-5 py-8 text-center text-sm text-slate-600">
                    <div style="color: #34d399;" class="font-medium">✓ All clear!</div>
                    <p class="mt-1">No pending payments to verify.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

</x-admin-layout>
