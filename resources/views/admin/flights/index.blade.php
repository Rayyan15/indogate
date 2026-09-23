<x-admin-layout>
    <x-slot name="header">{{ __('nav.flights') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.flights.eyebrow')" :title="__('admin.flights.index_title')" :lede="__('admin.flights.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('admin.flights.create')">{{ __('admin.flights.add_flight') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.flights.airline') }}</x-ui.th>
            <x-ui.th>{{ __('admin.flights.route') }}</x-ui.th>
            <x-ui.th>{{ __('admin.flights.departure') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.flights.base_price') }}</x-ui.th>
            <x-ui.th>{{ __('admin.flights.quota') }}</x-ui.th>
            <x-ui.th>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($flights as $flight)
        <x-ui.tr>
            <x-ui.td class="font-medium text-neutral-900">{{ $flight->airline }}</x-ui.td>
            <x-ui.td>
                <span class="inline-flex items-center gap-1.5 font-medium text-neutral-900">{{ $flight->origin }} <span class="text-neutral-300">→</span> {{ $flight->destination }}</span>
            </x-ui.td>
            <x-ui.td class="font-mono">{{ $flight->departure_at->format('d M Y, H:i') }}</x-ui.td>
            <x-ui.td numeric>IDR {{ number_format($flight->base_price) }}</x-ui.td>
            <x-ui.td><x-ui.status status="in_progress">{{ $flight->seat_quota }} {{ __('admin.flights.seats') }}</x-ui.status></x-ui.td>
            <x-ui.td>
                <div class="inline-flex items-center justify-center gap-1">
                    <x-ui.icon-button :href="route('admin.flights.edit', $flight)" :title="__('admin.common.edit')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </x-ui.icon-button>
                    <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" onsubmit="return confirm('{{ __('admin.flights.delete_confirm') }}')" class="inline-flex">
                        @csrf @method('DELETE')
                        <x-ui.icon-button type="submit" :title="__('admin.common.delete')" class="hover:bg-rose-50 hover:text-red-600">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </x-ui.icon-button>
                    </form>
                </div>
            </x-ui.td>
        </x-ui.tr>
        @empty
        <tr><td colspan="6" class="p-0">
            <x-ui.empty :title="__('admin.flights.no_flights_yet')" :text="__('admin.flights.no_flights_text')">
                <x-ui.button variant="primary" :href="route('admin.flights.create')">{{ __('admin.flights.add_first') }}</x-ui.button>
            </x-ui.empty>
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $flights->links() }}</div>
</x-admin-layout>
