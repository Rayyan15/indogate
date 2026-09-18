<x-admin-layout>
    <x-slot name="header">{{ __('lead.eyebrow') }}</x-slot>

    @livewire('admin.lead.lead-form', ['lead' => $lead ?? null])
</x-admin-layout>
