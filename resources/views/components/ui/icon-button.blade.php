{{-- Icon-only row action (Actions column). :title is required — it's the
     only accessible label since there's no visible text, exposed via the
     native title attribute and aria-label. --}}
@props(['href' => null, 'type' => 'button', 'title'])
@php
$base = 'inline-flex h-8 w-8 items-center justify-center rounded text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600';
@endphp
@if($href)
    <a href="{{ $href }}" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" title="{{ $title }}" aria-label="{{ $title }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
