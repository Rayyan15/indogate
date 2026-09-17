<x-admin-layout>
    <x-slot name="header">{{ __('admin.flights.create_title') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.flights.eyebrow')" :title="__('admin.flights.create_title')" />

    <x-ui.button variant="ghost" :href="route('admin.flights.index')" class="mb-6">{{ __('admin.flights.back_to_flights') }}</x-ui.button>

    <div class="max-w-3xl">
        <x-ui.panel>
            <form action="{{ route('admin.flights.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.field :label="__('admin.flights.airline_carrier')" :error="$errors->first('airline')">
                            <input type="text" name="airline" value="{{ old('airline') }}" placeholder="{{ __('admin.flights.airline_placeholder') }}" class="admin-input" required>
                        </x-ui.field>
                    </div>
                    <x-ui.field :label="__('admin.flights.origin')" :error="$errors->first('origin')">
                        <input type="text" name="origin" value="{{ old('origin') }}" placeholder="{{ __('admin.flights.origin_placeholder') }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.flights.destination')" :error="$errors->first('destination')">
                        <input type="text" name="destination" value="{{ old('destination') }}" placeholder="{{ __('admin.flights.destination_placeholder') }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.flights.departure_datetime')" :error="$errors->first('departure_at')">
                        <input type="datetime-local" name="departure_at" value="{{ old('departure_at') }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.flights.seat_quota')" :error="$errors->first('seat_quota')">
                        <input type="number" min="1" name="seat_quota" value="{{ old('seat_quota', 200) }}" placeholder="{{ __('admin.flights.seat_quota_placeholder') }}" class="admin-input font-mono" required>
                    </x-ui.field>
                    <div class="md:col-span-2">
                        <x-ui.field :label="__('admin.flights.base_price_idr')" :error="$errors->first('base_price')">
                            <input type="number" step="1000" min="0" name="base_price" value="{{ old('base_price') }}" placeholder="{{ __('admin.flights.base_price_placeholder') }}" class="admin-input font-mono" required>
                        </x-ui.field>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-neutral-200 pt-6">
                    <x-ui.button variant="primary" type="submit">{{ __('admin.flights.save_flight') }}</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.flights.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
