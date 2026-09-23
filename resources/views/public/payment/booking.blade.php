@php
    $fmt = fn (int $minor) => $booking->currency.' '.\App\Domain\Finance\Fx::format($minor, $booking->currency);
    $icons = [
        'va_bca' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'va_mandiri' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'qris' => 'M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 3h2m2 0h2m-6 3h6m-6-6h2',
        'card' => 'M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm2 9h3',
    ];
@endphp
<x-payment-layout :title="__('payment.title').' '.$booking->code">
    <section class="rounded-lg border border-neutral-200 bg-neutral-0 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-500">{{ __('payment.booking_code') }}</p>
        <h1 class="mt-1 font-mono text-2xl font-semibold text-neutral-900" dir="ltr">{{ $booking->code }}</h1>

        <dl class="mt-4 space-y-2 text-sm">
            @if($booking->quotation?->package)
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.package') }}</dt><dd class="text-end font-medium text-neutral-900">{{ $booking->quotation->package->name }}</dd></div>
            @endif
            @if($booking->quotation?->lead)
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.guest') }}</dt><dd class="text-end text-neutral-900">{{ $booking->quotation->lead->name }}</dd></div>
            @endif
            @if($booking->departure_date)
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.departure') }}</dt><dd class="text-end text-neutral-900">{{ $booking->departure_date->translatedFormat('d M Y') }}</dd></div>
            @endif
            <div class="flex justify-between gap-4 border-t border-neutral-100 pt-2"><dt class="text-neutral-500">{{ __('payment.total') }}</dt><dd class="font-mono tabular-nums text-neutral-900" dir="ltr">{{ $fmt((int) $booking->total_minor) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.paid_so_far') }}</dt><dd class="font-mono tabular-nums text-neutral-900" dir="ltr">{{ $fmt($booking->totalPaidMinor()) }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="font-semibold text-neutral-900">{{ __('payment.remaining') }}</dt><dd class="font-mono text-lg font-semibold tabular-nums {{ $remaining > 0 ? 'text-red-600' : 'text-success' }}" dir="ltr">{{ $fmt($remaining) }}</dd></div>
        </dl>
    </section>

    @if(! $payable)
        <div class="mt-6 rounded-lg border border-success/20 bg-success/10 p-5 text-sm font-medium text-success">
            {{ __('payment.nothing_to_pay') }}
        </div>
    @else
        <form method="POST" action="{{ $postUrl }}" class="mt-6 space-y-6"
              x-data="{ type: '{{ $dpAmount ? 'down_payment' : 'full_payment' }}', method: 'va_bca' }">
            @csrf

            @error('payment')
                <div class="rounded border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $message }}</div>
            @enderror

            <fieldset>
                <legend class="mb-3 font-display text-lg text-neutral-900">{{ __('payment.choose_amount') }}</legend>
                <div class="space-y-2">
                    @if($dpAmount)
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border bg-neutral-0 p-4 transition"
                               :class="type === 'down_payment' ? 'border-red-600 ring-1 ring-red-600' : 'border-neutral-200'">
                            <span class="flex items-center gap-3">
                                <input type="radio" name="type" value="down_payment" x-model="type" class="text-red-600 focus:ring-red-600">
                                <span class="text-sm font-medium text-neutral-900">{{ __('payment.dp_label', ['percent' => $dpPercent]) }}</span>
                            </span>
                            <span class="font-mono text-sm font-semibold tabular-nums text-neutral-900" dir="ltr">{{ $fmt($dpAmount) }}</span>
                        </label>
                    @endif
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border bg-neutral-0 p-4 transition"
                           :class="type === 'full_payment' ? 'border-red-600 ring-1 ring-red-600' : 'border-neutral-200'">
                        <span class="flex items-center gap-3">
                            <input type="radio" name="type" value="full_payment" x-model="type" class="text-red-600 focus:ring-red-600">
                            <span class="text-sm font-medium text-neutral-900">{{ __('payment.full_label') }}</span>
                        </span>
                        <span class="font-mono text-sm font-semibold tabular-nums text-neutral-900" dir="ltr">{{ $fmt($remaining) }}</span>
                    </label>
                </div>
            </fieldset>

            <fieldset>
                <legend class="mb-3 font-display text-lg text-neutral-900">{{ __('payment.choose_method') }}</legend>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($methods as $method)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border bg-neutral-0 p-4 transition"
                               :class="method === '{{ $method }}' ? 'border-red-600 ring-1 ring-red-600' : 'border-neutral-200'">
                            <input type="radio" name="method" value="{{ $method }}" x-model="method" class="sr-only">
                            <svg class="h-5 w-5 shrink-0 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $icons[$method] ?? $icons['card'] }}"></path></svg>
                            <span class="text-sm font-medium text-neutral-900">{{ __('payment.methods.'.$method) }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <button type="submit" class="w-full rounded-md bg-red-600 px-5 py-3.5 text-sm font-semibold uppercase tracking-wider text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                {{ __('payment.pay_now') }}
            </button>
        </form>
    @endif
</x-payment-layout>
