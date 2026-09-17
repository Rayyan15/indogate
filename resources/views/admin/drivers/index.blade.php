<x-admin-layout>
    <x-slot name="header">{{ __('nav.drivers') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.drivers.eyebrow')" :title="__('admin.drivers.index_title')" :lede="__('admin.drivers.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('admin.drivers.create')">{{ __('admin.drivers.add_driver') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.drivers.name') }}</x-ui.th>
            <x-ui.th>{{ __('admin.drivers.gender') }}</x-ui.th>
            <x-ui.th>{{ __('admin.drivers.phone') }}</x-ui.th>
            <x-ui.th>{{ __('admin.common.status') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($drivers as $driver)
        <x-ui.tr>
            <x-ui.td>
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded border border-neutral-200 bg-neutral-50 font-mono text-xs font-semibold text-neutral-600">{{ substr($driver->full_name, 0, 1) }}</span>
                    <span class="font-medium text-neutral-900">{{ $driver->full_name }}</span>
                </div>
            </x-ui.td>
            <x-ui.td class="capitalize">{{ $driver->gender === 'male' ? __('admin.drivers.male') : ($driver->gender === 'female' ? __('admin.drivers.female') : '-') }}</x-ui.td>
            <x-ui.td class="font-mono">{{ $driver->phone ?? '-' }}</x-ui.td>
            <x-ui.td>
                <x-ui.status :status="$driver->is_active ? 'paid' : 'cancelled'">{{ $driver->is_active ? __('admin.drivers.active') : __('admin.drivers.inactive') }}</x-ui.status>
            </x-ui.td>
            <x-ui.td numeric>
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="ghost" :href="route('admin.drivers.edit', $driver)">{{ __('admin.common.edit') }}</x-ui.button>
                    <form action="{{ route('admin.drivers.destroy', $driver) }}" method="POST" onsubmit="return confirm('{{ __('admin.drivers.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.common.delete') }}</x-ui.button>
                    </form>
                </div>
            </x-ui.td>
        </x-ui.tr>
        @empty
        <tr><td colspan="5" class="p-0">
            <x-ui.empty :title="__('admin.drivers.no_drivers_yet')" :text="__('admin.drivers.no_drivers_text')">
                <x-ui.button variant="primary" :href="route('admin.drivers.create')">{{ __('admin.drivers.add_first') }}</x-ui.button>
            </x-ui.empty>
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $drivers->links() }}</div>
</x-admin-layout>
