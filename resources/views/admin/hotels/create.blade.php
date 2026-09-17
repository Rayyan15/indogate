<x-admin-layout>
    <x-slot name="header">{{ __('admin.hotels.create_title') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.hotels.eyebrow')" :title="__('admin.hotels.create_title')" />

    <x-ui.button variant="ghost" :href="route('admin.hotels.index')" class="mb-6">{{ __('admin.hotels.back_to_hotels') }}</x-ui.button>

    <div class="max-w-2xl">
        <x-ui.panel>
            <form action="{{ route('admin.hotels.store') }}" method="POST" class="space-y-6">
                @csrf
                <x-ui.field :label="__('admin.hotels.hotel_name')" :error="$errors->first('name')">
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('admin.hotels.hotel_name_placeholder') }}" class="admin-input" required>
                </x-ui.field>
                <x-ui.field :label="__('admin.hotels.city_location')" :error="$errors->first('location')">
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="{{ __('admin.hotels.location_placeholder') }}" class="admin-input" required>
                </x-ui.field>
                <div class="grid grid-cols-2 gap-5">
                    <x-ui.field :label="__('admin.hotels.star_rating')" :error="$errors->first('star_rating')">
                        <select name="star_rating" class="admin-input" required>
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('star_rating', 5) == $i ? 'selected' : '' }}>{{ $i }} {{ __('admin.hotels.stars_suffix') }}</option>
                            @endfor
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.hotels.base_price_per_night')" :error="$errors->first('base_price_per_night')">
                        <input type="number" step="1000" min="0" name="base_price_per_night" value="{{ old('base_price_per_night') }}" placeholder="{{ __('admin.hotels.base_price_placeholder') }}" class="admin-input font-mono" required>
                    </x-ui.field>
                </div>
                <div class="flex gap-3 border-t border-neutral-200 pt-6">
                    <x-ui.button variant="primary" type="submit">{{ __('admin.hotels.save_hotel') }}</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.hotels.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
