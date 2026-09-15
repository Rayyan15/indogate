<x-admin-layout>
    <x-slot name="header">Drivers</x-slot>

    <div class="flex justify-between items-center mb-6">
        <p class="text-slate-500 text-sm">Manage chauffeurs and transportation services.</p>
        <a href="{{ route('admin.drivers.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Driver
        </a>
    </div>

    <div class="admin-card">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Name</th>
                    <th class="text-left">Gender</th>
                    <th class="text-left">Phone</th>
                    <th class="text-left">Status</th>
                    <th class="text-right pr-5">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($drivers as $driver)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm shrink-0" style="background: rgba(212,175,55,0.1); color: #D4AF37;">
                                {{ substr($driver->full_name, 0, 1) }}
                            </div>
                            <span class="font-medium text-white">{{ $driver->full_name }}</span>
                        </div>
                    </td>
                    <td class="capitalize">{{ $driver->gender ?? '-' }}</td>
                    <td>{{ $driver->phone ?? '-' }}</td>
                    <td>
                        @if($driver->is_active)
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(16,185,129,0.1); color: #34d399;">● Active</span>
                        @else
                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(239,68,68,0.1); color: #f87171;">● Inactive</span>
                        @endif
                    </td>
                    <td class="text-right pr-5">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.drivers.edit', $driver) }}" class="btn-edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" onsubmit="return confirm('Delete this driver?')">
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
                    <td colspan="5" class="py-16 text-center text-slate-600 text-sm">
                        <div class="flex flex-col items-center gap-3">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <p>No drivers added yet.</p>
                            <a href="{{ route('admin.drivers.create') }}" class="btn-primary text-xs">Add First Driver</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-white/5">{{ $drivers->links() }}</div>
    </div>
</x-admin-layout>
