{{-- variant: primary (max ONE per screen) | secondary | ghost | danger --}}
@props(['variant' => 'secondary', 'href' => null, 'type' => 'button'])
@php
$base = 'inline-flex items-center justify-center gap-2 rounded text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:cursor-not-allowed disabled:opacity-50';
$v = [
    'primary'   => 'bg-red-600 px-4 py-2.5 uppercase tracking-[0.14em] text-neutral-0 hover:bg-red-500 active:bg-red-700',
    'secondary' => 'border border-neutral-300 bg-neutral-0 px-3.5 py-2 text-neutral-800 hover:bg-neutral-100',
    'ghost'     => 'px-2 py-1.5 text-neutral-500 underline-offset-4 hover:text-neutral-900 hover:underline',
    'danger'    => 'px-2 py-1.5 text-neutral-400 hover:text-danger',
][$variant];
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$base $v"]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$base $v"]) }}>{{ $slot }}</button>
@endif
