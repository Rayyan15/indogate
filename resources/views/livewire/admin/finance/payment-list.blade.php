<div>
    <x-ui.page-header :eyebrow="'M9 · '.__('finance.finance')" :title="__('finance.payments')" lede="Kelola verifikasi pembayaran pemesanan di cabang {{ \App\Support\Branch\CurrentBranch::model()?->name }}">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode booking, tamu, kanal…" class="w-56 shrink-0" />

            <select wire:model.live="typeFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                <option value="">Semua Jenis</option>
                <option value="down_payment">{{ __('finance.type_down_payment') }}</option>
                <option value="full_payment">{{ __('finance.type_full_payment') }}</option>
                <option value="installment">{{ __('finance.type_installment') }}</option>
            </select>

            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                <option value="">Semua Status</option>
                <option value="pending">{{ __('finance.status_pending') }}</option>
                <option value="verified">{{ __('finance.status_verified') }}</option>
                <option value="rejected">{{ __('finance.status_rejected') }}</option>
            </select>
        </x-slot>
    </x-ui.page-header>

    @if($actionError)
        <div class="mb-4 flex items-center justify-between rounded border border-red-300 bg-red-50 p-4 text-xs font-medium text-red-800">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ $actionError }}</span>
            </div>
            <button type="button" wire:click="$set('actionError', null)" class="text-red-600 hover:text-red-900">&times;</button>
        </div>
    @endif

    @if($actionSuccess)
        <div class="mb-4 flex items-center justify-between rounded border border-emerald-300 bg-emerald-50 p-4 text-xs font-medium text-emerald-800">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ $actionSuccess }}</span>
            </div>
            <button type="button" wire:click="$set('actionSuccess', null)" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    @if($payments->isEmpty())
        <x-ui.empty :title="__('finance.payments')" text="Belum ada catatan pembayaran yang cocok dengan filter." />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('finance.booking_code') }}</x-ui.th>
                <x-ui.th>{{ __('finance.guest_lead') }}</x-ui.th>
                <x-ui.th>{{ __('finance.payment_type') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.amount') }}</x-ui.th>
                <x-ui.th>{{ __('finance.payment_proof') }}</x-ui.th>
                <x-ui.th>{{ __('finance.status') }}</x-ui.th>
                <x-ui.th>{{ __('catalog.common.actions') }}</x-ui.th>
            </x-slot>
            @foreach($payments as $item)
                <x-ui.tr wire:key="payment-{{ $item->id }}">
                    <x-ui.td>
                        <a href="{{ route('admin.package-bookings.show', $item->booking_id) }}" class="font-mono font-bold text-red-600 hover:text-red-700 hover:underline">
                            {{ $item->booking?->code }}
                        </a>
                        <div class="text-[10px] text-neutral-400">
                            {{ $item->created_at->translatedFormat('d M Y H:i') }}
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="font-medium text-neutral-900">{{ $item->booking?->quotation?->lead?->name ?? '—' }}</div>
                        <div class="text-xs text-neutral-500">{{ strtoupper($item->channel) }}</div>
                        <div class="mt-1 flex items-center justify-center gap-1.5">
                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-medium {{ $item->source === 'gateway' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-neutral-200 bg-neutral-0 text-neutral-600' }}">{{ __('payment.admin.source_'.($item->source ?? 'manual')) }}</span>
                            @if($item->provider_reference)
                                <span class="font-mono text-[10px] text-neutral-400" dir="ltr">{{ $item->provider_reference }}</span>
                            @endif
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium bg-neutral-100 text-neutral-700">
                            {{ __('finance.type_'.$item->type) }}
                        </span>
                    </x-ui.td>
                    <x-ui.td numeric>
                        <div class="font-bold text-neutral-900">
                            {{ $item->currency }} {{ number_format($item->amount_minor, 0, ',', '.') }}
                        </div>
                        @if($item->currency !== 'IDR')
                            <div class="text-[10px] text-neutral-500">
                                ≈ IDR {{ number_format($item->idr_equivalent_minor, 0, ',', '.') }}
                            </div>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        @if($item->proof_file)
                            <a href="{{ $this->proofUrl($item) }}" target="_blank" class="inline-flex items-center text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                <svg class="h-3.5 w-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                                {{ __('finance.view_proof') }}
                            </a>
                        @else
                            <span class="text-xs text-neutral-400">—</span>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        @php
                            $statusBadge = match($item->status) {
                                'verified' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-amber-50 text-amber-700 border-amber-200',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border {{ $statusBadge }}">
                            {{ __('finance.status_'.$item->status) }}
                        </span>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-center gap-1">
                            @if($item->status === 'verified')
                                <x-ui.icon-button
                                    :href="$this->receiptUrl($item)"
                                    target="_blank"
                                    :title="__('finance.receipt')"
                                    :aria-label="__('finance.receipt')"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </x-ui.icon-button>
                            @endif

                            @can('payment.verify')
                                @if($item->status === 'pending')
                                    <x-ui.icon-button
                                        variant="ghost"
                                        type="button"
                                        wire:click="verifyPayment({{ $item->id }})"
                                        class="text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50"
                                        title="Verifikasi"
                                        aria-label="Verifikasi"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </x-ui.icon-button>
                                    <x-ui.icon-button
                                        variant="ghost"
                                        type="button"
                                        wire:click="openRejectModal({{ $item->id }})"
                                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                        title="Tolak"
                                        aria-label="Tolak"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </x-ui.icon-button>
                                @endif
                            @endcan
                        </div>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            {{ $payments->links() }}
        </div>
    @endif

    {{-- Rejection Modal --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-md rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ __('finance.reject_payment') }}
                </h3>
                <p class="mt-1 text-xs text-neutral-600">
                    Masukkan alasan penolakan pembayaran. Bukti dan catatan ini akan disimpan dalam riwayat audit.
                </p>

                <form wire:submit="rejectPayment" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.rejection_reason') }} <span class="text-red-600">*</span>
                        </label>
                        <textarea wire:model="rejectionReason" rows="3" required placeholder="Contoh: Bukti transfer tidak terbaca / dana belum masuk rekening" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                        @error('rejectionReason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showRejectModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            {{ __('catalog.common.cancel') }}
                        </button>
                        <button type="submit" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                            {{ __('finance.reject_payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
