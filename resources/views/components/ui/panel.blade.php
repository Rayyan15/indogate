{{-- Hairline surface. The only "card". No shadow-lg, no rounded-2xl. --}}
@props(['title' => null, 'eyebrow' => null, 'flush' => false])
<section {{ $attributes->merge(['class' => 'rounded border border-neutral-200 bg-neutral-0']) }}>
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
</section>
