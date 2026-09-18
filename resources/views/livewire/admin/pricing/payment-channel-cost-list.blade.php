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
            <x-ui.th numeric>{{ __('pricing.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($costs as $cost)
            <x-ui.tr wire:key="cost-{{ $cost->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ __('pricing.channel_cost.'.$cost->channel->value) }}</x-ui.td>
                <x-ui.td numeric class="font-mono">{{ number_format($cost->percent_fee / 100, 2) }}%</x-ui.td>
                <x-ui.td numeric class="font-mono">{{ $cost->flat_fee_minor->amountMinor }} {{ $cost->flat_fee_minor->currency }}</x-ui.td>
                <x-ui.td numeric>
                    <x-ui.button variant="ghost" type="button" wire:click="$dispatch('edit-channel-cost', { costId: {{ $cost->id }} })">{{ __('pricing.common.edit') }}</x-ui.button>
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
