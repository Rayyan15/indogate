<div>
    <x-ui.page-header :eyebrow="__('pricing.eyebrow')" :title="__('pricing.exchange_rate.index_title')" :lede="__('pricing.exchange_rate.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-exchange-rate')">{{ __('pricing.common.add') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('pricing.exchange_rate.currency') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.exchange_rate.rate') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.exchange_rate.effective_from') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.exchange_rate.source') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.exchange_rate.created_by') }}</x-ui.th>
        </x-slot>
        @forelse ($rates as $rate)
            <x-ui.tr wire:key="rate-{{ $rate->id }}">
                <x-ui.td class="font-mono font-medium text-neutral-900">{{ $rate->currency }}</x-ui.td>
                <x-ui.td class="font-mono">{{ $rate->rate }}</x-ui.td>
                <x-ui.td>{{ \App\Support\Branch\CurrentBranch::local($rate->effective_from)->format('Y-m-d H:i') }}</x-ui.td>
                <x-ui.td>
                    <span class="rounded-full border border-neutral-200 px-2 py-0.5 text-[11px] {{ $rate->isAuto() ? 'text-neutral-600' : 'text-amber-700' }}">{{ $rate->isAuto() ? __('pricing.exchange_rate.source_auto') : __('pricing.exchange_rate.source_manual') }}</span>
                    @if($rate->isPinned())<span class="ms-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] text-amber-800">{{ __('pricing.exchange_rate.pinned_badge', ['date' => \App\Support\Branch\CurrentBranch::local($rate->pinned_until)->format('Y-m-d H:i')]) }}</span>@endif
                </x-ui.td>
                <x-ui.td>{{ $rate->creator?->name ?? __('pricing.exchange_rate.system') }}</x-ui.td>
            </x-ui.tr>
        @empty
            <tr><td colspan="5" class="p-0">
                <x-ui.empty :title="__('pricing.exchange_rate.no_data')" text="">
                    <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-exchange-rate')">{{ __('pricing.common.add') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $rates->links() }}</div>

    @livewire('admin.pricing.exchange-rate-form')
</div>
