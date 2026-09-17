<x-admin-layout>
    <x-slot name="header">{{ $flight->airline }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.flights.eyebrow')" :title="$flight->airline . ' — ' . $flight->origin . ' → ' . $flight->destination" />

    <x-ui.button variant="ghost" :href="route('admin.flights.index')" class="mb-6">{{ __('admin.flights.back_to_flights') }}</x-ui.button>

    <div class="max-w-3xl">
        <x-ui.panel>
            <form action="{{ route('admin.flights.update', $flight) }}" method="POST" class="space-y-6">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <x-ui.field :label="__('admin.flights.airline_carrier')">
                            <input type="text" name="airline" value="{{ old('airline', $flight->airline) }}" class="admin-input" required>
                        </x-ui.field>
                    </div>
                    <x-ui.field :label="__('admin.flights.origin')">
                        <input type="text" name="origin" value="{{ old('origin', $flight->origin) }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.flights.destination')">
                        <input type="text" name="destination" value="{{ old('destination', $flight->destination) }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.flights.departure_datetime')">
                        <input type="datetime-local" name="departure_at" value="{{ old('departure_at', $flight->departure_at->format('Y-m-d\TH:i')) }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.flights.seat_quota')">
                        <input type="number" min="1" name="seat_quota" value="{{ old('seat_quota', $flight->seat_quota) }}" class="admin-input font-mono" required>
                    </x-ui.field>
                    <div class="md:col-span-2">
                        <x-ui.field :label="__('admin.flights.base_price_idr')">
                            <input type="number" step="1000" name="base_price" value="{{ old('base_price', $flight->base_price) }}" class="admin-input font-mono" required>
                        </x-ui.field>
                    </div>
                </div>
                <div class="flex items-center justify-between border-t border-neutral-200 pt-6">
                    <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" onsubmit="return confirm('{{ __('admin.flights.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.flights.delete_flight') }}</x-ui.button>
                    </form>
                    <div class="flex gap-3">
                        <x-ui.button variant="secondary" :href="route('admin.flights.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                        <x-ui.button variant="primary" type="submit">{{ __('admin.flights.update_flight') }}</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
