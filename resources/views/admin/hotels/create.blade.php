<x-admin-layout>
    <x-slot name="header">Add New Hotel</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.hotels.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Hotels
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.hotels.store') }}" method="POST">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="admin-label">Hotel Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" placeholder="e.g. Makkah Grand Hilton" class="admin-input" required>
                        @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">City / Location</label>
                        <input type="text" name="location" id="location" value="{{ old('location') }}" placeholder="e.g. Makkah, Saudi Arabia" class="admin-input" required>
                        @error('location') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="admin-label">Star Rating</label>
                            <select name="star_rating" class="admin-input" required>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('star_rating', 5) == $i ? 'selected' : '' }}>{{ $i }} Stars</option>
                                @endfor
                            </select>
                            @error('star_rating') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">Base Price per Night (IDR)</label>
                            <input type="number" step="1000" min="0" name="base_price_per_night" id="base_price_per_night" value="{{ old('base_price_per_night') }}" placeholder="e.g. 1500000" class="admin-input" required>
                            @error('base_price_per_night') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="submit" class="btn-primary">Save Hotel</button>
                    <a href="{{ route('admin.hotels.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
