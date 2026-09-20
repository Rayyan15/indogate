<x-admin-layout>
    <x-slot name="header">
        <title>{{ __('finance.payments') }} — Indogate</title>
    </x-slot>

    @livewire('admin.finance.payment-list')
</x-admin-layout>
