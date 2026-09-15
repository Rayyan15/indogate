<x-admin-layout>
    <x-slot name="header">Create Pricing Rule</x-slot>

    <div class="mb-6">
        <a href="{{ route('admin.pricing.index') }}" class="btn-secondary text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Back to Pricing Rules
        </a>
    </div>

    <div class="max-w-2xl">
        <div class="admin-card p-8">
            <div class="mb-6 p-4 rounded-xl" style="background: rgba(212,175,55,0.06); border: 1px solid rgba(212,175,55,0.15);">
                <p class="text-sm" style="color: #D4AF37;">
                    <span class="font-bold">Note:</span> A pricing rule applies a percentage markup to the base price of the selected service type during the defined season period.
                </p>
            </div>
            
            <form action="{{ route('admin.pricing.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="admin-label">Service Type</label>
                        <select name="service_type" class="admin-input" style="background: rgba(255,255,255,0.04);" required>
                            <option value="hotel" {{ old('service_type') == 'hotel' ? 'selected' : '' }}>🏨 Hotel</option>
                            <option value="flight" {{ old('service_type') == 'flight' ? 'selected' : '' }}>✈️ Flight</option>
                            <option value="driver" {{ old('service_type') == 'driver' ? 'selected' : '' }}>🚗 Driver</option>
                            <option value="all" {{ old('service_type') == 'all' ? 'selected' : '' }}>🌐 All Services</option>
                        </select>
                        @error('service_type') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Markup Percentage (%)</label>
                        <input type="number" step="0.01" min="0" max="500" name="markup_percent" value="{{ old('markup_percent') }}" placeholder="e.g. 25 (for 25%)" class="admin-input" required>
                        @error('markup_percent') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Season Start Date</label>
                        <input type="date" name="season_start" value="{{ old('season_start') }}" class="admin-input" required>
                        @error('season_start') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="admin-label">Season End Date</label>
                        <input type="date" name="season_end" value="{{ old('season_end') }}" class="admin-input" required>
                        @error('season_end') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="admin-label">Label / Tier <span class="text-slate-600 font-normal">(Optional)</span></label>
                        <input type="text" name="tier" value="{{ old('tier') }}" placeholder="e.g. Haji Season, Ramadan, Eid" class="admin-input">
                    </div>
                </div>
                <div class="mt-8 flex gap-3">
                    <button type="submit" class="btn-primary">Save Pricing Rule</button>
                    <a href="{{ route('admin.pricing.index') }}" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
