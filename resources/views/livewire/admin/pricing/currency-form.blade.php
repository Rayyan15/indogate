<div>
    <x-modal name="currency-form" max-width="sm">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('pricing.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $originalCode ? __('pricing.currency.form_title_edit') : __('pricing.currency.form_title') }}</h2>
            </div>

            <x-ui.field :label="__('pricing.currency.code')" :error="$errors->first('code')">
                <input type="text" wire:model="code" maxlength="3" class="admin-input uppercase" @if($originalCode) disabled @endif>
            </x-ui.field>

            <x-ui.field :label="__('pricing.currency.symbol')" :error="$errors->first('symbol')">
                <input type="text" wire:model="symbol" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('pricing.currency.decimal_places')" :error="$errors->first('decimal_places')">
                <input type="number" min="0" max="4" wire:model="decimal_places" class="admin-input">
            </x-ui.field>

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input type="checkbox" wire:model="is_active" class="rounded accent-red-600">
                {{ __('pricing.common.active') }}
            </label>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'currency-form')">{{ __('pricing.common.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('pricing.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
