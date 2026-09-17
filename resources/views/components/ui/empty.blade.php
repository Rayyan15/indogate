{{-- Empty state that says what to do next. Optional action in slot. --}}
@props(['title', 'text' => null])
<div {{ $attributes->merge(['class' => 'rounded border border-dashed border-neutral-300 bg-neutral-50/50 px-6 py-12 text-center']) }}>
    <span class="mx-auto block h-px w-10 bg-neutral-300"></span>
    <p class="mt-4 font-display text-lg text-neutral-800">{{ $title }}</p>
    @if($text)<p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-neutral-500">{{ $text }}</p>@endif
    @if(trim($slot))<div class="mt-5 flex justify-center gap-2">{{ $slot }}</div>@endif
</div>
