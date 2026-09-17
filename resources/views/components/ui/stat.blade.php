{{-- Ledger-style KPI: hairline on the start edge, mono number, eyebrow label. --}}
@props(['label', 'value', 'note' => null, 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'group block border-s border-neutral-200 ps-5 py-1 transition' . ($href ? ' hover:border-red-600' : '')]) }}>
    <x-ui.eyebrow>{{ $label }}</x-ui.eyebrow>
    <p class="mt-2 font-mono text-3xl font-medium tabular-nums tracking-tight text-neutral-900">{{ $value }}</p>
    @if($note)<p class="mt-1 text-[11px] text-neutral-500">{{ $note }}</p>@endif
</{{ $tag }}>
