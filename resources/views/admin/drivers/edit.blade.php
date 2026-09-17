<x-admin-layout>
    <x-slot name="header">{{ $driver->full_name }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.drivers.eyebrow')" :title="$driver->full_name" />

    <x-ui.button variant="ghost" :href="route('admin.drivers.index')" class="mb-6">{{ __('admin.drivers.back_to_drivers') }}</x-ui.button>

    <div class="max-w-2xl">
        <x-ui.panel>
            <form action="{{ route('admin.drivers.update', $driver) }}" method="POST" class="space-y-6">
                @csrf @method('PATCH')
                <x-ui.field :label="__('admin.drivers.full_name')" :error="$errors->first('full_name')">
                    <input type="text" name="full_name" value="{{ old('full_name', $driver->full_name) }}" class="admin-input" required>
                </x-ui.field>
                <div class="grid grid-cols-2 gap-5">
                    <x-ui.field :label="__('admin.drivers.gender')">
                        <select name="gender" class="admin-input">
                            <option value="male" {{ old('gender', $driver->gender) == 'male' ? 'selected' : '' }}>{{ __('admin.drivers.male') }}</option>
                            <option value="female" {{ old('gender', $driver->gender) == 'female' ? 'selected' : '' }}>{{ __('admin.drivers.female') }}</option>
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.drivers.phone_number')">
                        <input type="text" name="phone" value="{{ old('phone', $driver->phone) }}" class="admin-input">
                    </x-ui.field>
                </div>
                <x-ui.field :label="__('admin.drivers.daily_rate')" :error="$errors->first('daily_rate')">
                    <input type="number" step="1000" min="0" name="daily_rate" value="{{ old('daily_rate', $driver->daily_rate) }}" class="admin-input font-mono" required>
                </x-ui.field>
                <label for="is_active" class="flex cursor-pointer items-center gap-3 rounded border border-neutral-200 bg-neutral-50 p-4">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $driver->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded accent-red-600">
                    <span class="text-sm font-medium text-neutral-700">{{ __('admin.drivers.mark_active') }}</span>
                </label>
                <div class="flex items-center justify-between border-t border-neutral-200 pt-6">
                    <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" onsubmit="return confirm('{{ __('admin.drivers.delete_confirm_permanent') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.drivers.delete_driver') }}</x-ui.button>
                    </form>
                    <div class="flex gap-3">
                        <x-ui.button variant="secondary" :href="route('admin.drivers.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                        <x-ui.button variant="primary" type="submit">{{ __('admin.drivers.update_driver') }}</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
