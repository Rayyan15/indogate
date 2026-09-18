<div>
    <x-ui.page-header :eyebrow="__('pricing.eyebrow')" :title="__('pricing.season.index_title')" :lede="__('pricing.season.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-season')">{{ __('pricing.common.add') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('pricing.season.name') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.season.date_from') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.season.date_to') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.season.type') }}</x-ui.th>
            <x-ui.th numeric>{{ __('pricing.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($seasons as $season)
            <x-ui.tr wire:key="season-{{ $season->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ $season->name }}</x-ui.td>
                <x-ui.td>{{ $season->date_from->format('Y-m-d') }}</x-ui.td>
                <x-ui.td>{{ $season->date_to->format('Y-m-d') }}</x-ui.td>
                <x-ui.td>{{ __('pricing.season.'.$season->type->value) }}</x-ui.td>
                <x-ui.td numeric>
                    <x-ui.button variant="ghost" type="button" wire:click="$dispatch('edit-season', { seasonId: {{ $season->id }} })">{{ __('pricing.common.edit') }}</x-ui.button>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="5" class="p-0">
                <x-ui.empty :title="__('pricing.season.no_data')" text="">
                    <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-season')">{{ __('pricing.common.add') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $seasons->links() }}</div>

    @livewire('admin.pricing.season-form')
</div>
