<x-admin-layout>
    <x-slot name="header">
        <title>{{ __('finance.vendor_payments') }} — Indogate</title>
    </x-slot>

    @livewire('admin.finance.vendor-payment-list')
</x-admin-layout>
