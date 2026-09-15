<x-admin-layout>
    <x-slot name="header">Flights</x-slot>

    <div class="flex justify-between items-center mb-6">
        <p class="text-slate-500 text-sm">Manage flight routes and schedules.</p>
        <a href="{{ route('admin.flights.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add Flight
        </a>
    </div>

    <div class="admin-card overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="text-left">Airline</th>
                    <th class="text-left">Route</th>
                    <th class="text-left">Departure</th>
                    <th class="text-left">Base Price</th>
                    <th class="text-left">Quota</th>
                    <th class="text-right pr-5">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($flights as $flight)
                <tr>
                    <td class="font-medium">{{ $flight->airline }}</td>
                    <td>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-white">{{ $flight->origin }}</span>
                            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            <span class="font-semibold text-white">{{ $flight->destination }}</span>
                        </div>
                    </td>
                    <td>{{ $flight->departure_at->format('d M Y, H:i') }}</td>
                    <td style="color: #D4AF37; font-weight: 600;">IDR {{ number_format($flight->base_price) }}</td>
                    <td>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background: rgba(99,102,241,0.1); color: #a5b4fc;">
                            {{ $flight->seat_quota }} seats
                        </span>
                    </td>
                    <td class="text-right pr-5">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.flights.edit', $flight) }}" class="btn-edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Edit
                            </a>
                            <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" onsubmit="return confirm('Delete this flight?')">
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
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p>No flight routes added yet.</p>
                            <a href="{{ route('admin.flights.create') }}" class="btn-primary text-xs">Add First Flight</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-5 py-4 border-t border-white/5">{{ $flights->links() }}</div>
    </div>
</x-admin-layout>
