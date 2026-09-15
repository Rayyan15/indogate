<x-admin-layout>
    <x-slot name="header">Bookings</x-slot>

    <div class="mb-6">
        <p class="text-slate-500 text-sm">View and manage all customer bookings.</p>
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Booking Ref</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Total</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Date</th>
                    <th class="text-right pr-5">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookings as $booking)
                <tr>
                    <td><span class="font-mono font-medium text-white">{{ substr($booking->booking_number, 0, 10) }}</span></td>
                    <td>{{ $booking->customer->user->name ?? ($booking->customer->full_name ?? 'N/A') }}</td>
                    <td style="color: #D4AF37; font-weight: 600;">IDR {{ number_format($booking->total_amount) }}</td>
                    <td>
                        @if($booking->status === 'confirmed')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">Confirmed</span>
                        @elseif($booking->status === 'pending_payment')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24;">Pending Payment</span>
                        @elseif(str_contains($booking->status, 'rejected'))
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(239,68,68,0.1); color: #f87171;">Rejected</span>
                        @else
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-800 text-slate-400">{{ ucwords(str_replace('_',' ',$booking->status)) }}</span>
                        @endif
                    </td>
                    <td class="text-slate-400">{{ $booking->created_at->format('d M Y') }}</td>
                    <td class="text-right pr-5">
                        <a href="{{ route('admin.bookings.show', $booking) }}" class="btn-view">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-16 text-center text-slate-600 text-sm">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            <p>No bookings yet.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-white/5">{{ $bookings->links() }}</div>
    </div>
</x-admin-layout>
