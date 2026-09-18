<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'channel-costs'])
    @livewire('admin.pricing.payment-channel-cost-list')
</x-admin-layout>
