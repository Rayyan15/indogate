<div>
    <x-ui.page-header :eyebrow="__('pricing.eyebrow')" :title="__('pricing.currency.index_title')" :lede="__('pricing.currency.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-currency')">{{ __('pricing.common.add') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('pricing.currency.code') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.currency.symbol') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.currency.decimal_places') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.common.status') }}</x-ui.th>
            <x-ui.th numeric>{{ __('pricing.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($currencies as $currency)
            <x-ui.tr wire:key="currency-{{ $currency->code }}">
                <x-ui.td class="font-mono font-medium text-neutral-900">{{ $currency->code }}</x-ui.td>
                <x-ui.td>{{ $currency->symbol }}</x-ui.td>
                <x-ui.td>{{ $currency->decimal_places }}</x-ui.td>
                <x-ui.td><x-ui.status :status="$currency->is_active ? 'paid' : 'cancelled'">{{ $currency->is_active ? __('pricing.common.active') : __('pricing.common.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td numeric>
                    <x-ui.button variant="ghost" type="button" wire:click="$dispatch('edit-currency', { code: '{{ $currency->code }}' })">{{ __('pricing.common.edit') }}</x-ui.button>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="5" class="p-0">
                <x-ui.empty :title="__('pricing.currency.no_data')" text="">
                    <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-currency')">{{ __('pricing.common.add') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    @livewire('admin.pricing.currency-form')
</div>
