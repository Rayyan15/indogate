{{-- Ledger-style KPI: hairline on the start edge, mono number, eyebrow label. Clickable variant gets a small corner arrow to signal it. --}}
@props(['label', 'value', 'note' => null, 'href' => null])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'group relative block border-s border-neutral-200 ps-5 py-1 pe-5 transition' . ($href ? ' hover:border-red-600' : '')]) }}>
    @if($href)
        <svg class="absolute end-0 top-0.5 h-3.5 w-3.5 text-neutral-300 transition group-hover:text-red-500 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
    @endif
    <x-ui.eyebrow>{{ $label }}</x-ui.eyebrow>
    <p class="mt-2 font-mono text-3xl font-medium tabular-nums tracking-tight text-neutral-900">{{ $value }}</p>
    @if($note)<p class="mt-1 text-[11px] text-neutral-500">{{ $note }}</p>@endif
</{{ $tag }}>
