<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'currencies'])
    @livewire('admin.pricing.currency-list')
</x-admin-layout>
