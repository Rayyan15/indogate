<x-admin-layout>
    <x-slot name="header">{{ __('nav.pricing') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.pricing.eyebrow')" :title="__('admin.pricing.index_title')" :lede="__('admin.pricing.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('admin.pricing.create')">{{ __('admin.pricing.add_rule') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot name="head">
            <x-ui.th>{{ __('admin.pricing.service_type') }}</x-ui.th>
            <x-ui.th>{{ __('admin.pricing.season_start') }}</x-ui.th>
            <x-ui.th>{{ __('admin.pricing.season_end') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.pricing.markup') }}</x-ui.th>
            <x-ui.th>{{ __('admin.common.status') }}</x-ui.th>
            <x-ui.th numeric>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($rules as $rule)
        @php $isActive = now()->between($rule->season_start, $rule->season_end); @endphp
        <x-ui.tr>
            <x-ui.td class="font-medium capitalize text-neutral-900">{{ __('admin.pricing.' . ($rule->service_type === 'all' ? 'all_services' : $rule->service_type)) }}</x-ui.td>
            <x-ui.td class="font-mono">{{ $rule->season_start->format('d M Y') }}</x-ui.td>
            <x-ui.td class="font-mono">{{ $rule->season_end->format('d M Y') }}</x-ui.td>
            <x-ui.td numeric>+{{ $rule->markup_percent }}%</x-ui.td>
            <x-ui.td><x-ui.status :status="$isActive ? 'confirmed' : 'draft'">{{ $isActive ? __('admin.pricing.active_now') : __('admin.pricing.scheduled') }}</x-ui.status></x-ui.td>
            <x-ui.td numeric>
                <div class="flex items-center justify-end gap-3">
                    <x-ui.button variant="ghost" :href="route('admin.pricing.edit', $rule)">{{ __('admin.common.edit') }}</x-ui.button>
                    <form action="{{ route('admin.pricing.destroy', $rule) }}" method="POST" onsubmit="return confirm('{{ __('admin.pricing.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.common.delete') }}</x-ui.button>
                    </form>
                </div>
            </x-ui.td>
        </x-ui.tr>
        @empty
        <tr><td colspan="6" class="p-0">
            <x-ui.empty :title="__('admin.pricing.no_rules_yet')" :text="__('admin.pricing.no_rules_text')">
                <x-ui.button variant="primary" :href="route('admin.pricing.create')">{{ __('admin.pricing.create_first') }}</x-ui.button>
            </x-ui.empty>
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $rules->links() }}</div>
</x-admin-layout>
