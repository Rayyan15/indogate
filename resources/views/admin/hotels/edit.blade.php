<x-admin-layout>
    <x-slot name="header">Edit Hotel</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.hotels.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Hotels
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.hotels.update', $hotel) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="space-y-6">
                    <div>
                        <label class="admin-label">Hotel Name</label>
                        <input type="text" name="name" value="{{ old('name', $hotel->name) }}" class="admin-input" required>
                        @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">City / Location</label>
                        <input type="text" name="location" value="{{ old('location', $hotel->location) }}" class="admin-input" required>
                        @error('location') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="admin-label">Star Rating</label>
                            <select name="star_rating" class="admin-input" required>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ old('star_rating', $hotel->star_rating) == $i ? 'selected' : '' }}>{{ $i }} Stars</option>
                                @endfor
                            </select>
                            @error('star_rating') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="admin-label">Base Price per Night (IDR)</label>
                            <input type="number" step="1000" min="0" name="base_price_per_night" value="{{ old('base_price_per_night', $hotel->base_price_per_night) }}" class="admin-input" required>
                            @error('base_price_per_night') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" onsubmit="return confirm('Delete this hotel permanently?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Delete Hotel
                        </button>
                    </form>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.hotels.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Hotel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
