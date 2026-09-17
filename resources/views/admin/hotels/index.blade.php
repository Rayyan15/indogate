<x-admin-layout>
    <x-slot name="header">{{ __('nav.hotels') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.hotels.eyebrow')" :title="__('admin.hotels.index_title')" :lede="__('admin.hotels.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('admin.hotels.create')">{{ __('admin.hotels.add_hotel') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.hotels.hotel_name') }}</x-ui.th>
            <x-ui.th>{{ __('admin.hotels.location') }}</x-ui.th>
            <x-ui.th>{{ __('admin.hotels.stars') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.hotels.base_price_night') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($hotels as $hotel)
        <x-ui.tr>
            <x-ui.td class="font-medium text-neutral-900">{{ $hotel->name }}</x-ui.td>
            <x-ui.td>{{ $hotel->location }}</x-ui.td>
            <x-ui.td class="font-mono">{{ str_repeat('★', $hotel->star_rating) }}<span class="text-neutral-300">{{ str_repeat('★', 5 - $hotel->star_rating) }}</span></x-ui.td>
            <x-ui.td numeric>IDR {{ number_format($hotel->base_price_per_night) }}</x-ui.td>
            <x-ui.td numeric>
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="ghost" :href="route('admin.hotels.edit', $hotel)">{{ __('admin.common.edit') }}</x-ui.button>
                    <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" onsubmit="return confirm('{{ __('admin.hotels.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.common.delete') }}</x-ui.button>
                    </form>
                </div>
            </x-ui.td>
        </x-ui.tr>
        @empty
        <tr><td colspan="5" class="p-0">
            <x-ui.empty :title="__('admin.hotels.no_hotels_yet')" :text="__('admin.hotels.no_hotels_text')">
                <x-ui.button variant="primary" :href="route('admin.hotels.create')">{{ __('admin.hotels.add_first') }}</x-ui.button>
            </x-ui.empty>
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $hotels->links() }}</div>
</x-admin-layout>
