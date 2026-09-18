<div>
    <x-modal name="exchange-rate-form" max-width="sm">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('pricing.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ __('pricing.exchange_rate.form_title') }}</h2>
            </div>

            <x-ui.field :label="__('pricing.exchange_rate.currency')" :error="$errors->first('currency')">
                <input type="text" wire:model="currency" maxlength="3" class="admin-input uppercase" placeholder="USD">
            </x-ui.field>

            <x-ui.field :label="__('pricing.exchange_rate.rate')" :error="$errors->first('rate')">
                <input type="number" step="0.00000001" wire:model="rate" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('pricing.exchange_rate.effective_from')" :error="$errors->first('effective_from')">
                <input type="datetime-local" wire:model="effective_from" class="admin-input">
            </x-ui.field>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'exchange-rate-form')">{{ __('pricing.common.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('pricing.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
