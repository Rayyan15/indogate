<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'margin-rules'])
    @livewire('admin.pricing.margin-rule-list')
</x-admin-layout>
