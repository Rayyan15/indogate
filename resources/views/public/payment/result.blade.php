@php
    $status = in_array($intent->status, ['completed', 'cancelled', 'expired'], true) ? $intent->status : 'pending';
    $fmt = fn (int $minor, string $currency) => $currency.' '.\App\Domain\Finance\Fx::format($minor, $currency);
    $remaining = $booking?->remainingBalanceMinor() ?? 0;
    $tone = [
        'completed' => ['bg-success/10 text-success', 'M5 13l4 4L19 7'],
        'cancelled' => ['bg-danger/10 text-danger', 'M6 18L18 6M6 6l12 12'],
        'expired' => ['bg-warning/10 text-warning', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        'pending' => ['bg-neutral-100 text-neutral-600', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
    ][$status];
@endphp
<x-payment-layout :title="__('payment.result.'.$status.'_title')">
    @if($intent->provider === 'simulator')
        <p class="mb-4 rounded-md bg-warning/10 px-4 py-2 text-center text-xs font-semibold uppercase tracking-wider text-warning">{{ __('payment.simulation_banner') }}</p>
    @endif

    <section class="rounded-lg border border-neutral-200 bg-neutral-0 p-6 text-center shadow-sm">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full {{ $tone[0] }}">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tone[1] }}"></path></svg>
        </span>
        <h1 class="mt-4 font-display text-2xl text-neutral-900">{{ __('payment.result.'.$status.'_title') }}</h1>
        <p class="mt-2 text-sm text-neutral-500">{{ __('payment.result.'.$status.'_body') }}</p>

        @if($status === 'cancelled' && $intent->failure_reason)
            <p class="mt-3 text-sm text-danger">{{ __('payment.reason') }}: {{ $intent->failure_reason }}</p>
        @endif

        <dl class="mt-6 space-y-2 border-t border-neutral-100 pt-4 text-start text-sm">
            @if($booking)
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.booking_code') }}</dt><dd class="font-mono text-neutral-900" dir="ltr">{{ $booking->code }}</dd></div>
            @endif
            <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ $intent->status === 'completed' ? __('payment.amount_paid') : __('payment.amount_due') }}</dt><dd class="font-mono tabular-nums text-neutral-900" dir="ltr">{{ $fmt((int) $intent->amount_minor, $intent->currency) }}</dd></div>
            @if($intent->provider_reference)
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.reference') }}</dt><dd class="font-mono text-neutral-900" dir="ltr">{{ $intent->provider_reference }}</dd></div>
            @endif
            @if($booking)
                <div class="flex justify-between gap-4"><dt class="text-neutral-500">{{ __('payment.booking_status') }}</dt><dd class="text-neutral-900">{{ __('booking.status.'.$booking->status->value) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="font-semibold text-neutral-900">{{ __('payment.remaining') }}</dt><dd class="font-mono font-semibold tabular-nums text-neutral-900" dir="ltr">{{ $fmt($remaining, $booking->currency) }}</dd></div>
            @endif
        </dl>

        @if($status === 'pending' && $intent->isPayable())
            <a href="{{ route('payments.simulator.show', ['intent' => $intent->public_token]) }}" class="mt-6 inline-block w-full rounded-md bg-red-600 px-5 py-3.5 text-sm font-semibold uppercase tracking-wider text-white hover:bg-red-700">{{ __('payment.back_to_checkout') }}</a>
        @elseif($retryUrl && $remaining > 0 && in_array($booking?->status?->value, ['confirmed', 'partially_paid'], true))
            <a href="{{ $retryUrl }}" class="mt-6 inline-block w-full rounded-md bg-red-600 px-5 py-3.5 text-sm font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                {{ $status === 'completed' ? __('payment.pay_remaining') : __('payment.retry') }}
            </a>
        @endif
    </section>
</x-payment-layout>
