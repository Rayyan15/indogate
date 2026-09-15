<x-admin-layout>
    <x-slot name="header">Booking Details</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.bookings.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Bookings
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Booking Info + Items -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest mb-1">Booking Reference</p>
                        <h3 class="text-2xl font-bold text-white font-mono">{{ strtoupper(substr($booking->booking_number, 0, 10)) }}</h3>
                    </div>
                    <div>
                        @if($booking->status === 'confirmed')
                            <span class="text-sm font-bold px-3 py-1.5 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">✓ Confirmed</span>
                        @elseif($booking->status === 'pending_payment')
                            <span class="text-sm font-bold px-3 py-1.5 rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24;">● Pending Payment</span>
                        @else
                            <span class="text-sm font-bold px-3 py-1.5 rounded-full bg-slate-800 text-slate-400">{{ ucwords(str_replace('_',' ',$booking->status)) }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Customer</p>
                        <p class="text-white font-medium">{{ $booking->customer->user->name ?? ($booking->customer->full_name ?? 'N/A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Total Amount</p>
                        <p class="font-bold text-xl" style="color: #D4AF37;">IDR {{ number_format($booking->total_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Booking Date</p>
                        <p class="text-white font-medium">{{ $booking->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1 uppercase tracking-wider">Assigned Driver</p>
                        <p class="text-white font-medium">{{ $booking->driver?->full_name ?? 'Not assigned yet' }}</p>
                    </div>
                </div>
            </div>

            <!-- Booking Items -->
            <div class="admin-card p-6">
                <h4 class="text-sm font-bold text-white mb-4">Services Booked</h4>
                <div class="space-y-3">
                    @forelse($booking->items as $item)
                    <div class="flex justify-between items-center py-3 border-b border-white/5 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(212,175,55,0.1);">
                                <span style="color: #D4AF37; font-size: 0.75rem; font-weight: 700;">{{ strtoupper(substr(class_basename($item->bookable_type), 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-white">{{ class_basename($item->bookable_type) }} #{{ $item->bookable_id }}</p>
                                <p class="text-xs text-slate-500">Qty: {{ $item->quantity }} × IDR {{ number_format($item->unit_price) }}</p>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-white">IDR {{ number_format($item->subtotal) }}</span>
                    </div>
                    @empty
                    <p class="text-sm text-slate-500">No items found.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar: Assign Driver + Payments -->
        <div class="space-y-6">
            <div class="admin-card p-6">
                <h4 class="text-sm font-bold text-white mb-4">Assign Driver</h4>
                <form action="{{ route('admin.bookings.assign-driver', $booking) }}" method="POST" class="space-y-4">
                    @csrf
                    <select name="driver_id" class="admin-input w-full" style="background: rgba(255,255,255,0.04);">
                        <option value="">— Select Driver —</option>
                        @foreach($drivers as $driver)
                        <option value="{{ $driver->id }}" {{ $booking->driver_id == $driver->id ? 'selected' : '' }}>
                            {{ $driver->full_name }} ({{ ucfirst($driver->gender) }})
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-primary w-full justify-center">Assign Driver</button>
                </form>
            </div>

            <div class="admin-card p-6">
                <h4 class="text-sm font-bold text-white mb-4">Payment History</h4>
                @forelse($booking->payments as $payment)
                <div class="py-3 border-b border-white/5 last:border-0">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-medium text-white">IDR {{ number_format($payment->amount) }}</span>
                        @if($payment->status === 'verified')
                            <span class="text-xs font-semibold" style="color: #34d399;">✓ Verified</span>
                        @elseif($payment->status === 'rejected')
                            <span class="text-xs font-semibold" style="color: #f87171;">✗ Rejected</span>
                        @else
                            <a href="{{ route('admin.payments.show', $payment) }}" class="btn-view text-xs">Review</a>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1">{{ $payment->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <p class="text-sm text-slate-600">No payments yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
