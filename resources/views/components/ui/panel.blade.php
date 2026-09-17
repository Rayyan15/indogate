{{-- Hairline surface. The only "card". No shadow-lg, no rounded-2xl.
     Pass href to make the whole panel a clickable card — gets a small corner arrow to signal it. --}}
@props(['title' => null, 'eyebrow' => null, 'flush' => false, 'href' => null])
@php $tag = $href ? 'a' : 'section'; @endphp
<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'relative block rounded border border-neutral-200 bg-neutral-0' . ($href ? ' transition hover:border-neutral-400' : '')]) }}>
    @if($href)
        <svg class="absolute end-3 top-3 h-3.5 w-3.5 text-neutral-300 transition group-hover:text-red-500 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
    @endif
    @if($title || $eyebrow || isset($actions))
        <div class="flex items-end justify-between gap-4 border-b border-neutral-200 px-5 py-4">
            <div>
                @if($eyebrow)<x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>@endif
                @if($title)<h2 class="mt-0.5 font-display text-lg text-neutral-900">{{ $title }}</h2>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'p-5' }}">{{ $slot }}</div>
</{{ $tag }}>
