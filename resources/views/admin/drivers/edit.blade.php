<x-admin-layout>
    <x-slot name="header">Edit Driver</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.drivers.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Drivers
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.drivers.update', $driver) }}" method="POST">
                @csrf @method('PATCH')
                <div class="space-y-6">
                    <div>
                        <label class="admin-label">Full Name</label>
                        <input type="text" name="full_name" value="{{ old('full_name', $driver->full_name) }}" class="admin-input" required>
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="admin-label">Gender</label>
                            <select name="gender" class="admin-input" style="background: rgba(255,255,255,0.04);">
                                <option value="male" {{ old('gender', $driver->gender) == 'male' ? 'selected' : '' }}>Male</option>
                                <option value="female" {{ old('gender', $driver->gender) == 'female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="admin-label">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $driver->phone) }}" class="admin-input">
                        </div>
                    </div>
                    <div>
                        <label class="admin-label">Daily Rate (IDR)</label>
                        <input type="number" step="1000" min="0" name="daily_rate" value="{{ old('daily_rate', $driver->daily_rate) }}" class="admin-input" required>
                    </div>
                    <div class="flex items-center gap-3 p-4 rounded-xl" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07);">
                        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $driver->is_active) ? 'checked' : '' }}
                            class="w-4 h-4 rounded" style="accent-color: #D4AF37;">
                        <label for="is_active" class="text-sm font-medium text-slate-300 cursor-pointer">
                            Mark as Active (available for booking)
                        </label>
                    </div>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" onsubmit="return confirm('Delete this driver permanently?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Delete Driver
                        </button>
                    </form>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.drivers.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Driver</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
