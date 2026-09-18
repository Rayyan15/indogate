<div
    x-data="{ viewMode: @js($viewMode) }"
    x-init="viewMode = localStorage.getItem('leads.view_mode') || viewMode"
>
    <x-ui.page-header :eyebrow="__('lead.eyebrow')" :title="__('lead.list.index_title')" :lede="__('lead.list.index_lede')">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" :placeholder="__('lead.list.name').'…'" class="w-48 shrink-0" />
            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 px-2 text-sm">
                <option value="">{{ __('lead.list.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ __('lead.status.'.$status->value) }}</option>
                @endforeach
            </select>
            <select wire:model.live="sourceFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 px-2 text-sm">
                <option value="">{{ __('lead.list.all_sources') }}</option>
                @foreach ($sources as $source)
                    <option value="{{ $source->value }}">{{ __('lead.source.'.$source->value) }}</option>
                @endforeach
            </select>
            <label class="flex h-9 shrink-0 items-center gap-1.5 rounded border border-neutral-300 px-2 text-sm">
                <input type="checkbox" wire:model.live="dueOnly">
                {{ __('lead.list.due_only') }}
            </label>
            <div class="flex shrink-0 overflow-hidden rounded border border-neutral-300">
                <button type="button"
                    x-on:click="viewMode = 'list'; localStorage.setItem('leads.view_mode', 'list')"
                    title="{{ __('lead.list.view_list') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center border-e border-neutral-300 transition-colors duration-150"
                    :class="viewMode === 'list' ? 'bg-neutral-900 text-white' : 'bg-neutral-0 text-neutral-500 hover:bg-neutral-100'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <button type="button"
                    x-on:click="viewMode = 'kanban'; localStorage.setItem('leads.view_mode', 'kanban')"
                    title="{{ __('lead.list.view_kanban') }}"
                    class="flex h-9 w-9 shrink-0 items-center justify-center transition-colors duration-150"
                    :class="viewMode === 'kanban' ? 'bg-neutral-900 text-white' : 'bg-neutral-0 text-neutral-500 hover:bg-neutral-100'">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h4v16H4V4zm6 0h4v10h-4V4zm6 0h4v7h-4V4z"></path></svg>
                </button>
            </div>
            <x-ui.button variant="primary" :href="route('admin.leads.create')" class="shrink-0">{{ __('lead.list.create') }}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($leads->isEmpty())
        <x-ui.empty :title="__('lead.list.no_data')" text="">
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
                    <x-ui.th numeric>{{ __('catalog.common.actions') }}</x-ui.th>
                </x-slot>
                @foreach ($leads as $lead)
                    <x-ui.tr wire:key="lead-{{ $lead->id }}">
                        <x-ui.td class="font-medium text-neutral-900">{{ $lead->name }}</x-ui.td>
                        <x-ui.td class="font-mono">{{ $lead->phone }}</x-ui.td>
                        <x-ui.td><x-ui.status :status="$lead->status->value">{{ __('lead.status.'.$lead->status->value) }}</x-ui.status></x-ui.td>
                        <x-ui.td>{{ __('lead.source.'.$lead->source->value) }}</x-ui.td>
                        <x-ui.td>{{ $lead->assignee?->name ?? '—' }}</x-ui.td>
                        <x-ui.td>@include('livewire.admin.lead.partials.follow-up-badge', ['lead' => $lead])</x-ui.td>
                        <x-ui.td numeric>
                            <x-ui.icon-button :href="route('admin.leads.edit', $lead)" :title="__('lead.list.edit')">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </x-ui.icon-button>
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
            </x-ui.table>
            <div class="mt-5">{{ $leads->links() }}</div>
        </div>

        {{-- Kanban view — click a card to open the lead's edit page; status
             changes still go through the form, no drag-and-drop. --}}
        <div x-cloak x-show="viewMode === 'kanban'" class="flex gap-4 overflow-x-auto pb-2">
            @foreach ($statuses as $status)
                @php $columnLeads = $kanbanLeads->get($status->value, collect()); @endphp
                <div class="w-64 shrink-0 rounded-lg bg-neutral-50 p-3">
                    <div class="mb-3 flex items-center justify-between px-1">
                        <span class="text-xs font-medium text-neutral-600">{{ __('lead.status.'.$status->value) }}</span>
                        <span class="text-xs text-neutral-400">{{ $columnLeads->count() }}</span>
                    </div>
                    <div class="space-y-2">
                        @forelse($columnLeads as $lead)
                            <a href="{{ route('admin.leads.edit', $lead) }}" wire:key="kanban-{{ $lead->id }}"
                               class="block rounded border border-neutral-200 bg-neutral-0 p-3 text-xs shadow-sm transition hover:shadow-md">
                                <div class="font-medium text-neutral-900">{{ $lead->name }}</div>
                                <div class="mt-0.5 font-mono text-neutral-500">{{ $lead->phone }}</div>
                                <div class="mt-1 flex items-center justify-between text-neutral-400">
                                    <span>{{ $lead->assignee?->name ?? '—' }}</span>
                                    @include('livewire.admin.lead.partials.follow-up-badge', ['lead' => $lead])
                                </div>
                            </a>
                        @empty
                            <p class="px-1 text-xs text-neutral-400">—</p>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
