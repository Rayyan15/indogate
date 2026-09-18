<div>
    <x-modal name="season-form" max-width="sm">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('pricing.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $seasonId ? __('pricing.season.form_title_edit') : __('pricing.season.form_title_create') }}</h2>
            </div>

            <x-ui.field :label="__('pricing.season.name')" :error="$errors->first('name')">
                <input type="text" wire:model="name" class="admin-input">
            </x-ui.field>

            <div class="grid grid-cols-2 gap-5">
                <x-ui.field :label="__('pricing.season.date_from')" :error="$errors->first('date_from')">
                    <input type="date" wire:model="date_from" class="admin-input">
                </x-ui.field>
                <x-ui.field :label="__('pricing.season.date_to')" :error="$errors->first('date_to')">
                    <input type="date" wire:model="date_to" class="admin-input">
                </x-ui.field>
            </div>

            <x-ui.field :label="__('pricing.season.type')" :error="$errors->first('type')">
                <select wire:model="type" class="admin-input">
                    <option value="">—</option>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}">{{ __('pricing.season.'.$t->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'season-form')">{{ __('pricing.common.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('pricing.common.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
