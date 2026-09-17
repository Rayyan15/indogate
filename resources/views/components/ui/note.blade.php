{{-- Informational panel (PRD §4.5). tone: info | warning --}}
@props(['tone' => 'info'])
@php $t = $tone === 'warning' ? 'border-warning bg-warning/5' : 'border-blue-600 bg-blue-50'; @endphp
<div {{ $attributes->merge(['class' => "rounded-sm border-s-4 px-4 py-3 text-xs leading-relaxed text-neutral-800 $t"]) }}>{{ $slot }}</div>
