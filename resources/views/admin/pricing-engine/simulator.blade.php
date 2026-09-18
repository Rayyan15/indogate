<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'simulator'])
    @livewire('admin.pricing.pricing-simulator')
</x-admin-layout>
