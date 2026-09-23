<div>
    <x-ui.page-header :eyebrow="'M9 · '.__('finance.finance')" :title="__('finance.receivables')" lede="Pemantauan sisa tagihan pemesanan aktif di cabang {{ \App\Support\Branch\CurrentBranch::model()?->name }}">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" placeholder="Cari kode booking, nama tamu…" class="w-56 shrink-0" />

            <div class="flex items-center gap-1 rounded border border-neutral-300 bg-neutral-100 p-1 text-xs font-medium">
                <button type="button" wire:click="$set('statusFilter', 'unpaid')" class="rounded px-2.5 py-1 {{ $statusFilter === 'unpaid' ? 'bg-neutral-0 font-bold text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900' }}">
                    {{ __('finance.unpaid') }}
                </button>
                <button type="button" wire:click="$set('statusFilter', 'overdue')" class="rounded px-2.5 py-1 {{ $statusFilter === 'overdue' ? 'bg-neutral-0 font-bold text-red-600 shadow-sm' : 'text-neutral-600 hover:text-red-600' }}">
                    Jatuh Tempo
                </button>
                <button type="button" wire:click="$set('statusFilter', 'settled')" class="rounded px-2.5 py-1 {{ $statusFilter === 'settled' ? 'bg-neutral-0 font-bold text-emerald-700 shadow-sm' : 'text-neutral-600 hover:text-neutral-900' }}">
                    {{ __('finance.settled') }}
                </button>
                <button type="button" wire:click="$set('statusFilter', 'all')" class="rounded px-2.5 py-1 {{ $statusFilter === 'all' ? 'bg-neutral-0 font-bold text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Semua
                </button>
            </div>
        </x-slot>
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded border border-neutral-200 bg-neutral-0 p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Pemesanan Belum Lunas</div>
            <div class="mt-1 text-2xl font-bold text-neutral-900">{{ $totalReceivableCount }} Pemesanan</div>
        </div>
        <div class="rounded border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-wider text-amber-700">Total Nominal Piutang Cabang</div>
            <div class="mt-1 text-2xl font-bold text-amber-900">≈ {{ number_format($totalReceivableMinor, 0, ',', '.') }} Minor</div>
        </div>
    </div>

    @if($bookings->isEmpty())
        <x-ui.empty :title="__('finance.receivables')" text="Tidak ada data piutang pemesanan untuk filter ini." />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('finance.booking_code') }}</x-ui.th>
                <x-ui.th>{{ __('finance.guest_lead') }}</x-ui.th>
                <x-ui.th>{{ __('finance.departure_date') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.total_billed') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.total_paid') }}</x-ui.th>
                <x-ui.th numeric>{{ __('finance.remaining_balance') }}</x-ui.th>
                <x-ui.th>{{ __('finance.status') }}</x-ui.th>
                <x-ui.th numeric>{{ __('catalog.common.actions') }}</x-ui.th>
            </x-slot>
            @foreach($bookings as $booking)
                <x-ui.tr wire:key="ar-{{ $booking->id }}">
                    <x-ui.td>
                        <a href="{{ route('admin.package-bookings.show', $booking->id) }}" class="font-mono font-bold text-red-600 hover:text-red-700 hover:underline">
                            {{ $booking->code }}
                        </a>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="font-medium text-neutral-900">{{ $booking->quotation?->lead?->name ?? '—' }}</div>
                        <div class="text-xs text-neutral-500">{{ $booking->quotation?->lead?->country ?? '—' }}</div>
                    </x-ui.td>
                    <x-ui.td class="text-xs">
                        <div class="{{ $booking->departure_date->isPast() && $booking->remainingBalanceMinor() > 0 ? 'font-bold text-red-600' : 'text-neutral-800' }}">
                            {{ $booking->departure_date->translatedFormat('d M Y') }}
                        </div>
                        @if($booking->departure_date->isPast() && $booking->remainingBalanceMinor() > 0)
                            <div class="text-[10px] font-semibold uppercase text-red-500">Lewat Keberangkatan</div>
                        @endif
                    </x-ui.td>
                    <x-ui.td numeric class="font-medium text-neutral-900">
                        {{ $booking->currency }} {{ number_format($booking->total_minor, 0, ',', '.') }}
                    </x-ui.td>
                    <x-ui.td numeric class="font-medium text-emerald-700">
                        {{ $booking->currency }} {{ number_format($booking->totalPaidMinor(), 0, ',', '.') }}
                    </x-ui.td>
                    <x-ui.td numeric>
                        <span class="font-bold {{ $booking->remainingBalanceMinor() > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ $booking->currency }} {{ number_format($booking->remainingBalanceMinor(), 0, ',', '.') }}
                        </span>
                    </x-ui.td>
                    <x-ui.td>
                        @php
                            $statusBadge = match($booking->status->value) {
                                'paid', 'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'partially_paid' => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-rose-50 text-rose-700 border-rose-200',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border {{ $statusBadge }}">
                            {{ strtoupper($booking->status->value) }}
                        </span>
                    </x-ui.td>
                    <x-ui.td numeric>
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ $this->invoiceUrl($booking) }}" target="_blank" class="inline-flex items-center rounded border border-neutral-300 bg-neutral-0 px-2.5 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                                {{ __('finance.invoice') }}
                            </a>
                            <a href="{{ route('admin.package-bookings.show', $booking->id) }}" class="rounded bg-neutral-100 px-2.5 py-1 text-xs font-medium text-neutral-800 hover:bg-neutral-200">
                                Buka
                            </a>
                        </div>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
