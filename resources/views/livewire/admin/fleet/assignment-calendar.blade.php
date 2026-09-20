<div>
    <x-ui.page-header eyebrow="M8 · {{ __('fleet.fleet') }}" :title="__('fleet.assignment_calendar')" lede="Jadwal penugasan driver dan armada di cabang {{ \App\Support\Branch\CurrentBranch::model()?->name }}">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" placeholder="Cari driver, plat, booking…" class="w-48 shrink-0" />

            <div class="flex items-center gap-1 shrink-0">
                <input type="date" wire:model.live="selectedDate" class="h-9 rounded border border-neutral-300 bg-neutral-0 px-2 text-sm focus:border-red-500 focus:outline-none" />
                <button type="button" wire:click="$set('selectedDate', '{{ now()->toDateString() }}')" class="h-9 rounded border border-neutral-300 bg-neutral-100 px-2.5 text-xs font-medium text-neutral-700 hover:bg-neutral-200">
                    Hari Ini
                </button>
            </div>

            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 px-2 text-sm">
                <option value="">Semua Status</option>
                <option value="assigned">{{ __('fleet.status_assigned') }}</option>
                <option value="in_progress">{{ __('fleet.status_in_progress') }}</option>
                <option value="completed">{{ __('fleet.status_completed') }}</option>
                <option value="cancelled">{{ __('fleet.status_cancelled') }}</option>
            </select>
        </x-slot>
    </x-ui.page-header>

    <div class="mb-4 flex items-center justify-between rounded border border-neutral-200 bg-neutral-0 px-4 py-3">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-neutral-500">Tanggal Aktif:</span>
            <span class="text-sm font-bold text-neutral-900">{{ $targetDate->translatedFormat('l, d F Y') }}</span>
        </div>
        <div class="text-xs text-neutral-500">
            Total {{ $assignments->total() }} penugasan aktif pada tanggal ini
        </div>
    </div>

    @if($assignments->isEmpty())
        <x-ui.empty :title="__('fleet.assignment_calendar')" text="Tidak ada penugasan driver pada tanggal ini." />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('fleet.booking_code') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.assigned_driver') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.assigned_vehicle') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.assignment_period') }}</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th numeric>{{ __('catalog.common.actions') }}</x-ui.th>
            </x-slot>
            @foreach($assignments as $item)
                <x-ui.tr wire:key="assignment-{{ $item->id }}">
                    <x-ui.td>
                        <div>
                            <a href="{{ route('admin.package-bookings.show', $item->booking_id) }}" class="font-mono font-bold text-red-600 hover:text-red-700 hover:underline">
                                {{ $item->booking?->code }}
                            </a>
                            <div class="text-xs text-neutral-500">
                                {{ $item->booking?->quotation?->lead?->name }}
                            </div>
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="font-medium text-neutral-900">{{ $item->driver?->name }}</div>
                        <div class="text-xs text-neutral-500">
                            {{ $item->driver?->phone ?? '—' }} · {{ $item->driver?->gender === 'female' ? __('fleet.gender_female') : __('fleet.gender_male') }}
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        @if($item->vehicle)
                            <div class="font-mono font-bold text-xs text-neutral-900">{{ $item->vehicle->plate }}</div>
                            <div class="text-xs text-neutral-500">{{ $item->vehicle->type }}</div>
                        @else
                            <span class="text-neutral-400 text-xs">—</span>
                        @endif
                    </x-ui.td>
                    <x-ui.td class="text-xs">
                        <div class="font-medium text-neutral-800">
                            {{ $item->date_from->translatedFormat('d M') }} — {{ $item->date_to->translatedFormat('d M Y') }}
                        </div>
                        @if($item->notes)
                            <div class="text-neutral-500 italic max-w-xs truncate">{{ $item->notes }}</div>
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        @php
                            $statusClasses = match($item->status) {
                                'assigned' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'in_progress' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-neutral-100 text-neutral-600 border-neutral-200',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium border {{ $statusClasses }}">
                            {{ __('fleet.status_'.$item->status) }}
                        </span>
                    </x-ui.td>
                    <x-ui.td numeric>
                        <div class="inline-flex items-center gap-2">
                            <a href="{{ $this->dutyLetterUrl($item->id) }}" target="_blank" class="inline-flex items-center rounded border border-neutral-300 bg-neutral-0 px-2.5 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                                <svg class="h-3.5 w-3.5 me-1 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                {{ __('fleet.duty_letter') }}
                            </a>
                            @if($item->status !== 'cancelled')
                                <button type="button" wire:click="openCancelModal({{ $item->id }})" class="text-xs font-medium text-red-600 hover:text-red-800">
                                    {{ __('catalog.common.cancel') }}
                                </button>
                            @endif
                        </div>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            {{ $assignments->links() }}
        </div>
    @endif

    {{-- Cancel Assignment Modal --}}
    @if($showCancelModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-md rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ __('fleet.cancel_assignment') }}
                </h3>
                <p class="mt-1 text-xs text-neutral-600">
                    Masukkan alasan pembatalan penugasan driver. Alasan ini akan tercatat dalam audit log.
                </p>

                <form wire:submit="cancelAssignment" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.cancel_reason') }} <span class="text-red-600">*</span>
                        </label>
                        <textarea wire:model="cancel_reason" rows="3" required placeholder="Contoh: Tamu membatalkan permintaan driver pribadi" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                        @error('cancel_reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showCancelModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            {{ __('catalog.common.cancel') }}
                        </button>
                        <button type="submit" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                            {{ __('fleet.cancel_assignment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
