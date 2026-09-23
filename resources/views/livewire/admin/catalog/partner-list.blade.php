<div>
    <x-ui.page-header :eyebrow="__('catalog.partner.eyebrow')" :title="__('catalog.partner.index_title')" :lede="__('catalog.partner.index_lede')">
        <x-slot name="actions">
            <x-ui.search-input :placeholder="__('catalog.partner.name').'…'" class="w-48" />
            <x-ui.filter-select model="typeFilter" class="w-44" :placeholder="__('catalog.partner.all_types')" :options="[
                'hotel' => __('catalog.partner.hotel'),
                'villa' => __('catalog.partner.villa'),
                'vehicle_vendor' => __('catalog.partner.vehicle_vendor'),
            ]" />
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-partner')">{{ __('catalog.partner.add_partner') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('catalog.partner.name') }}</x-ui.th>
            <x-ui.th sortable field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('catalog.partner.type') }}</x-ui.th>
            <x-ui.th sortable field="city" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('catalog.partner.city') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.partner.items_count') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.common.status') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($partners as $partner)
            <x-ui.tr wire:key="partner-{{ $partner->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ $partner->name }}</x-ui.td>
                <x-ui.td>{{ __('catalog.partner.'.$partner->type->value) }}</x-ui.td>
                <x-ui.td>{{ $partner->city ?? '—' }}</x-ui.td>
                <x-ui.td class="font-mono">{{ $partner->inventory_items_count }}</x-ui.td>
                <x-ui.td><x-ui.status :status="$partner->is_active ? 'paid' : 'cancelled'">{{ $partner->is_active ? __('catalog.partner.active') : __('catalog.partner.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td>
                    <div class="flex items-center justify-center">
                        <x-ui.icon-button type="button" wire:click="$dispatch('edit-partner', { partnerId: {{ $partner->id }} })" :title="__('catalog.common.edit')">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </x-ui.icon-button>
                    </div>
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
