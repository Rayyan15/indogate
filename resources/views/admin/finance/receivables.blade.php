<x-admin-layout>
    <x-slot name="header">
        <title>{{ __('finance.receivables') }} — Indogate</title>
    </x-slot>

    @livewire('admin.finance.accounts-receivable-list')
</x-admin-layout>
