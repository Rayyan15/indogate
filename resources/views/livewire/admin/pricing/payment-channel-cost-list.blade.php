<div>
    <x-ui.page-header :eyebrow="__('pricing.eyebrow')" :title="__('pricing.channel_cost.index_title')" :lede="__('pricing.channel_cost.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-channel-cost')">{{ __('pricing.common.add') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('pricing.channel_cost.channel') }}</x-ui.th>
            <x-ui.th numeric>{{ __('pricing.channel_cost.percent_fee') }}</x-ui.th>
            <x-ui.th numeric>{{ __('pricing.channel_cost.flat_fee_minor') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($costs as $cost)
            <x-ui.tr wire:key="cost-{{ $cost->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ __('pricing.channel_cost.'.$cost->channel->value) }}</x-ui.td>
                <x-ui.td numeric class="font-mono">{{ number_format($cost->percent_fee / 100, 2) }}%</x-ui.td>
                <x-ui.td numeric class="font-mono">{{ $cost->flat_fee_minor->amountMinor }} {{ $cost->flat_fee_minor->currency }}</x-ui.td>
                <x-ui.td>
                    <div class="flex items-center justify-center">
                        <x-ui.icon-button
                            variant="ghost"
                            type="button"
                            wire:click="$dispatch('edit-channel-cost', { costId: {{ $cost->id }} })"
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
            <tr><td colspan="4" class="p-0">
                <x-ui.empty :title="__('pricing.channel_cost.no_data')" text="">
                    <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-channel-cost')">{{ __('pricing.common.add') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    @livewire('admin.pricing.payment-channel-cost-form')
</div>
