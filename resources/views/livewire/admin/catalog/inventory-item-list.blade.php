<div>
    <x-ui.page-header :eyebrow="__('catalog.item.eyebrow')" :title="__('catalog.item.index_title')" :lede="__('catalog.item.index_lede')">
        <x-slot name="actions">
            <x-ui.search-input :placeholder="__('catalog.item.name').'…'" class="w-44" />
            <x-ui.filter-select model="partnerFilter" class="w-44" :placeholder="__('catalog.item.all_partners')" :options="$partners->pluck('name', 'id')->all()" />
            <x-ui.filter-select model="typeFilter" class="w-40" :placeholder="__('catalog.item.all_types')" :options="[
                'room' => __('catalog.item.room'),
                'vehicle' => __('catalog.item.vehicle'),
                'ticket' => __('catalog.item.ticket'),
                'activity' => __('catalog.item.activity'),
            ]" />
            <x-ui.button variant="primary" :href="route('admin.catalog.inventory-items.create')">{{ __('catalog.item.add_item') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('catalog.item.name') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.item.partner') }}</x-ui.th>
            <x-ui.th sortable field="type" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('catalog.item.type') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.item.rates_count') }}</x-ui.th>
            <x-ui.th sortable field="is_active" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('catalog.common.status') }}</x-ui.th>
            <x-ui.th numeric>{{ __('catalog.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($items as $item)
            <x-ui.tr wire:key="item-{{ $item->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ $item->name }}</x-ui.td>
                <x-ui.td>{{ $item->partner?->name }}</x-ui.td>
                <x-ui.td>{{ __('catalog.item.'.$item->type->value) }}</x-ui.td>
                <x-ui.td class="font-mono">{{ $item->rates_count }}</x-ui.td>
                <x-ui.td><x-ui.status :status="$item->is_active ? 'paid' : 'cancelled'">{{ $item->is_active ? __('catalog.partner.active') : __('catalog.partner.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td numeric>
                    <x-ui.icon-button :href="route('admin.catalog.inventory-items.edit', $item)" :title="__('catalog.common.edit')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </x-ui.icon-button>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="6" class="p-0">
                <x-ui.empty :title="__('catalog.item.no_items_yet')" :text="__('catalog.item.no_items_text')">
                    <x-ui.button variant="primary" :href="route('admin.catalog.inventory-items.create')">{{ __('catalog.item.add_item') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $items->links() }}</div>
</div>
