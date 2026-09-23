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
            <x-ui.th>{{ __('admin.common.actions') }}</x-ui.th>
        </x-slot>
        @forelse($rules as $rule)
        @php $isActive = now()->between($rule->season_start, $rule->season_end); @endphp
        <x-ui.tr>
            <x-ui.td>
                <div class="font-bold capitalize text-neutral-900">{{ __('admin.pricing.' . ($rule->service_type === 'all' ? 'all_services' : $rule->service_type)) }}</div>
                @if($rule->tier)
                    <div class="text-[11px] text-neutral-500 mt-0.5 font-medium">{{ $rule->tier }}</div>
                @endif
            </x-ui.td>
            <x-ui.td class="font-mono text-xs text-neutral-600">{{ $rule->season_start->format('d M Y') }}</x-ui.td>
            <x-ui.td class="font-mono text-xs text-neutral-600">{{ $rule->season_end->format('d M Y') }}</x-ui.td>
            <x-ui.td numeric class="font-mono font-bold text-emerald-700 text-xs sm:text-sm">+{{ number_format($rule->markup_percent, 1) }}%</x-ui.td>
            <x-ui.td>
                <x-ui.status :status="$isActive ? 'paid' : 'draft'">
                    {{ $isActive ? __('admin.pricing.active_now') : __('admin.pricing.scheduled') }}
                </x-ui.status>
            </x-ui.td>
            <x-ui.td>
                <div class="inline-flex items-center justify-center gap-1">
                    <x-ui.icon-button :href="route('admin.pricing.edit', $rule)" :title="__('admin.common.edit')">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    </x-ui.icon-button>
                    <form action="{{ route('admin.pricing.destroy', $rule) }}" method="POST" onsubmit="return confirm('{{ __('admin.pricing.delete_confirm') }}')" class="inline-flex">
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
            <x-ui.empty :title="__('admin.pricing.no_rules_yet')" :text="__('admin.pricing.no_rules_text')">
                <x-ui.button variant="primary" :href="route('admin.pricing.create')">{{ __('admin.pricing.create_first') }}</x-ui.button>
            </x-ui.empty>
        </td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-5">{{ $rules->links() }}</div>
</x-admin-layout>
