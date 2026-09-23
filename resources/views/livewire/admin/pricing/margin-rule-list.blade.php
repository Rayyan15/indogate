<div>
    <x-ui.page-header :eyebrow="__('pricing.eyebrow')" :title="__('pricing.margin_rule.index_title')" :lede="__('pricing.margin_rule.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-margin-rule')">{{ __('pricing.common.add') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('pricing.margin_rule.product_type') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.margin_rule.season_type') }}</x-ui.th>
            <x-ui.th numeric>{{ __('pricing.margin_rule.margin_percent') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.common.status') }}</x-ui.th>
            <x-ui.th>{{ __('pricing.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse ($rules as $rule)
            <x-ui.tr wire:key="rule-{{ $rule->id }}">
                <x-ui.td class="font-medium text-neutral-900">{{ __('catalog.item.'.$rule->product_type->value) }}</x-ui.td>
                <x-ui.td>{{ $rule->season_type ? __('pricing.season.'.$rule->season_type->value) : __('pricing.common.any_season') }}</x-ui.td>
                <x-ui.td numeric class="font-mono">{{ number_format($rule->margin_percent / 100, 2) }}%</x-ui.td>
                <x-ui.td><x-ui.status :status="$rule->is_active ? 'paid' : 'cancelled'">{{ $rule->is_active ? __('pricing.common.active') : __('pricing.common.inactive') }}</x-ui.status></x-ui.td>
                <x-ui.td>
                    <div class="flex items-center justify-center">
                        <x-ui.icon-button
                            variant="ghost"
                            type="button"
                            wire:click="$dispatch('edit-margin-rule', { ruleId: {{ $rule->id }} })"
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
                <x-ui.empty :title="__('pricing.margin_rule.no_data')" text="">
                    <x-ui.button variant="primary" type="button" wire:click="$dispatch('create-margin-rule')">{{ __('pricing.common.add') }}</x-ui.button>
                </x-ui.empty>
            </td></tr>
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $rules->links() }}</div>

    @livewire('admin.pricing.margin-rule-form')
</div>
