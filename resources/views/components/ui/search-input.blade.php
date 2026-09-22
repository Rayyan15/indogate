{{-- Reusable list-page search box. Binds to the Livewire component's
     `search` property with a 150ms debounce used across every admin list
     screen — short enough to feel instant, long enough to not fire a
     request per keystroke. Pass :placeholder, and any extra class/width
     via the component's own attributes (merged onto admin-input). --}}
@props(['placeholder' => ''])
<input type="text" placeholder="{{ $placeholder }}"
    @if(!$attributes->has('wire:model') && !$attributes->has('wire:model.live') && !$attributes->has('wire:model.live.debounce.150ms') && !$attributes->has('wire:model.live.debounce.300ms'))
        wire:model.live.debounce.150ms="search"
    @endif
    {{ $attributes->merge(['class' => 'admin-input']) }}>
