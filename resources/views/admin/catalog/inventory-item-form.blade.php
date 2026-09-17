<x-admin-layout>
    <x-slot name="header">{{ isset($item) ? __('catalog.item.edit_title') : __('catalog.item.create_title') }}</x-slot>

    @livewire('admin.catalog.inventory-item-manager', ['item' => $item ?? null])
</x-admin-layout>
