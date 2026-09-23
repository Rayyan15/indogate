<div
    x-data="{ viewMode: @js($viewMode) }"
    x-init="viewMode = localStorage.getItem('packages.view_mode') || viewMode"
>
    <x-ui.page-header :eyebrow="__('packaging.eyebrow')" :title="__('packaging.list.index_title')" :lede="__('packaging.list.index_lede')">
        <x-slot name="actions">
            <x-ui.search-input :placeholder="__('packaging.list.name').'…'" class="w-48 shrink-0" />
            <div class="flex shrink-0 overflow-hidden rounded border border-neutral-300">
                <button type="button"
                    x-on:click="viewMode = 'list'; localStorage.setItem('packages.view_mode', 'list')"
                    title="{{ __('packaging.list.view_list') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center border-e border-neutral-300 transition-colors duration-150"
                    :class="viewMode === 'list' ? 'bg-neutral-900 text-white' : 'bg-neutral-0 text-neutral-500 hover:bg-neutral-100'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <button type="button"
                    x-on:click="viewMode = 'grid'; localStorage.setItem('packages.view_mode', 'grid')"
                    title="{{ __('packaging.list.view_grid') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center transition-colors duration-150"
                    :class="viewMode === 'grid' ? 'bg-neutral-900 text-white' : 'bg-neutral-0 text-neutral-500 hover:bg-neutral-100'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"></path></svg>
                </button>
            </div>
            <x-ui.button variant="primary" :href="route('admin.packages.create')" class="shrink-0">{{ __('packaging.list.create') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($packages->isEmpty())
        <x-ui.empty :title="__('packaging.list.no_data')" text="">
            <x-ui.button variant="primary" :href="route('admin.packages.create')">{{ __('packaging.list.create') }}</x-ui.button>
        </x-ui.empty>
    @else
        {{-- Both views render server-side once; switching is a pure client-side
             x-show + transition, no Livewire round-trip — instant, no re-fetch
             flash. Preference persists via localStorage (a per-viewer display
             setting, not authoritative state, so it belongs there per this
             app's own convention for browser-storage use). --}}
        <div
            x-cloak
            x-show="viewMode === 'grid'"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            @foreach ($packages as $package)
                @php
                    $isImageBrochure = $package->brochure_path && str($package->brochure_path)->lower()->endsWith(['.jpg', '.jpeg', '.png']);
                    $previewItems = $package->items->take(3);
                    $remainingCount = $package->items_count - $previewItems->count();
                @endphp
                <div wire:key="package-card-{{ $package->id }}" class="group flex flex-col overflow-hidden rounded-lg border border-neutral-200 bg-neutral-0 transition hover:shadow-md">
                    {{-- Thumbnail: e-commerce product-card style — brochure image fills the frame,
                         a PDF/no-brochure package falls back to a neutral placeholder icon rather
                         than a bare text link. --}}
                    <a href="{{ route('admin.packages.edit', $package) }}" class="relative block aspect-[4/3] w-full overflow-hidden bg-neutral-100">
                        @if($isImageBrochure)
                            <img src="{{ Storage::disk('public')->url($package->brochure_path) }}" alt="{{ $package->name }}" class="h-full w-full object-cover transition group-hover:scale-105">
                        @elseif($package->brochure_path)
                            <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-neutral-400">
                                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span class="text-[11px] font-medium uppercase tracking-wide">PDF</span>
                            </div>
                        @else
                            <div class="flex h-full w-full items-center justify-center text-neutral-300">
                                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12l-8 4-8-4m16 0l-8-4-8 4m16 0v6l-8 4-8-4v-6"></path></svg>
                            </div>
                        @endif
                        @if($package->is_template)
                            <span class="absolute end-2 top-2"><x-ui.status status="quoted">{{ __('packaging.list.template') }}</x-ui.status></span>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col gap-3 p-4">
                        <div>
                            <h3 class="font-display text-base font-medium text-neutral-900">{{ $package->name }}</h3>
                            <div class="mt-1 flex gap-3 text-xs text-neutral-500">
                                <span>{{ __('packaging.list.pax') }}: {{ $package->base_pax }}</span>
                                <span>{{ __('packaging.list.duration') }}: {{ $package->duration_days ?? '—' }}</span>
                            </div>
                        </div>

                        {{-- Contents preview — like an e-commerce card listing what's included,
                             not a live editor: full customization happens in the builder. --}}
                        <ul class="flex-1 space-y-1 text-xs text-neutral-600">
                            @forelse($previewItems as $item)
                                <li class="flex items-center gap-1.5">
                                    <span class="h-1 w-1 shrink-0 rounded-full bg-neutral-300"></span>
                                    <span class="truncate">{{ $item->inventoryItem?->name }}</span>
                                </li>
                            @empty
                                <li class="text-neutral-400">{{ __('packaging.list.no_components') }}</li>
                            @endforelse
                            @if($remainingCount > 0)
                                <li class="text-neutral-400">{{ __('packaging.list.more_components', ['count' => $remainingCount]) }}</li>
                            @endif
                        </ul>

                        <div class="flex gap-2 border-t border-neutral-100 pt-3">
                            <x-ui.button variant="secondary" :href="route('admin.packages.edit', $package)">{{ __('packaging.list.customize') }}</x-ui.button>
                            <x-ui.button variant="ghost" type="button" wire:click="duplicate({{ $package->id }})">{{ __('packaging.list.duplicate') }}</x-ui.button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div
            x-cloak
            x-show="viewMode === 'list'"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        >
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('packaging.list.name') }}</x-ui.th>
                <x-ui.th sortable field="base_pax" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('packaging.list.pax') }}</x-ui.th>
                <x-ui.th sortable field="duration_days" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('packaging.list.duration') }}</x-ui.th>
                <x-ui.th>{{ __('packaging.list.template') }}</x-ui.th>
                <x-ui.th>{{ __('packaging.builder.brochure') }}</x-ui.th>
                <x-ui.th>{{ __('catalog.common.actions') }}</x-ui.th>
            </x-slot>
            @foreach ($packages as $package)
                <x-ui.tr wire:key="package-{{ $package->id }}">
                    <x-ui.td class="font-medium text-neutral-900">{{ $package->name }}</x-ui.td>
                    <x-ui.td class="font-mono">{{ $package->base_pax }}</x-ui.td>
                    <x-ui.td class="font-mono">{{ $package->duration_days ?? '—' }}</x-ui.td>
                    <x-ui.td>{{ $package->is_template ? __('packaging.list.yes') : __('packaging.list.no') }}</x-ui.td>
                    <x-ui.td>
                        @if($package->brochure_path)
                            <a href="{{ Storage::disk('public')->url($package->brochure_path) }}" target="_blank" class="text-blue-600 hover:underline">{{ __('packaging.builder.view_brochure') }}</a>
                        @else
                            —
                        @endif
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-center gap-1">
                            <x-ui.icon-button :href="route('admin.packages.edit', $package)" :title="__('packaging.list.edit')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </x-ui.icon-button>
                            <x-ui.icon-button type="button" wire:click="duplicate({{ $package->id }})" :title="__('packaging.list.duplicate')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </x-ui.icon-button>
                        </div>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>
        </div>
    @endif

    <div class="mt-5">{{ $packages->links() }}</div>
</div>
