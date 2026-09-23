<div
    x-data="{ viewMode: @js($viewMode) }"
    x-init="viewMode = localStorage.getItem('leads.view_mode') || viewMode"
    class="space-y-4"
>
    {{-- Header --}}
    <x-ui.page-header :eyebrow="__('lead.eyebrow')" :title="__('lead.list.index_title')" :lede="__('lead.list.index_lede')">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('admin.leads.create')" class="shrink-0 flex items-center gap-1.5 shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>{{ __('lead.list.create') }}</span>
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>

    {{-- Filter Toolbar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-neutral-200/90 bg-white p-3 shadow-2xs">
        {{-- Search & Dropdowns --}}
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-[280px]">
            <div class="relative w-full sm:w-64">
                <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-neutral-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau no. telepon…"
                    class="h-9 w-full rounded border border-neutral-200 bg-neutral-50/50 ps-9 pe-3 text-xs text-neutral-800 placeholder-neutral-400 focus:border-red-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-600 transition"
                >
            </div>

            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-200 bg-neutral-50/50 ps-3 pe-8 text-xs text-neutral-700 focus:border-red-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-600 transition">
                <option value="">{{ __('lead.list.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ __('lead.status.'.$status->value) }}</option>
                @endforeach
            </select>

            <select wire:model.live="sourceFilter" class="h-9 shrink-0 rounded border border-neutral-200 bg-neutral-50/50 ps-3 pe-8 text-xs text-neutral-700 focus:border-red-600 focus:bg-white focus:outline-none focus:ring-1 focus:ring-red-600 transition">
                <option value="">{{ __('lead.list.all_sources') }}</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->value }}">{{ __('lead.source.'.$source->value) }}</option>
                @endforeach
            </select>

            <label class="inline-flex h-9 shrink-0 items-center gap-2 rounded border border-neutral-200 bg-neutral-50/50 px-2.5 text-xs text-neutral-700 cursor-pointer select-none hover:bg-neutral-100 transition">
                <input type="checkbox" wire:model.live="dueOnly" class="rounded border-neutral-300 text-red-600 focus:ring-red-500">
                <span class="font-medium">{{ __('lead.list.due_only') }}</span>
            </label>
        </div>

        {{-- View Switcher --}}
        <div class="flex items-center gap-1 rounded-md border border-neutral-200 bg-neutral-100/70 p-0.5">
            <button
                type="button"
                x-on:click="viewMode = 'kanban'; localStorage.setItem('leads.view_mode', 'kanban')"
                title="{{ __('lead.list.view_kanban') }}"
                class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-xs font-semibold transition"
                :class="viewMode === 'kanban' ? 'bg-white text-neutral-900 shadow-2xs' : 'text-neutral-500 hover:text-neutral-800'"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h4v16H4V4zm6 0h4v10h-4V4zm6 0h4v7h-4V4z"></path>
                </svg>
                <span>Kanban</span>
            </button>
            <button
                type="button"
                x-on:click="viewMode = 'list'; localStorage.setItem('leads.view_mode', 'list')"
                title="{{ __('lead.list.view_list') }}"
                class="flex items-center gap-1.5 rounded px-2.5 py-1.5 text-xs font-semibold transition"
                :class="viewMode === 'list' ? 'bg-white text-neutral-900 shadow-2xs' : 'text-neutral-500 hover:text-neutral-800'"
            >
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
                <span>Tabel</span>
            </button>
        </div>
    </div>

    @if($leads->isEmpty() && !($statusFilter || $sourceFilter || $dueOnly || $search))
        <x-ui.empty :title="__('lead.list.no_data')" text="Belum ada calon pelanggan atau permintaan masuk terdaftar.">
            <x-ui.button variant="primary" :href="route('admin.leads.create')">{{ __('lead.list.create') }}</x-ui.button>
        </x-ui.empty>
    @else
        {{-- List view --}}
        <div x-cloak x-show="viewMode === 'list'">
            <x-ui.table>
                <x-slot name="head">
                    <x-ui.th sortable field="name" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('lead.list.name') }}</x-ui.th>
                    <x-ui.th>{{ __('lead.list.phone') }}</x-ui.th>
                    <x-ui.th sortable field="status" :sort-field="$sortField" :sort-direction="$sortDirection">{{ __('lead.list.status') }}</x-ui.th>
                    <x-ui.th>{{ __('lead.list.source') }}</x-ui.th>
                    <x-ui.th>{{ __('lead.list.assignee') }}</x-ui.th>
                    <x-ui.th>{{ __('lead.list.follow_up') }}</x-ui.th>
                    <x-ui.th>{{ __('catalog.common.actions') }}</x-ui.th>
                </x-slot>
                @forelse ($leads as $lead)
                    <x-ui.tr wire:key="lead-{{ $lead->id }}">
                        <x-ui.td class="font-medium text-neutral-900">
                            <a href="{{ route('admin.leads.edit', $lead) }}" class="hover:text-red-600 transition">
                                {{ $lead->name }}
                            </a>
                            @if($lead->country)
                                <span class="ms-1.5 inline-block font-mono text-[10px] text-neutral-400 uppercase">[{{ $lead->country }}]</span>
                            @endif
                        </x-ui.td>
                        <x-ui.td class="font-mono text-neutral-600">{{ $lead->phone }}</x-ui.td>
                        <x-ui.td><x-ui.status :status="$lead->status->value">{{ __('lead.status.'.$lead->status->value) }}</x-ui.status></x-ui.td>
                        <x-ui.td>
                            <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-medium {{ $lead->source->value === 'website' ? 'bg-blue-50 text-blue-700 border border-blue-200/80' : 'bg-neutral-100 text-neutral-600' }}">
                                {{ __('lead.source.'.$lead->source->value) }}
                            </span>
                        </x-ui.td>
                        <x-ui.td>{{ $lead->assignee?->name ?? '—' }}</x-ui.td>
                        <x-ui.td>@include('livewire.admin.lead.partials.follow-up-badge', ['lead' => $lead])</x-ui.td>
                        <x-ui.td>
                            <div class="flex items-center justify-center">
                                <x-ui.icon-button :href="route('admin.leads.edit', $lead)" :title="__('lead.list.edit')">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </x-ui.icon-button>
                            </div>
                        </x-ui.td>
                    </x-ui.tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-xs text-neutral-400">
                            Tidak ada lead yang cocok dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>
            <div class="mt-4">{{ $leads->links() }}</div>
        </div>

        {{-- Kanban view --}}
        <div x-cloak x-show="viewMode === 'kanban'" class="flex gap-4 overflow-x-auto pb-6 pt-1 select-none scrollbar-thin scrollbar-thumb-neutral-200">
            @php
                $statusColors = [
                    'new' => ['dot' => 'bg-blue-500', 'border' => 'border-t-blue-500', 'badge' => 'text-blue-700 bg-blue-50 border-blue-200/80'],
                    'contacted' => ['dot' => 'bg-amber-500', 'border' => 'border-t-amber-500', 'badge' => 'text-amber-800 bg-amber-50 border-amber-200/80'],
                    'qualified' => ['dot' => 'bg-purple-500', 'border' => 'border-t-purple-500', 'badge' => 'text-purple-700 bg-purple-50 border-purple-200/80'],
                    'quoted' => ['dot' => 'bg-indigo-500', 'border' => 'border-t-indigo-500', 'badge' => 'text-indigo-700 bg-indigo-50 border-indigo-200/80'],
                    'won' => ['dot' => 'bg-emerald-500', 'border' => 'border-t-emerald-500', 'badge' => 'text-emerald-700 bg-emerald-50 border-emerald-200/80'],
                    'lost' => ['dot' => 'bg-neutral-400', 'border' => 'border-t-neutral-400', 'badge' => 'text-neutral-600 bg-neutral-100 border-neutral-200'],
                ];
            @endphp

            @foreach ($statuses as $status)
                @php
                    $columnLeads = $kanbanLeads->get($status->value, collect());
                    $theme = $statusColors[$status->value] ?? ['dot' => 'bg-neutral-400', 'border' => 'border-t-neutral-400', 'badge' => 'text-neutral-600 bg-neutral-100 border-neutral-200'];
                @endphp
                <div class="w-[280px] shrink-0 rounded-xl bg-neutral-50/80 border border-neutral-200/80 border-t-4 {{ $theme['border'] }} p-3 flex flex-col shadow-2xs">
                    {{-- Column Header --}}
                    <div class="mb-3 flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full {{ $theme['dot'] }}"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-neutral-800">
                                {{ __('lead.status.'.$status->value) }}
                            </span>
                        </div>
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 rounded-full px-1.5 text-[11px] font-bold font-mono bg-white border border-neutral-200 text-neutral-600 shadow-2xs">
                            {{ $columnLeads->count() }}
                        </span>
                    </div>

                    {{-- Cards List --}}
                    <div class="space-y-2.5 flex-1">
                        @forelse($columnLeads as $lead)
                            <a
                                href="{{ route('admin.leads.edit', $lead) }}"
                                wire:key="kanban-{{ $lead->id }}"
                                class="block rounded-lg border border-neutral-200/90 bg-white p-3.5 shadow-2xs transition-all duration-150 hover:shadow-md hover:border-neutral-300 hover:-translate-y-0.5 group cursor-pointer"
                            >
                                {{-- Top Meta: Source & Country --}}
                                <div class="flex items-center justify-between gap-1 mb-1.5">
                                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[9px] font-semibold {{ $lead->source->value === 'website' ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : 'bg-neutral-100 text-neutral-600 border border-neutral-200/60' }}">
                                        @if($lead->source->value === 'website')
                                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                        @else
                                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        @endif
                                        <span>{{ __('lead.source.'.$lead->source->value) }}</span>
                                    </span>

                                    @if($lead->country)
                                        <span class="inline-flex items-center rounded bg-neutral-100 px-1.5 py-0.5 text-[9px] font-bold font-mono text-neutral-600 uppercase border border-neutral-200/60" title="Negara Asal">
                                            {{ $lead->country }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Lead Name --}}
                                <div class="font-bold text-neutral-900 text-xs sm:text-sm leading-snug group-hover:text-red-600 transition line-clamp-1">
                                    {{ $lead->name }}
                                </div>

                                {{-- Phone Number --}}
                                <div class="mt-1 flex items-center gap-1.5 font-mono text-[11px] text-neutral-500">
                                    <svg class="h-3 w-3 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span>{{ $lead->phone }}</span>
                                </div>

                                {{-- Quotation Badge (if any) --}}
                                @if($lead->quotations && $lead->quotations->isNotEmpty())
                                    <div class="mt-2 flex items-center gap-1 text-[10px] text-indigo-700 bg-indigo-50/80 px-2 py-0.5 rounded border border-indigo-200/60 font-medium">
                                        <svg class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span>{{ $lead->quotations->count() }} Penawaran Dibuat</span>
                                    </div>
                                @endif

                                {{-- Lost Reason (if any) --}}
                                @if($lead->lost_reason)
                                    <div class="mt-2 text-[10px] text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200/70 line-clamp-1" title="{{ $lead->lost_reason }}">
                                        {{ $lead->lost_reason }}
                                    </div>
                                @endif

                                {{-- Footer: Assignee & Follow Up --}}
                                <div class="mt-2.5 pt-2 border-t border-neutral-100 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        @if($lead->assignee)
                                            <span class="h-5 w-5 shrink-0 rounded-full bg-neutral-100 border border-neutral-200 text-neutral-700 font-bold text-[9px] flex items-center justify-center font-mono">
                                                {{ strtoupper(substr($lead->assignee->name, 0, 2)) }}
                                            </span>
                                            <span class="truncate text-[11px] text-neutral-600 font-medium" title="{{ $lead->assignee->name }}">
                                                {{ $lead->assignee->name }}
                                            </span>
                                        @else
                                            <span class="text-[11px] text-neutral-400 italic">Belum ada PIC</span>
                                        @endif
                                    </div>

                                    <div>
                                        @include('livewire.admin.lead.partials.follow-up-badge', ['lead' => $lead])
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="flex flex-col items-center justify-center rounded-lg border border-dashed border-neutral-200/80 py-8 px-2 text-center bg-white/40">
                                <svg class="h-5 w-5 text-neutral-300 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <span class="text-[11px] text-neutral-400 font-medium">Belum ada lead</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
