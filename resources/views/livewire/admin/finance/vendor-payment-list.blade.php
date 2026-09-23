<div>
    <x-ui.page-header :eyebrow="'M9 · '.__('finance.finance')" :title="__('finance.vendor_payments')" lede="Pencatatan pengeluaran dan pembayaran modal ke mitra vendor cabang {{ \App\Support\Branch\CurrentBranch::model()?->name }}">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" placeholder="Cari vendor, booking, catatan…" class="w-56 shrink-0" />

            <select wire:model.live="partnerFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 px-2 text-xs">
                <option value="">Semua Mitra Vendor</option>
                @foreach($partners as $partner)
                    <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                @endforeach
            </select>

            <button type="button" wire:click="openCreateModal" class="rounded bg-red-600 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                + {{ __('finance.record_vendor_payment') }}
            </button>
        </x-slot>
    </x-ui.page-header>

    @if($actionSuccess)
        <div class="mb-4 flex items-center justify-between rounded border border-emerald-300 bg-emerald-50 p-4 text-xs font-medium text-emerald-800">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ $actionSuccess }}</span>
            </div>
            <button type="button" wire:click="$set('actionSuccess', null)" class="text-emerald-600 hover:text-emerald-900">&times;</button>
        </div>
    @endif

    @if($vendorPayments->isEmpty())
        <x-ui.empty :title="__('finance.vendor_payments')" text="Belum ada catatan pembayaran vendor yang tersimpan." />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('finance.paid_at') }}</x-ui.th>
                <x-ui.th>{{ __('finance.partner') }}</x-ui.th>
                <x-ui.th>{{ __('finance.booking_code') }}</x-ui.th>
                <x-ui.th>{{ __('finance.description') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.amount') }}</x-ui.th>
                <x-ui.th numeric>Aksi</x-ui.th>
            </x-slot>
            @foreach($vendorPayments as $vp)
                <x-ui.tr wire:key="vp-{{ $vp->id }}">
                    <x-ui.td class="text-xs">
                        {{ $vp->paid_at->translatedFormat('d M Y') }}
                    </x-ui.td>
                    <x-ui.td>
                        <div class="font-medium text-neutral-900">{{ $vp->partner?->name }}</div>
                        <div class="text-xs text-neutral-500">{{ strtoupper($vp->partner?->type?->value ?? '') }}</div>
                    </x-ui.td>
                    <x-ui.td>
                        @if($vp->booking)
                            <a href="{{ route('admin.package-bookings.show', $vp->booking_id) }}" class="font-mono font-bold text-red-600 hover:text-red-700 hover:underline">
                                {{ $vp->booking->code }}
                            </a>
                        @else
                            <span class="text-neutral-400 text-xs">— (Biaya Operasional Umum)</span>
                        @endif
                    </x-ui.td>
                    <x-ui.td class="text-xs text-neutral-600">
                        {{ $vp->description ?? '—' }}
                    </x-ui.td>
                    <x-ui.td numeric>
                        <div class="font-bold text-neutral-900">
                            {{ $vp->currency }} {{ number_format($vp->amount_minor, 0, ',', '.') }}
                        </div>
                        @if($vp->currency !== 'IDR')
                            <div class="text-[10px] text-neutral-500">
                                ≈ IDR {{ number_format($vp->idr_equivalent_minor, 0, ',', '.') }}
                            </div>
                        @endif
                    </x-ui.td>
                    <x-ui.td numeric>
                        @can('delete', $vp)
                            <button type="button" wire:click="deleteVendorPayment({{ $vp->id }})" wire:loading.attr="disabled" class="text-xs font-medium text-red-600 hover:text-red-800 disabled:opacity-50">
                                Hapus
                            </button>
                        @endcan
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            {{ $vendorPayments->links() }}
        </div>
    @endif

    {{-- Create Vendor Payment Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-lg rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ __('finance.record_vendor_payment') }}
                </h3>

                <form wire:submit="save" class="mt-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.partner') }} <span class="text-red-600">*</span>
                        </label>
                        <select wire:model="partner_id" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                            <option value="">-- Pilih Mitra Vendor --</option>
                            @foreach($partners as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ strtoupper($p->type?->value ?? '') }})</option>
                            @endforeach
                        </select>
                        @error('partner_id') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            Terkait Pemesanan (Opsional)
                        </label>
                        <select wire:model="booking_id" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                            <option value="">-- Tidak Terikat / Umum --</option>
                            @foreach($recentBookings as $b)
                                <option value="{{ $b->id }}">{{ $b->code }} — {{ $b->quotation?->lead?->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.amount') }} (Minor) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" wire:model="amount_minor" required min="1" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                            @error('amount_minor') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.currency') }} <span class="text-red-600">*</span>
                            </label>
                            <select wire:model.live="currency" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                                <option value="IDR">IDR (Rupiah)</option>
                                <option value="SAR">SAR (Riyal)</option>
                                <option value="USD">USD (Dollar)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.fx_rate') }}
                            </label>
                            <input type="number" step="0.00000001" wire:model="fx_rate" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                            @error('fx_rate') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.paid_at') }} <span class="text-red-600">*</span>
                            </label>
                            <input type="date" wire:model="paid_at" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                            @error('paid_at') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.description') }}
                        </label>
                        <input type="text" wire:model="description" placeholder="Contoh: Pembayaran deposit kamar hotel 3 malam" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                        @error('description') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.payment_proof') }} (PDF/Gambar, maks 5MB)
                        </label>
                        <input type="file" wire:model="proofFile" class="mt-1 block w-full text-xs text-neutral-600" />
                        @error('proofFile') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            {{ __('catalog.common.cancel') }}
                        </button>
                        <button type="submit" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                            {{ __('finance.record_vendor_payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
