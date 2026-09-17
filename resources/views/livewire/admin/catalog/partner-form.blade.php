<div>
    <x-modal name="partner-form" max-width="md">
        <form wire:submit="save" class="space-y-5 p-6">
            <div class="border-b border-neutral-200 pb-4">
                <x-ui.eyebrow>{{ __('catalog.partner.eyebrow') }}</x-ui.eyebrow>
                <h2 class="mt-1 font-display text-xl text-neutral-900">{{ $partnerId ? __('catalog.partner.form_edit_title') : __('catalog.partner.form_create_title') }}</h2>
            </div>

            @foreach(['en' => 'English', 'id' => 'Indonesia', 'ar' => 'العربية'] as $locale => $label)
                <x-ui.field :label="__('catalog.partner.name_locale', ['locale' => $label])" :error="$errors->first('name.'.$locale)">
                    <input type="text" wire:model="name.{{ $locale }}" class="admin-input" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
                </x-ui.field>
            @endforeach

            <x-ui.field :label="__('catalog.partner.type')" :error="$errors->first('type')">
                <select wire:model="type" class="admin-input">
                    <option value="">{{ __('catalog.partner.type_select') }}</option>
                    @foreach($types as $t)
                        <option value="{{ $t->value }}">{{ __('catalog.partner.'.$t->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <div class="grid grid-cols-2 gap-5">
                <x-ui.field :label="__('catalog.partner.city')">
                    <input type="text" wire:model="city" class="admin-input">
                </x-ui.field>
                <x-ui.field :label="__('catalog.partner.contact')">
                    <input type="text" wire:model="contact" class="admin-input">
                </x-ui.field>
            </div>

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input type="checkbox" wire:model="is_active" class="rounded accent-red-600">
                {{ __('catalog.partner.active') }}
            </label>

            <div class="flex justify-end gap-3 border-t border-neutral-200 pt-5">
                <x-ui.button variant="secondary" type="button" x-on:click="$dispatch('close-modal', 'partner-form')">{{ __('catalog.common.cancel') }}</x-ui.button>
                <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('catalog.item.save') }}</x-ui.button>
            </div>
        </form>
    </x-modal>
</div>
