{{-- Sticky financial docket for the 35% column. rows = [[label, value], ...]. Put the ONE red button in the slot. --}}
@props(['eyebrow', 'title', 'ref' => null, 'rows' => [], 'totalLabel' => null, 'total' => null, 'footnote' => null])
<aside {{ $attributes->merge(['class' => 'sticky top-24 rounded border border-neutral-200 bg-neutral-0 p-6 shadow-sm']) }}>
    <div class="flex items-baseline justify-between border-b border-neutral-200 pb-4">
        <div>
            <x-ui.eyebrow>{{ $eyebrow }}</x-ui.eyebrow>
            <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $title }}</h2>
        </div>
        @if($ref)<span class="font-mono text-[10px] text-neutral-400">{{ $ref }}</span>@endif
    </div>
    <dl class="my-5 space-y-2.5 text-xs">
        @foreach($rows as [$label, $value])
            <div class="flex items-baseline justify-between gap-4">
                <dt class="text-neutral-500">{{ $label }}</dt>
                <dd class="font-mono tabular-nums text-neutral-800">{{ $value }}</dd>
            </div>
        @endforeach
        @if($total !== null)
            <div class="flex items-baseline justify-between border-t border-neutral-900 pt-3">
                <dt class="text-[11px] font-bold uppercase tracking-[0.14em] text-neutral-700">{{ $totalLabel }}</dt>
                <dd class="font-mono text-2xl font-medium tabular-nums tracking-tight text-neutral-900">{{ $total }}</dd>
            </div>
        @endif
        @if($footnote)<p class="pt-1 text-[11px] italic text-neutral-400">{{ $footnote }}</p>@endif
    </dl>
    {{ $slot }}
</aside>
