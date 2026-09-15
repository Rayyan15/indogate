<x-admin-layout>
    <x-slot name="header">Edit Pricing Rule</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.pricing.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Pricing Rules
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="admin-card p-8">
            <form action="{{ route('admin.pricing.update', $rule) }}" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="admin-label">Service Type</label>
                        <select name="service_type" class="admin-input" style="background: rgba(255,255,255,0.04);" required>
                            <option value="hotel" {{ old('service_type', $rule->service_type) == 'hotel' ? 'selected' : '' }}>🏨 Hotel</option>
                            <option value="flight" {{ old('service_type', $rule->service_type) == 'flight' ? 'selected' : '' }}>✈️ Flight</option>
                            <option value="driver" {{ old('service_type', $rule->service_type) == 'driver' ? 'selected' : '' }}>🚗 Driver</option>
                            <option value="all" {{ old('service_type', $rule->service_type) == 'all' ? 'selected' : '' }}>🌐 All Services</option>
                        </select>
                    </div>
                    <div>
                        <label class="admin-label">Markup Percentage (%)</label>
                        <input type="number" step="0.01" min="0" max="500" name="markup_percent" value="{{ old('markup_percent', $rule->markup_percent) }}" class="admin-input" required>
                    </div>
                    <div>
                        <label class="admin-label">Season Start Date</label>
                        <input type="date" name="season_start" value="{{ old('season_start', $rule->season_start->format('Y-m-d')) }}" class="admin-input" required>
                    </div>
                    <div>
                        <label class="admin-label">Season End Date</label>
                        <input type="date" name="season_end" value="{{ old('season_end', $rule->season_end->format('Y-m-d')) }}" class="admin-input" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="admin-label">Label / Tier <span class="text-slate-600 font-normal">(Optional)</span></label>
                        <input type="text" name="tier" value="{{ old('tier', $rule->tier) }}" placeholder="e.g. Haji Season, Ramadan, Eid" class="admin-input">
                    </div>
                </div>
                <div class="mt-8 flex justify-between items-center">
                    <form action="{{ route('admin.pricing.destroy', $rule) }}" method="POST" onsubmit="return confirm('Delete this pricing rule?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-danger">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Delete Rule
                        </button>
                    </form>
                    <div class="flex gap-3">
                        <a href="{{ route('admin.pricing.index') }}" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Update Rule</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
