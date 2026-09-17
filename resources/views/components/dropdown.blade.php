@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-neutral-0'])

@php
$alignmentClasses = match ($align) {
    'left' => 'start-0 ltr:origin-top-left rtl:origin-top-right',
    'top' => 'origin-top',
    default => 'end-0 ltr:origin-top-right rtl:origin-top-left',
};

$width = match ($width) {
    '48' => 'w-48',
    default => $width,
};
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} rounded border border-neutral-200 shadow-md {{ $alignmentClasses }}"
            style="display: none;"
            @click="open = false">
        <div class="rounded {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
