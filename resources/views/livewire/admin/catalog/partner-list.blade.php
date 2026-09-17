<div>
    <x-ui.page-header :eyebrow="__('catalog.partner.eyebrow')" :title="__('catalog.partner.index_title')" :lede="__('catalog.partner.index_lede')">
        <x-slot name="actions">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('catalog.partner.name') }}…" class="admin-input w-48">
            <select wire:model.live="typeFilter" class="admin-input w-44">
                <option value="">{{ __('catalog.partner.all_types') }}</option>
                <option value="hotel">{{ __('catalog.partner.hotel') }}</option>
                <option value="villa">{{ __('catalog.partner.villa') }}</option>
                <option value="vehicle_vendor">{{ __('catalog.partner.vehicle_vendor') }}</option>
            </select>
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-partner')">{{ __('catalog.partner.add_partner') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('catalog.partner.name') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.partner.type') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.partner.city') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.partner.items_count') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.common.status') }}</x-ui.th>
            <x-ui.th numeric>{{ __('catalog.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($partners as $partner)
            <x-ui.tr wire:key="partner-{{ $partner->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ $partner->name }}</x-ui.td>
                <x-ui.td>{{ __('catalog.partner.'.$partner->type->value) }}</x-ui.td>
                <x-ui.td>{{ $partner->city ?? '—' }}</x-ui.td>
                <x-ui.td class="font-mono">{{ $partner->inventory_items_count }}</x-ui.td>
                <x-ui.td><x-ui.status :status="$partner->is_active ? 'paid' : 'cancelled'">{{ $partner->is_active ? __('catalog.partner.active') : __('catalog.partner.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td numeric>
                    <x-ui.button variant="ghost" type="button" wire:click="$dispatch('edit-partner', { partnerId: {{ $partner->id }} })">{{ __('catalog.common.edit') }}</x-ui.button>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="6" class="p-0">
                <x-ui.empty :title="__('catalog.partner.no_partners_yet')" :text="__('catalog.partner.no_partners_text')">
                    <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-partner')">{{ __('catalog.partner.add_partner') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $partners->links() }}</div>

    @livewire('admin.catalog.partner-form')
</div>
