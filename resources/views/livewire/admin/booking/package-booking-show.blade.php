<div wire:poll.3s="checkVoucherReady">
    <x-ui.page-header :eyebrow="__('booking.eyebrow')" :title="$booking->code">
        <x-slot name="actions">
            <x-ui.status :status="$booking->status->value">{{ __('booking.status.'.$booking->status->value) }}</x-ui.status>
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Info --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4 text-sm">
                <div class="grid grid-cols-2 gap-3">
                    <div><span class="text-neutral-500">{{ __('booking.show.lead') }}</span><div class="font-medium">{{ $booking->quotation->lead->name }}</div></div>
                    <div><span class="text-neutral-500">{{ __('booking.show.departure') }}</span><div class="font-medium">{{ $booking->departure_date->format('d M Y') }}</div></div>
                    <div><span class="text-neutral-500">{{ __('booking.show.return') }}</span><div class="font-medium">{{ $booking->return_date?->format('d M Y') ?? '—' }}</div></div>
                    <div><span class="text-neutral-500">{{ __('booking.show.total') }}</span><div class="font-mono font-medium">{{ number_format($booking->total_minor / 100, 2) }} {{ $booking->currency }}</div></div>
                </div>
            </div>

            {{-- Status transitions --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <h3 class="text-sm font-medium text-neutral-900">{{ __('booking.show.transition_title') }}</h3>
                @error('transition') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($nextStatuses as $next)
                        @if($next->value !== 'cancelled')
                            <x-ui.button type="button" variant="secondary" wire:click="transitionTo('{{ $next->value }}')" wire:confirm="{{ __('booking.show.confirm_transition', ['status' => __('booking.status.'.$next->value)]) }}">
                                → {{ __('booking.status.'.$next->value) }}
                            </x-ui.button>
                        @endif
                    @endforeach
                    @if(collect($nextStatuses)->contains(fn($s) => $s->value === 'cancelled'))
                        <x-ui.button type="button" variant="ghost" wire:click="$set('showCancelModal', true)">{{ __('booking.show.cancel') }}</x-ui.button>
                    @endif
                </div>

                @if($showCancelModal)
                    <form wire:submit="cancel" class="mt-3 rounded border border-danger/20 bg-danger/5 p-3">
                        <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('booking.show.cancel_reason') }}</label>
                        <textarea wire:model="cancel_reason" rows="2" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm"></textarea>
                        @error('cancel_reason') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                        <div class="mt-2 flex gap-2">
                            <x-ui.button type="submit" variant="primary" class="text-xs">{{ __('booking.show.confirm_cancel') }}</x-ui.button>
                            <x-ui.button type="button" variant="ghost" class="text-xs" wire:click="$set('showCancelModal', false)">{{ __('booking.show.close') }}</x-ui.button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Guests --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <h3 class="text-sm font-medium text-neutral-900">{{ __('booking.show.guests') }}</h3>
                <div class="mt-2 space-y-2">
                    @forelse($booking->guests as $guest)
                        <div class="flex items-center justify-between rounded border border-neutral-100 px-3 py-2 text-xs">
                            <span>{{ $guest->name }}{{ $guest->is_lead_guest ? ' ('.__('booking.show.lead_guest').')' : '' }} · {{ $guest->nationality ?? '—' }}</span>
                            <span class="flex items-center gap-2">
                                @if($guest->passport_file)
                                    <a href="{{ $this->passportDownloadUrl($guest) }}" class="text-blue-600 hover:underline">{{ __('booking.show.view_passport') }}</a>
                                @endif
                                <button type="button" wire:click="removeGuest({{ $guest->id }})" wire:confirm="{{ __('booking.show.confirm_remove_guest') }}" class="text-danger hover:underline">{{ __('booking.show.remove') }}</button>
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-neutral-400">{{ __('booking.show.no_guests') }}</p>
                    @endforelse
                </div>

                <form wire:submit="addGuest" class="mt-3 grid grid-cols-2 gap-2 rounded border border-neutral-100 bg-neutral-50 p-3">
                    <div class="col-span-2">
                        <input type="text" wire:model="guest_name" placeholder="{{ __('booking.show.guest_name') }}" class="w-full rounded border border-neutral-300 px-2 py-1.5 text-xs">
                        @error('guest_name') <p class="text-[10px] text-danger">{{ $message }}</p> @enderror
                    </div>
                    <input type="text" wire:model="guest_passport_number" placeholder="{{ __('booking.show.passport_number') }}" class="rounded border border-neutral-300 px-2 py-1.5 text-xs">
                    <input type="text" wire:model="guest_nationality" placeholder="{{ __('booking.show.nationality') }}" class="rounded border border-neutral-300 px-2 py-1.5 text-xs">
                    <input type="file" wire:model="guest_passport_file" class="col-span-2 text-xs">
                    @error('guest_passport_file') <p class="col-span-2 text-[10px] text-danger">{{ $message }}</p> @enderror
                    <label class="col-span-2 flex items-center gap-1.5 text-xs text-neutral-600">
                        <input type="checkbox" wire:model="guest_is_lead"> {{ __('booking.show.is_lead_guest') }}
                    </label>
                    <div class="col-span-2">
                        <x-ui.button type="submit" variant="secondary" class="text-xs">{{ __('booking.show.add_guest') }}</x-ui.button>
                    </div>
                </form>
            </div>

            {{-- Notes --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <h3 class="text-sm font-medium text-neutral-900">{{ __('booking.show.notes') }}</h3>
                <ul class="mt-2 space-y-1 text-xs text-neutral-600">
                    @forelse($booking->notes as $note)
                        <li class="border-b border-neutral-100 pb-1">{{ $note->note }} <span class="text-neutral-400">— {{ $note->user?->name ?? 'System' }} · {{ $note->created_at->diffForHumans() }}</span></li>
                    @empty
                        <li class="text-neutral-400">{{ __('booking.show.no_notes') }}</li>
                    @endforelse
                </ul>
                <form wire:submit="addNote" class="mt-2 flex gap-2">
                    <input type="text" wire:model="note_text" class="flex-1 rounded border border-neutral-300 px-2 py-1.5 text-xs">
                    <x-ui.button type="submit" variant="secondary" class="text-xs">{{ __('booking.show.add_note') }}</x-ui.button>
                </form>
                @error('note_text') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-6">
            {{-- Voucher --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <h3 class="text-sm font-medium text-neutral-900">{{ __('booking.show.voucher') }}</h3>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach(['id','en','ar'] as $locale)
                        <x-ui.button type="button" variant="secondary" class="text-xs" wire:click="exportVoucher('{{ $locale }}')" wire:loading.attr="disabled">{{ strtoupper($locale) }}</x-ui.button>
                    @endforeach
                </div>
                @if($exportPending)
                    <p class="mt-2 text-xs text-neutral-500">{{ __('booking.show.voucher_generating') }}</p>
                @endif
                @if($downloadUrl)
                    <a href="{{ $downloadUrl }}" target="_blank" class="mt-2 block text-xs text-blue-600 hover:underline">{{ __('booking.show.voucher_download') }}</a>
                @endif
            </div>

            {{-- Status history --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <h3 class="text-sm font-medium text-neutral-900">{{ __('booking.show.history') }}</h3>
                <ul class="mt-2 space-y-1 text-xs text-neutral-600">
                    @forelse($booking->statusHistories as $history)
                        <li class="border-b border-neutral-100 pb-1">
                            {{ $history->from_status?->value ?? '—' }} → {{ $history->to_status->value }}
                            @if($history->reason) — {{ $history->reason }} @endif
                            <span class="text-neutral-400">· {{ $history->user?->name ?? 'System' }} · {{ $history->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="text-neutral-400">—</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
