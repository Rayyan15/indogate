<div>
    <x-modal name="margin-rule-form" max-width="sm">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('pricing.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $ruleId ? __('pricing.margin_rule.form_title_edit') : __('pricing.margin_rule.form_title_create') }}</h2>
            </div>

            <x-ui.field :label="__('pricing.margin_rule.product_type')" :error="$errors->first('product_type')">
                <select wire:model="product_type" class="admin-input">
                    <option value="">—</option>
                    @foreach($productTypes as $t)
                        <option value="{{ $t->value }}">{{ __('catalog.item.'.$t->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('pricing.margin_rule.season_type')" :error="$errors->first('season_type')" :hint="__('pricing.common.any_season')">
                <select wire:model="season_type" class="admin-input">
                    <option value="">{{ __('pricing.common.any_season') }}</option>
                    @foreach($seasonTypes as $t)
                        <option value="{{ $t->value }}">{{ __('pricing.season.'.$t->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('pricing.margin_rule.margin_percent')" :error="$errors->first('margin_percent')">
                <input type="number" min="0" max="100000" wire:model.live="margin_percent" class="admin-input">
            </x-ui.field>

            <p class="text-xs text-neutral-500">{{ __('pricing.margin_rule.preview', ['sell' => number_format($this->previewSell)]) }}</p>

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input type="checkbox" wire:model="is_active" class="rounded accent-red-600">
                {{ __('pricing.common.active') }}
            </label>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'margin-rule-form')">{{ __('pricing.common.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('pricing.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
