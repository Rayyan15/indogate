<x-admin-layout>
    <x-slot name="header">{{ __('admin.drivers.create_title') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.drivers.eyebrow')" :title="__('admin.drivers.create_title')" />

    <x-ui.button variant="ghost" :href="route('admin.drivers.index')" class="mb-6">{{ __('admin.drivers.back_to_drivers') }}</x-ui.button>

    <div class="max-w-2xl">
        <x-ui.panel>
            <form action="{{ route('admin.drivers.store') }}" method="POST" class="space-y-6">
                @csrf
                <x-ui.field :label="__('admin.drivers.full_name')" :error="$errors->first('full_name')">
                    <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="{{ __('admin.drivers.full_name_placeholder') }}" class="admin-input" required>
                </x-ui.field>
                <div class="grid grid-cols-2 gap-5">
                    <x-ui.field :label="__('admin.drivers.gender')">
                        <select name="gender" class="admin-input">
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>{{ __('admin.drivers.male') }}</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>{{ __('admin.drivers.female') }}</option>
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.drivers.phone_number')">
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ __('admin.drivers.phone_placeholder') }}" class="admin-input">
                    </x-ui.field>
                </div>
                <x-ui.field :label="__('admin.drivers.daily_rate')" :error="$errors->first('daily_rate')">
                    <input type="number" step="1000" min="0" name="daily_rate" value="{{ old('daily_rate') }}" placeholder="{{ __('admin.drivers.daily_rate_placeholder') }}" class="admin-input font-mono" required>
                </x-ui.field>
                <label for="is_active" class="flex cursor-pointer items-center gap-3 rounded border border-neutral-200 bg-neutral-50 p-4">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 rounded accent-red-600">
                    <span class="text-sm font-medium text-neutral-700">{{ __('admin.drivers.mark_active') }}</span>
                </label>
                <div class="flex gap-3 border-t border-neutral-200 pt-6">
                    <x-ui.button variant="primary" type="submit">{{ __('admin.drivers.save_driver') }}</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.drivers.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
