{{-- Reusable list-page filter dropdown. :model is the Livewire property
     name to bind (wire:model.live), :options an ['value' => 'label'] map,
     :placeholder the "all" option label. --}}
@props(['model', 'options' => [], 'placeholder' => ''])
<select wire:model.live="{{ $model }}" {{ $attributes->merge(['class' => 'admin-input']) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
