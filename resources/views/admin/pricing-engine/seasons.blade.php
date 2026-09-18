<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'seasons'])
    @livewire('admin.pricing.season-list')
</x-admin-layout>
