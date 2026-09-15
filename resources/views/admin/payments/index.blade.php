<x-admin-layout>
    <x-slot name="header">Payment Verifications</x-slot>

    <div class="mb-6">
        <p class="text-slate-500 text-sm">Review and verify all incoming payment proofs.</p>
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Payment ID</th>
                    <th class="text-left">Booking Ref</th>
                    <th class="text-left">Customer</th>
                    <th class="text-left">Amount</th>
                    <th class="text-left">Status</th>
                    <th class="text-left">Submitted</th>
                    <th class="text-right pr-5">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td><span class="font-mono font-medium text-white">#{{ $payment->id }}</span></td>
                    <td><span class="font-mono text-slate-400">{{ substr($payment->booking->booking_number ?? '', 0, 8) }}</span></td>
                    <td>{{ $payment->booking->customer->user->name ?? 'N/A' }}</td>
                    <td style="color: #D4AF37; font-weight: 600;">IDR {{ number_format($payment->amount) }}</td>
                    <td>
                        @if($payment->status === 'verified')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">Verified</span>
                        @elseif($payment->status === 'rejected')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(239,68,68,0.1); color: #f87171;">Rejected</span>
                        @else
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24;">Pending</span>
                        @endif
                    </td>
                    <td class="text-slate-400 text-xs">{{ $payment->created_at->format('d M Y, H:i') }}</td>
                    <td class="text-right pr-5">
                        <a href="{{ route('admin.payments.show', $payment) }}" class="btn-view">Review</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-16 text-center text-slate-600 text-sm">
                        <div class="flex flex-col items-center gap-2" style="color: #34d399;">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>All caught up! No payments to review.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-white/5">{{ $payments->links() }}</div>
    </div>
</x-admin-layout>
