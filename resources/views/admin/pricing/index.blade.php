<x-admin-layout>
    <x-slot name="header">Dynamic Pricing</x-slot>

    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-slate-500 text-sm">Manage seasonal markup rules for all service categories.</p>
        </div>
        <a href="{{ route('admin.pricing.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Pricing Rule
        </a>
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Service Type</th>
                    <th class="text-left">Season Start</th>
                    <th class="text-left">Season End</th>
                    <th class="text-left">Markup</th>
                    <th class="text-left">Status</th>
                    <th class="text-right pr-5">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rules as $rule)
                @php
                    $isActive = now()->between($rule->season_start, $rule->season_end);
                @endphp
                <tr>
                    <td>
                        <span class="capitalize font-medium text-white">{{ $rule->service_type }}</span>
                    </td>
                    <td>{{ $rule->season_start->format('d M Y') }}</td>
                    <td>{{ $rule->season_end->format('d M Y') }}</td>
                    <td>
                        <span class="font-bold text-base" style="color: #D4AF37;">+{{ $rule->markup_percent }}%</span>
                    </td>
                    <td>
                        @if($isActive)
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">● Active Now</span>
                        @else
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-800 text-slate-500">Scheduled</span>
                        @endif
                    </td>
                    <td class="text-right pr-5">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.pricing.edit', $rule) }}" class="btn-edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.pricing.destroy', $rule) }}" method="POST" onsubmit="return confirm('Delete this pricing rule?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-danger">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-16 text-center text-slate-600 text-sm">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            <p>No pricing rules configured yet.</p>
                            <a href="{{ route('admin.pricing.create') }}" class="btn-primary text-xs">Create First Rule</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-white/5">{{ $rules->links() }}</div>
    </div>
</x-admin-layout>
