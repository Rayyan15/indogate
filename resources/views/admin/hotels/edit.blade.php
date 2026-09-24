<x-admin-layout>
    <x-slot name="header">{{ $hotel->name }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.hotels.eyebrow')" :title="$hotel->name" />

    <x-ui.button variant="ghost" :href="route('admin.hotels.index')" class="mb-6">{{ __('admin.hotels.back_to_hotels') }}</x-ui.button>

    <div class="max-w-2xl">
        <x-ui.panel>
            <form action="{{ route('admin.hotels.update', $hotel) }}" method="POST" class="space-y-6">
                @csrf @method('PATCH')
                <x-ui.field :label="__('admin.hotels.hotel_name')" :error="$errors->first('name')">
                    <input type="text" name="name" value="{{ old('name', $hotel->name) }}" class="admin-input" required>
                </x-ui.field>
                <x-ui.field :label="__('admin.hotels.city_location')" :error="$errors->first('location')">
                    <input type="text" name="location" value="{{ old('location', $hotel->location) }}" class="admin-input" required>
                </x-ui.field>
                <div class="grid grid-cols-2 gap-5">
                    <x-ui.field :label="__('admin.hotels.star_rating')" :error="$errors->first('star_rating')">
                        <select name="star_rating" class="admin-input" required>
                            @for($i = 1; $i <= 5; $i++)
                                <option value="{{ $i }}" {{ old('star_rating', $hotel->star_rating) == $i ? 'selected' : '' }}>{{ $i }} {{ __('admin.hotels.stars_suffix') }}</option>
                            @endfor
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.hotels.base_price_per_night')" :error="$errors->first('base_price_per_night')">
                        <input type="number" step="1000" min="0" name="base_price_per_night" value="{{ old('base_price_per_night', $hotel->base_price_per_night) }}" class="admin-input font-mono" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.hotels.currency')" :error="$errors->first('currency')">
                        <select name="currency" class="admin-input">
                            @foreach(\App\Domain\Pricing\Models\Currency::where('is_active', true)->orderBy('code')->pluck('code') as $code)
                                <option value="{{ $code }}" {{ old('currency', $hotel->currency) === $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
                <div class="flex items-center justify-between border-t border-neutral-200 pt-6">
                    <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" onsubmit="return confirm('{{ __('admin.hotels.delete_confirm_permanent') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.hotels.delete_hotel') }}</x-ui.button>
                    </form>
                    <div class="flex gap-3">
                        <x-ui.button variant="secondary" :href="route('admin.hotels.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                        <x-ui.button variant="primary" type="submit">{{ __('admin.hotels.update_hotel') }}</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
