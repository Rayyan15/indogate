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
                        <div wire:key="guest-{{ $guest->id }}" class="flex items-center justify-between rounded border border-neutral-100 px-3 py-2 text-xs">
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
                    <div>
                        <input type="text" wire:model="guest_passport_number" placeholder="{{ __('booking.show.passport_number') }}" class="w-full rounded border border-neutral-300 px-2 py-1.5 text-xs">
                        @error('guest_passport_number') <p class="text-[10px] text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <input type="text" wire:model="guest_nationality" placeholder="{{ __('booking.show.nationality') }}" class="w-full rounded border border-neutral-300 px-2 py-1.5 text-xs">
                        @error('guest_nationality') <p class="text-[10px] text-danger">{{ $message }}</p> @enderror
                    </div>
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
                        <li wire:key="note-{{ $note->id }}" class="border-b border-neutral-100 pb-1">{{ $note->note }} <span class="text-neutral-400">— {{ $note->user?->name ?? 'System' }} · {{ $note->created_at->diffForHumans() }}</span></li>
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

            {{-- Driver & Armada / Fleet Assignment --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100 pb-3">
                    <div>
                        <h3 class="text-sm font-medium text-neutral-900">{{ __('fleet.assignments') }}</h3>
                        <p class="text-xs text-neutral-500">Penugasan driver dan armada untuk pemesanan ini</p>
                    </div>
                    {{-- Gender Preference Switcher --}}
                    <div class="flex items-center gap-1">
                        <span class="text-xs text-neutral-500 font-medium me-1">{{ __('fleet.gender_preference') }}:</span>
                        <div class="inline-flex rounded border border-neutral-200 p-0.5 text-xs">
                            <button type="button" wire:click="updateGenderPreference('female')" class="rounded px-2 py-1 font-medium transition {{ $driver_gender_preference === 'female' ? 'bg-pink-100 text-pink-700 font-semibold' : 'text-neutral-600 hover:bg-neutral-100' }}">
                                {{ __('fleet.pref_female') }}
                            </button>
                            <button type="button" wire:click="updateGenderPreference('male')" class="rounded px-2 py-1 font-medium transition {{ $driver_gender_preference === 'male' ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-neutral-600 hover:bg-neutral-100' }}">
                                {{ __('fleet.pref_male') }}
                            </button>
                            <button type="button" wire:click="updateGenderPreference('')" class="rounded px-2 py-1 font-medium transition {{ empty($driver_gender_preference) ? 'bg-neutral-200 text-neutral-800 font-semibold' : 'text-neutral-600 hover:bg-neutral-100' }}">
                                {{ __('fleet.pref_any') }}
                            </button>
                        </div>
                    </div>
                </div>

                @if($booking->activeAssignment)
                    {{-- Current active assignment --}}
                    <div class="mt-4 rounded border border-neutral-200 bg-neutral-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-bold text-neutral-900">{{ $booking->activeAssignment->driver?->name }}</span>
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                                        {{ $booking->activeAssignment->driver?->gender === 'female' ? __('fleet.gender_female') : __('fleet.gender_male') }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 border border-emerald-200">
                                        {{ __('fleet.status_'.$booking->activeAssignment->status) }}
                                    </span>
                                </div>
                                <div class="text-xs text-neutral-600">
                                    Telp: <span class="font-mono">{{ $booking->activeAssignment->driver?->phone ?? '—' }}</span>
                                    @if(!empty($booking->activeAssignment->driver?->languages))
                                        · Bahasa: {{ implode(', ', (array) $booking->activeAssignment->driver->languages) }}
                                    @endif
                                </div>
                                @if($booking->activeAssignment->vehicle)
                                    <div class="text-xs text-neutral-700 pt-1">
                                        Kendaraan: <strong class="font-mono">{{ $booking->activeAssignment->vehicle->plate }}</strong> ({{ $booking->activeAssignment->vehicle->type }} · {{ __('fleet.capacity_pax', ['count' => $booking->activeAssignment->vehicle->capacity]) }})
                                    </div>
                                @endif
                                <div class="text-xs text-neutral-500 pt-1">
                                    Periode: {{ $booking->activeAssignment->date_from->translatedFormat('d M Y') }} s/d {{ $booking->activeAssignment->date_to->translatedFormat('d M Y') }}
                                    @if($booking->activeAssignment->notes)
                                        · Catatan: {{ $booking->activeAssignment->notes }}
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <a href="{{ $this->dutyLetterUrl($booking->activeAssignment) }}" target="_blank" class="inline-flex items-center rounded border border-neutral-300 bg-neutral-0 px-3 py-1.5 text-xs font-medium text-neutral-800 shadow-sm hover:bg-neutral-50">
                                    <svg class="h-3.5 w-3.5 me-1.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    {{ __('fleet.duty_letter') }}
                                </a>
                                @can('driver.assign')
                                    <button type="button" wire:click="openCancelAssignmentModal({{ $booking->activeAssignment->id }})" class="text-xs font-medium text-red-600 hover:text-red-800">
                                        {{ __('fleet.cancel_assignment') }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                @else
                    {{-- No assignment yet --}}
                    @can('driver.assign')
                        <div class="mt-4 space-y-4">
                            @if($genderWarning)
                                <div class="flex items-center gap-2 rounded border border-amber-300 bg-amber-50 p-3 text-xs text-amber-800">
                                    <svg class="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>{{ $genderWarning }}</span>
                                </div>
                            @endif

                            @error('driver_assignment')
                                <div class="rounded border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                                    {{ $message }}
                                </div>
                            @enderror

                            <form wire:submit="assignDriver" class="rounded border border-neutral-100 bg-neutral-50 p-4 space-y-3">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                            {{ __('fleet.driver') }} <span class="text-red-600">*</span>
                                        </label>
                                        <select wire:model="selected_driver_id" required class="w-full rounded border border-neutral-300 px-3 py-2 text-xs focus:border-red-500 focus:outline-none">
                                            <option value="">-- Pilih Driver ({{ $suggestedDrivers->count() }} tersedia) --</option>
                                            @foreach($suggestedDrivers as $driver)
                                                <option value="{{ $driver->id }}">
                                                    {{ $driver->name }} ({{ $driver->gender === 'female' ? 'Perempuan' : 'Laki-laki' }})
                                                    @if(!empty($driver->languages)) [{{ implode(',', (array) $driver->languages) }}] @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('selected_driver_id') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                            {{ __('fleet.vehicle') }} (Opsional)
                                        </label>
                                        <select wire:model="selected_vehicle_id" class="w-full rounded border border-neutral-300 px-3 py-2 text-xs focus:border-red-500 focus:outline-none">
                                            <option value="">-- Tanpa Kendaraan / Driver Bawa Sendiri --</option>
                                            @foreach($availableVehicles as $veh)
                                                <option value="{{ $veh->id }}">
                                                    {{ $veh->plate }} — {{ $veh->type }} ({{ $veh->capacity }} Pax)
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('selected_vehicle_id') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                            {{ __('fleet.departure_date') }} <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" wire:model.live="assignment_date_from" required class="w-full rounded border border-neutral-300 px-3 py-1.5 text-xs" />
                                        @error('assignment_date_from') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                            {{ __('fleet.return_date') }} <span class="text-red-600">*</span>
                                        </label>
                                        <input type="date" wire:model.live="assignment_date_to" required class="w-full rounded border border-neutral-300 px-3 py-1.5 text-xs" />
                                        @error('assignment_date_to') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-neutral-700 mb-1">
                                        {{ __('fleet.notes') }}
                                    </label>
                                    <input type="text" wire:model="assignment_notes" placeholder="Contoh: Tamu butuh driver berbahasa Arab untuk penjemputan bandara" class="w-full rounded border border-neutral-300 px-3 py-1.5 text-xs" />
                                    @error('assignment_notes') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                                </div>

                                <div class="pt-2">
                                    <button type="submit" wire:loading.attr="disabled" wire:target="assignFleet" class="inline-flex items-center rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700 disabled:opacity-50">
                                        {{ __('fleet.assign_driver_and_vehicle') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-neutral-500 italic">{{ __('fleet.no_assignment') }}</p>
                    @endcan
                @endif
            </div>
        </div>

        <div class="space-y-6">
            {{-- Finance & Invoicing Card --}}
            <div class="rounded border border-neutral-200 bg-neutral-0 p-4">
                <div class="flex items-center justify-between border-b border-neutral-100 pb-2">
                    <h3 class="text-sm font-bold text-neutral-900">{{ __('finance.finance') }}</h3>
                    <a href="{{ $this->invoiceUrl() }}" target="_blank" class="inline-flex items-center rounded border border-neutral-300 bg-neutral-0 px-2 py-1 text-[11px] font-medium text-neutral-700 hover:bg-neutral-50">
                        <svg class="h-3 w-3 me-1 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        {{ __('finance.invoice') }}
                    </a>
                </div>

                @if($financeError)
                    <div class="mt-2 rounded border border-red-300 bg-red-50 p-2.5 text-xs text-red-800">
                        {{ $financeError }}
                    </div>
                @endif
                @if($financeSuccess)
                    <div class="mt-2 rounded border border-emerald-300 bg-emerald-50 p-2.5 text-xs text-emerald-800">
                        {{ $financeSuccess }}
                    </div>
                @endif

                <div class="mt-3 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-neutral-500">{{ __('finance.total_billed') }}:</span>
                        <strong class="text-neutral-900">{{ $booking->currency }} {{ number_format($booking->total_minor, 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-neutral-500">{{ __('finance.total_paid') }}:</span>
                        <strong class="text-emerald-700">{{ $booking->currency }} {{ number_format($booking->totalPaidMinor(), 0, ',', '.') }}</strong>
                    </div>
                    <div class="flex justify-between border-t border-neutral-100 pt-1 font-semibold">
                        <span class="text-neutral-700">{{ __('finance.remaining_balance') }}:</span>
                        <span class="{{ $booking->remainingBalanceMinor() > 0 ? 'text-red-600' : 'text-emerald-700' }}">
                            {{ $booking->currency }} {{ number_format($booking->remainingBalanceMinor(), 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="button" wire:click="openRecordPaymentModal" class="w-full rounded bg-red-600 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                        + {{ __('finance.record_payment') }}
                    </button>
                </div>

                {{-- Payment History List --}}
                <div class="mt-4 border-t border-neutral-100 pt-3">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-2">Riwayat Pembayaran</h4>
                    <div class="space-y-2">
                        @forelse($bookingPayments as $payment)
                            <div wire:key="payment-{{ $payment->id }}" class="rounded border border-neutral-100 bg-neutral-50 p-2 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-neutral-900">
                                        {{ $payment->currency }} {{ number_format($payment->amount_minor, 0, ',', '.') }}
                                    </span>
                                    @php
                                        $badgeClass = match($payment->status) {
                                            'verified' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            default => 'bg-amber-50 text-amber-700 border-amber-200',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium border {{ $badgeClass }}">
                                        {{ __('finance.status_'.$payment->status) }}
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[11px] text-neutral-500">
                                    <span>{{ __('finance.type_'.$payment->type) }} · {{ strtoupper($payment->channel) }}</span>
                                    <span>{{ $payment->created_at->format('d/m/Y') }}</span>
                                </div>

                                <div class="mt-2 flex items-center justify-between border-t border-neutral-200/60 pt-1.5 text-[11px]">
                                    <div class="flex items-center gap-2">
                                        @if($payment->proof_file)
                                            <a href="{{ $this->proofUrl($payment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                Bukti
                                            </a>
                                        @endif
                                        @if($payment->status === 'verified')
                                            <a href="{{ $this->receiptUrl($payment) }}" target="_blank" class="text-neutral-700 hover:underline font-medium">
                                                {{ __('finance.receipt') }}
                                            </a>
                                        @endif
                                    </div>

                                    @can('payment.verify')
                                        @if($payment->status === 'pending')
                                            <button type="button" wire:click="verifyBookingPayment({{ $payment->id }})" wire:loading.attr="disabled" wire:target="verifyBookingPayment({{ $payment->id }})" class="font-bold text-emerald-700 hover:underline disabled:opacity-50">
                                                Verifikasi
                                            </button>
                                        @elseif($payment->status === 'verified')
                                            <button type="button" wire:click="openRefundModal({{ $payment->id }})" wire:loading.attr="disabled" wire:target="openRefundModal({{ $payment->id }})" class="text-red-600 hover:underline disabled:opacity-50">
                                                Refund
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @empty
                            <p class="text-[11px] text-neutral-400 italic">Belum ada catatan pembayaran.</p>
                        @endforelse
                    </div>
                </div>
            </div>

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
                        <li wire:key="history-{{ $history->id }}" class="border-b border-neutral-100 pb-1">
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

    {{-- Cancel Assignment Modal --}}
    @if($showCancelAssignmentModal)
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
                        <textarea wire:model="cancel_assignment_reason" rows="3" required placeholder="Contoh: Tamu membatalkan penugasan driver pribadi" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                        @error('cancel_assignment_reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showCancelAssignmentModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
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

    {{-- Record Payment Modal --}}
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-lg rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ __('finance.record_payment') }} — {{ $booking->code }}
                </h3>

                <form wire:submit="recordPayment" class="mt-4 space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.payment_type') }} <span class="text-red-600">*</span>
                            </label>
                            <select wire:model="payment_type" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                                <option value="down_payment">{{ __('finance.type_down_payment') }}</option>
                                <option value="full_payment">{{ __('finance.type_full_payment') }}</option>
                                <option value="installment">{{ __('finance.type_installment') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.channel') }} <span class="text-red-600">*</span>
                            </label>
                            <select wire:model="payment_channel" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                                <option value="manual_transfer">Transfer Bank (Manual)</option>
                                <option value="international_card">Kartu Kredit Internasional (MDR ~5.5%)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.amount') }} (Minor) <span class="text-red-600">*</span>
                            </label>
                            <input type="number" wire:model="payment_amount_minor" required min="1" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                            @error('payment_amount_minor') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.currency') }} <span class="text-red-600">*</span>
                            </label>
                            <select wire:model.live="payment_currency" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none">
                                <option value="IDR">IDR</option>
                                <option value="SAR">SAR</option>
                                <option value="USD">USD</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.fx_rate') }}
                            </label>
                            <input type="number" step="0.00000001" wire:model="payment_fx_rate" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                            @error('payment_fx_rate') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                                {{ __('finance.payment_proof') }}
                            </label>
                            <input type="file" wire:model="paymentProofFile" class="mt-1 block w-full text-xs text-neutral-600" />
                            @error('paymentProofFile') <span class="text-[10px] text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.notes') }}
                        </label>
                        <input type="text" wire:model="payment_notes" placeholder="Contoh: Transfer Bank Mandiri rek 12345678 a.n. Tamu" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showPaymentModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            {{ __('catalog.common.cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="recordPayment" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700 disabled:opacity-50">
                            {{ __('finance.record_payment') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Refund Modal --}}
    @if($showRefundModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-md rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ __('finance.process_refund') }}
                </h3>
                <p class="mt-1 text-xs text-neutral-600">
                    Pengembalian dana (refund) wajib disertai alasan tertulis yang sah untuk audit keuangan.
                </p>

                <form wire:submit="processRefund" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.amount') }} (Minor) <span class="text-red-600">*</span>
                        </label>
                        <input type="number" wire:model="refund_amount_minor" required min="1" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                        @error('refund_amount_minor') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('finance.refund_reason') }} <span class="text-red-600">*</span>
                        </label>
                        <textarea wire:model="refund_reason" rows="3" required placeholder="Contoh: Tamu membatalkan satu kamar hotel karena perubahan jadwal penerbangan" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none"></textarea>
                        @error('refund_reason') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showRefundModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            {{ __('catalog.common.cancel') }}
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="processRefund" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700 disabled:opacity-50">
                            {{ __('finance.process_refund') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
