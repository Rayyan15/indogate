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
            <x-ui.th>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($hotels as $hotel)
        <x-ui.tr>
            <x-ui.td class="font-medium text-neutral-900">{{ $hotel->name }}</x-ui.td>
            <x-ui.td>{{ $hotel->location }}</x-ui.td>
            <x-ui.td class="font-mono">{{ str_repeat('★', $hotel->star_rating) }}<span class="text-neutral-300">{{ str_repeat('★', 5 - $hotel->star_rating) }}</span></x-ui.td>
            <x-ui.td numeric>IDR {{ number_format($hotel->base_price_per_night) }}</x-ui.td>
            <x-ui.td>
                <div class="inline-flex items-center justify-center gap-1">
                    <x-ui.icon-button :href="route('admin.hotels.edit', $hotel)" :title="__('admin.common.edit')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </x-ui.icon-button>
                    <form action="{{ route('admin.hotels.destroy', $hotel) }}" method="POST" onsubmit="return confirm('{{ __('admin.hotels.delete_confirm') }}')" class="inline-flex">
                        @csrf @method('DELETE')
                        <x-ui.icon-button type="submit" :title="__('admin.common.delete')" class="hover:bg-rose-50 hover:text-red-600">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </x-ui.icon-button>
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
