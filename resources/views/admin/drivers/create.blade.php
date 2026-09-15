<x-admin-layout>
    <x-slot name="header">Add New Driver</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.drivers.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Drivers
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.drivers.store') }}" method="POST">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="admin-label">Full Name</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="e.g. Budi Santoso" class="admin-input" required>
                        @error('full_name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="admin-label">Gender</label>
                            <select name="gender" class="admin-input" style="background: rgba(255,255,255,0.04);">
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="e.g. 08123456789" class="admin-input">
                        </div>
                    </div>
                    <div>
                        <label class="admin-label">Daily Rate (IDR)</label>
                        <input type="number" step="1000" min="0" name="daily_rate" value="{{ old('daily_rate') }}" placeholder="e.g. 500000" class="admin-input" required>
                        @error('daily_rate') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3 p-4 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                            class="w-4 h-4 rounded" style="accent-color: #D4AF37;">
                        <label for="is_active" class="text-sm font-medium text-slate-300 cursor-pointer">
                            Mark as Active (available for booking)
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="submit" class="btn-primary">Save Driver</button>
                    <a href="{{ route('admin.drivers.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
