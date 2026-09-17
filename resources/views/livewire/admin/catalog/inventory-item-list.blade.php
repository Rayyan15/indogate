<div>
    <x-ui.page-header :eyebrow="__('catalog.item.eyebrow')" :title="__('catalog.item.index_title')" :lede="__('catalog.item.index_lede')">
        <x-slot name="actions">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('catalog.item.name') }}…" class="admin-input w-44">
            <select wire:model.live="partnerFilter" class="admin-input w-44">
                <option value="">{{ __('catalog.item.all_partners') }}</option>
                @foreach($partners as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="typeFilter" class="admin-input w-40">
                <option value="">{{ __('catalog.item.all_types') }}</option>
                <option value="room">{{ __('catalog.item.room') }}</option>
                <option value="vehicle">{{ __('catalog.item.vehicle') }}</option>
                <option value="ticket">{{ __('catalog.item.ticket') }}</option>
                <option value="activity">{{ __('catalog.item.activity') }}</option>
            </select>
            <x-ui.button variant="primary" :href="route('admin.catalog.inventory-items.create')">{{ __('catalog.item.add_item') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('catalog.item.name') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.item.partner') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.item.type') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.item.rates_count') }}</x-ui.th>
            <x-ui.th>{{ __('catalog.common.status') }}</x-ui.th>
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
                    <x-ui.button variant="ghost" :href="route('admin.catalog.inventory-items.edit', $item)">{{ __('catalog.common.edit') }}</x-ui.button>
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
