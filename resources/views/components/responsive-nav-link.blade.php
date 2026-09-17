@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full border-s-4 border-red-600 bg-red-50 py-2 ps-3 pe-4 text-start text-base font-medium text-red-700 transition focus:outline-none'
            : 'block w-full border-s-4 border-transparent py-2 ps-3 pe-4 text-start text-base font-medium text-neutral-600 transition hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-800 focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
