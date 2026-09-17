{{-- Amount with muted currency code. --}}
@props(['currency', 'amount', 'size' => 'text-sm'])
<span {{ $attributes->merge(['class' => "font-mono tabular-nums $size text-neutral-900"]) }}><span class="me-1 text-[0.75em] font-medium text-neutral-400">{{ $currency }}</span>{{ $amount }}</span>
