@php
    $booking = $intent->booking;
    $amount = $intent->currency.' '.\App\Domain\Finance\Fx::format((int) $intent->amount_minor, $intent->currency);
    $method = $intent->method ?? 'va_bca';
    $vaPrefix = $method === 'va_mandiri' ? '8908' : '8808';
    $vaNumber = $vaPrefix.str_pad((string) $intent->booking_id, 12, '0', STR_PAD_LEFT);
    $simulateUrl = route('payments.simulator.simulate', ['intent' => $intent->public_token]);
@endphp
<x-payment-layout :title="__('payment.title')">
    <div class="sticky top-0 z-10 -mx-4 mb-6 border-y border-warning/30 bg-warning px-4 py-3 text-center sm:mx-0 sm:rounded-md sm:border">
        <p class="text-sm font-bold uppercase tracking-wider text-white">{{ __('payment.simulation_banner') }}</p>
        <p class="mt-0.5 text-xs text-white/90">{{ __('payment.simulation_hint') }}</p>
    </div>

    <section class="rounded-lg border border-neutral-200 bg-neutral-0 p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-500">{{ __('payment.amount_due') }}</p>
                <p class="mt-1 font-mono text-2xl font-semibold tabular-nums text-neutral-900" dir="ltr">{{ $amount }}</p>
                <p class="mt-1 text-xs text-neutral-500">
                    {{ __('payment.booking_code') }} <span class="font-mono" dir="ltr">{{ $booking?->code }}</span>
                    @if($booking?->quotation?->lead) · {{ $booking->quotation->lead->name }} @endif
                </p>
            </div>
            <span class="rounded-full bg-neutral-100 px-3 py-1 text-xs font-medium text-neutral-700">{{ __('payment.methods.'.$method) }}</span>
        </div>

        @if($intent->expires_at)
            <div class="mt-4 flex items-center justify-between rounded-md bg-neutral-50 px-4 py-2.5 text-sm"
                 x-data="{ end: {{ $intent->expires_at->getTimestamp() * 1000 }}, left: '' }"
                 x-init="const tick = () => { let s = Math.max(0, Math.floor((end - Date.now()) / 1000)); left = [Math.floor(s / 3600), Math.floor(s % 3600 / 60), s % 60].map(n => String(n).padStart(2, '0')).join(':'); }; tick(); setInterval(tick, 1000)">
                <span class="text-neutral-500">{{ __('payment.time_left') }}</span>
                <span class="font-mono font-semibold tabular-nums text-neutral-900" dir="ltr" x-text="left"></span>
            </div>
        @endif

        <div class="mt-5 border-t border-neutral-100 pt-5">
            @if(str_starts_with($method, 'va_'))
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-500">{{ __('payment.va_number') }}</p>
                <div class="mt-2 flex items-center justify-between gap-3 rounded-md border border-dashed border-neutral-300 px-4 py-3"
                     x-data="{ copied: false }">
                    <span class="font-mono text-xl font-semibold tracking-wider tabular-nums text-neutral-900" dir="ltr">{{ $vaNumber }}</span>
                    <button type="button" class="text-xs font-semibold text-red-600 hover:text-red-700"
                            @click="navigator.clipboard?.writeText('{{ $vaNumber }}'); copied = true; setTimeout(() => copied = false, 1500)"
                            x-text="copied ? '{{ __('payment.admin.copied') }}' : '{{ __('payment.admin.copy') }}'"></button>
                </div>
                <p class="mt-3 text-sm text-neutral-500">{{ __('payment.va_steps') }}</p>
            @elseif($method === 'qris')
                <div class="mx-auto w-48">
                    <svg viewBox="0 0 29 29" class="h-48 w-48 rounded border border-neutral-200 bg-neutral-0 p-2" shape-rendering="crispEdges" aria-hidden="true">
                        @php mt_srand($intent->id); @endphp
                        @for($y = 0; $y < 25; $y++)
                            @for($x = 0; $x < 25; $x++)
                                @php $finder = ($x < 7 && $y < 7) || ($x > 17 && $y < 7) || ($x < 7 && $y > 17); @endphp
                                @if(! $finder && mt_rand(0, 1))
                                    <rect x="{{ $x + 2 }}" y="{{ $y + 2 }}" width="1" height="1" fill="#1A1A19" />
                                @endif
                            @endfor
                        @endfor
                        @foreach([[2, 2], [20, 2], [2, 20]] as [$fx, $fy])
                            <rect x="{{ $fx }}" y="{{ $fy }}" width="7" height="7" fill="#1A1A19" />
                            <rect x="{{ $fx + 1 }}" y="{{ $fy + 1 }}" width="5" height="5" fill="#FFFFFF" />
                            <rect x="{{ $fx + 2 }}" y="{{ $fy + 2 }}" width="3" height="3" fill="#1A1A19" />
                        @endforeach
                    </svg>
                </div>
                <p class="mt-3 text-center text-sm text-neutral-500">{{ __('payment.qris_steps') }}</p>
            @else
                <div class="space-y-3">
                    <label class="block text-xs font-semibold text-neutral-700">{{ __('payment.card_number') }}
                        <input type="text" value="4111 1111 1111 1111" disabled dir="ltr" class="admin-input mt-1 w-full font-mono">
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-xs font-semibold text-neutral-700">{{ __('payment.card_expiry') }}
                            <input type="text" value="12/30" disabled dir="ltr" class="admin-input mt-1 w-full font-mono">
                        </label>
                        <label class="block text-xs font-semibold text-neutral-700">{{ __('payment.card_cvv') }}
                            <input type="text" value="123" disabled dir="ltr" class="admin-input mt-1 w-full font-mono">
                        </label>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="mt-6 rounded-lg border border-dashed border-warning/50 bg-neutral-0 p-5">
        <h2 class="font-display text-lg text-neutral-900">{{ __('payment.simulate_title') }}</h2>
        <div class="mt-4 space-y-2">
            <form method="POST" action="{{ $simulateUrl }}">
                @csrf
                <input type="hidden" name="outcome" value="paid">
                <button type="submit" class="w-full rounded-md bg-red-600 px-5 py-3.5 text-sm font-semibold uppercase tracking-wider text-white shadow-sm hover:bg-red-700">
                    {{ __('payment.simulate_paid') }}
                </button>
            </form>
            <div class="grid grid-cols-2 gap-2">
                <form method="POST" action="{{ $simulateUrl }}">
                    @csrf
                    <input type="hidden" name="outcome" value="failed">
                    <button type="submit" class="w-full rounded-md border border-neutral-300 bg-neutral-0 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50">{{ __('payment.simulate_failed') }}</button>
                </form>
                <form method="POST" action="{{ $simulateUrl }}">
                    @csrf
                    <input type="hidden" name="outcome" value="expired">
                    <button type="submit" class="w-full rounded-md border border-neutral-300 bg-neutral-0 px-4 py-2.5 text-sm font-medium text-neutral-700 hover:bg-neutral-50">{{ __('payment.simulate_expired') }}</button>
                </form>
            </div>
        </div>
    </section>
</x-payment-layout>
