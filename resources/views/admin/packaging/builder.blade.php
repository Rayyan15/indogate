<x-admin-layout>
    <x-slot name="header">{{ __('packaging.eyebrow') }}</x-slot>

    @livewire('admin.packaging.package-builder', ['package' => $package ?? null])
</x-admin-layout>
