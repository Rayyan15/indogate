<x-admin-layout>
    <x-slot name="header">Review Payment</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.payments.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Payments
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Payment Details -->
        <div class="admin-card p-6">
            <h3 class="text-base font-bold text-white mb-1">Payment #{{ $payment->id }}</h3>
            <p class="text-sm text-slate-500 mb-6">Submitted {{ $payment->created_at->diffForHumans() }}</p>
            
            <div class="space-y-4">
                <div class="flex justify-between py-3 border-b border-white/5">
                    <span class="text-sm text-slate-400">Customer</span>
                    <span class="text-sm font-medium text-white">{{ $payment->booking->customer->user->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-white/5">
                    <span class="text-sm text-slate-400">Booking Ref</span>
                    <span class="text-sm font-mono text-slate-300">{{ substr($payment->booking->booking_number ?? '', 0, 10) }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-white/5">
                    <span class="text-sm text-slate-400">Amount</span>
                    <span class="text-lg font-bold" style="color: #D4AF37;">IDR {{ number_format($payment->amount) }}</span>
                </div>
                <div class="flex justify-between py-3 border-b border-white/5">
                    <span class="text-sm text-slate-400">Status</span>
                    <span>
                        @if($payment->status === 'verified')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">✓ Verified</span>
                        @elseif($payment->status === 'rejected')
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(239,68,68,0.1); color: #f87171;">✗ Rejected</span>
                        @else
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(245,158,11,0.1); color: #fbbf24;">● Pending Review</span>
                        @endif
                    </span>
                </div>
                @if($payment->verified_at)
                <div class="flex justify-between py-3">
                    <span class="text-sm text-slate-400">Processed At</span>
                    <span class="text-sm text-slate-300">{{ $payment->verified_at->format('d M Y, H:i') }}</span>
                </div>
                @endif
            </div>

            @if($payment->status === 'pending')
            <div class="mt-8 space-y-3">
                <form action="{{ route('admin.payments.verify', $payment) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl font-bold text-sm tracking-wide flex items-center justify-center gap-2"
                        style="background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3);"
                        onmouseover="this.style.background='rgba(16,185,129,0.25)'" onmouseout="this.style.background='rgba(16,185,129,0.15)'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Verify & Confirm Payment
                    </button>
                </form>
                <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" onsubmit="return confirm('Reject this payment? This cannot be undone.')">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl font-bold text-sm tracking-wide flex items-center justify-center gap-2"
                        style="background: rgba(239,68,68,0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2);"
                        onmouseover="this.style.background='rgba(239,68,68,0.2)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Reject Payment
                    </button>
                </form>
            </div>
            @endif
        </div>

        <!-- Proof of Payment -->
        <div class="admin-card p-6">
            <h3 class="text-base font-bold text-white mb-6">Payment Proof</h3>
            @forelse($payment->proofs as $proof)
            <div class="admin-card p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs text-slate-500">Uploaded {{ $proof->created_at->diffForHumans() }}</p>
                    <a href="{{ Storage::url($proof->file_path) }}" target="_blank" class="btn-view text-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        Open Full
                    </a>
                </div>
                <div class="rounded-xl overflow-hidden border border-white/10">
                    <img src="{{ Storage::url($proof->file_path) }}" alt="Payment Proof" class="w-full object-contain max-h-96">
                </div>
            </div>
            @empty
            <div class="flex flex-col items-center justify-center py-12 text-slate-600">
                <svg class="w-10 h-10 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <p class="text-sm">No proof uploaded yet.</p>
            </div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
