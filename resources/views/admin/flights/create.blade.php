<x-admin-layout>
    <x-slot name="header">Add New Flight Route</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.flights.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Flights
        </a>
    </div>

    <div class="max-w-3xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.flights.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="admin-label">Airline / Carrier</label>
                        <input type="text" name="airline" value="{{ old('airline') }}" placeholder="e.g. Garuda Indonesia" class="admin-input" required>
                        @error('airline') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Origin City / Airport Code</label>
                        <input type="text" name="origin" value="{{ old('origin') }}" placeholder="e.g. CGK (Jakarta)" class="admin-input" required>
                        @error('origin') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Destination City / Airport Code</label>
                        <input type="text" name="destination" value="{{ old('destination') }}" placeholder="e.g. JED (Jeddah)" class="admin-input" required>
                        @error('destination') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Departure Date & Time</label>
                        <input type="datetime-local" name="departure_at" value="{{ old('departure_at') }}" class="admin-input" required>
                        @error('departure_at') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Seat Quota</label>
                        <input type="number" min="1" name="seat_quota" value="{{ old('seat_quota', 200) }}" placeholder="e.g. 200" class="admin-input" required>
                        @error('seat_quota') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="admin-label">Base Price (IDR)</label>
                        <input type="number" step="1000" min="0" name="base_price" value="{{ old('base_price') }}" placeholder="e.g. 5000000" class="admin-input" required>
                        @error('base_price') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="submit" class="btn-primary">Save Flight Route</button>
                    <a href="{{ route('admin.flights.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
