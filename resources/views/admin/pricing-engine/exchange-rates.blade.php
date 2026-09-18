<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'exchange-rates'])
    @livewire('admin.pricing.exchange-rate-list')
</x-admin-layout>
