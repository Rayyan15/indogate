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
            <x-ui.th numeric>{{ __('admin.common.actions') }}</x-ui.th>
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
            <x-ui.td numeric>
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="ghost" :href="route('admin.flights.edit', $flight)">{{ __('admin.common.edit') }}</x-ui.button>
                    <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" onsubmit="return confirm('{{ __('admin.flights.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.common.delete') }}</x-ui.button>
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
