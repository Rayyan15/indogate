{{-- Label + control + hint/error. Put the <input class="admin-input"> in the slot. --}}
@props(['label', 'for' => null, 'hint' => null, 'error' => null])
<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <label @if($for) for="{{ $for }}" @endif class="flex items-baseline justify-between">
        <span class="text-[11px] font-semibold uppercase tracking-[0.14em] text-neutral-600">{{ $label }}</span>
        @if($hint)<span class="text-[11px] text-neutral-400">{{ $hint }}</span>@endif
    </label>
    {{ $slot }}
    @if($error)<p class="text-[11px] text-danger">{{ $error }}</p>@endif
</div>
