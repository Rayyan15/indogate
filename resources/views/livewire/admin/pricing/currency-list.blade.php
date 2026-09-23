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
            <x-ui.th>{{ __('pricing.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($currencies as $currency)
            <x-ui.tr wire:key="currency-{{ $currency->code }}">
                <x-ui.td class="font-mono font-medium text-neutral-900">{{ $currency->code }}</x-ui.td>
                <x-ui.td>{{ $currency->symbol }}</x-ui.td>
                <x-ui.td>{{ $currency->decimal_places }}</x-ui.td>
                <x-ui.td><x-ui.status :status="$currency->is_active ? 'paid' : 'cancelled'">{{ $currency->is_active ? __('pricing.common.active') : __('pricing.common.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td>
                    <div class="flex items-center justify-center">
                        <x-ui.icon-button
                            variant="ghost"
                            type="button"
                            wire:click="$dispatch('edit-currency', { code: '{{ $currency->code }}' })"
                            :title="__('pricing.common.edit')"
                            :aria-label="__('pricing.common.edit')"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </x-ui.icon-button>
                    </div>
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
