{{-- Numbered itinerary/line-item row. Stack inside `divide-y divide-neutral-200 rounded border border-neutral-200`. --}}
@props(['index', 'kicker' => null, 'title', 'meta' => null, 'amount' => null])
<div {{ $attributes->merge(['class' => 'group flex items-start gap-4 bg-neutral-0 p-4 transition hover:bg-neutral-50']) }}>
    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded border border-neutral-200 bg-neutral-50 font-mono text-[11px] font-semibold text-neutral-600">{{ str_pad($index, 2, '0', STR_PAD_LEFT) }}</span>
    <div class="min-w-0 flex-1">
        @if($kicker)<x-ui.eyebrow>{{ $kicker }}</x-ui.eyebrow>@endif
        <h3 class="mt-0.5 truncate text-sm font-semibold text-neutral-900">{{ $title }}</h3>
        @if($meta)<p class="mt-0.5 text-xs leading-relaxed text-neutral-500">{{ $meta }}</p>@endif
    </div>
    <div class="shrink-0 text-end">
        @if($amount)<span class="block font-mono text-xs font-semibold tabular-nums text-neutral-900">{{ $amount }}</span>@endif
        @isset($actions)<div class="mt-1.5 flex justify-end gap-2 opacity-60 transition group-hover:opacity-100">{{ $actions }}</div>@endisset
    </div>
</div>
