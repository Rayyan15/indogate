<div>
    <x-modal name="channel-cost-form" max-width="sm">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('pricing.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $costId ? __('pricing.channel_cost.form_title_edit') : __('pricing.channel_cost.form_title_create') }}</h2>
            </div>

            <x-ui.field :label="__('pricing.channel_cost.channel')" :error="$errors->first('channel')">
                <select wire:model="channel" class="admin-input" @if($costId) disabled @endif>
                    <option value="">—</option>
                    @foreach($channels as $c)
                        <option value="{{ $c->value }}">{{ __('pricing.channel_cost.'.$c->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('pricing.channel_cost.percent_fee')" :error="$errors->first('percent_fee')">
                <input type="number" min="0" max="10000" wire:model="percent_fee" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('pricing.channel_cost.flat_fee_minor')" :error="$errors->first('flat_fee_minor')">
                <input type="number" min="0" wire:model="flat_fee_minor" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('pricing.channel_cost.currency')" :error="$errors->first('currency')">
                <input type="text" wire:model="currency" maxlength="3" class="admin-input uppercase">
            </x-ui.field>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'channel-cost-form')">{{ __('pricing.common.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('pricing.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
