{{-- Editorial page header: eyebrow (context) + serif title + optional actions slot. --}}
@props(['eyebrow' => null, 'title', 'lede' => null])
<header {{ $attributes->merge(['class' => 'mb-8 border-b border-neutral-200 pb-6']) }}>
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0">
            @if($eyebrow)
                <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-neutral-500">{{ $eyebrow }}</div>
            @endif
            <h1 class="font-display text-3xl font-light tracking-tight text-neutral-900 md:text-4xl [text-wrap:balance]">{{ $title }}</h1>
            @if($lede)<p class="mt-2 max-w-2xl text-sm leading-relaxed text-neutral-500">{{ $lede }}</p>@endif
        </div>
        @isset($actions)<div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>@endisset
    </div>
</header>
