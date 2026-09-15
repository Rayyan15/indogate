<x-admin-layout>
    <x-slot name="header">Edit Flight Route</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.flights.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Flights
        </a>
    </div>

    <div class="max-w-3xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.flights.update', $flight) }}" method="POST">
                @csrf @method('PATCH')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="admin-label">Airline / Carrier</label>
                        <input type="text" name="airline" value="{{ old('airline', $flight->airline) }}" class="admin-input" required>
                    </div>
                    <div>
                        <label class="admin-label">Origin</label>
                        <input type="text" name="origin" value="{{ old('origin', $flight->origin) }}" class="admin-input" required>
                    </div>
                    <div>
                        <label class="admin-label">Destination</label>
                        <input type="text" name="destination" value="{{ old('destination', $flight->destination) }}" class="admin-input" required>
                    </div>
                    <div>
                        <label class="admin-label">Departure Date & Time</label>
                        <input type="datetime-local" name="departure_at" value="{{ old('departure_at', $flight->departure_at->format('Y-m-d\TH:i')) }}" class="admin-input" required>
                    </div>
                    <div>
                        <label class="admin-label">Seat Quota</label>
                        <input type="number" min="1" name="seat_quota" value="{{ old('seat_quota', $flight->seat_quota) }}" class="admin-input" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="admin-label">Base Price (IDR)</label>
                        <input type="number" step="1000" name="base_price" value="{{ old('base_price', $flight->base_price) }}" class="admin-input" required>
                    </div>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" onsubmit="return confirm('Delete this flight?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Delete Flight
                        </button>
                    </form>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.flights.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Flight</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
