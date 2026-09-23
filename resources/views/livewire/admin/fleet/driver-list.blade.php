<div>
    <x-ui.page-header eyebrow="M8 · {{ __('fleet.fleet') }}" :title="__('fleet.drivers')" lede="{{ __('fleet.drivers') }} di cabang {{ \App\Support\Branch\CurrentBranch::model()?->name }}">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" placeholder="{{ __('fleet.driver_name') }} / {{ __('fleet.driver_phone') }}…" class="w-48 shrink-0" />
            
            <select wire:model.live="genderFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                <option value="">Semua Gender</option>
                <option value="male">{{ __('fleet.gender_male') }}</option>
                <option value="female">{{ __('fleet.gender_female') }}</option>
            </select>

            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                <option value="">Semua Status</option>
                <option value="active">{{ __('fleet.status_active') }}</option>
                <option value="inactive">{{ __('fleet.status_inactive') }}</option>
            </select>

            <button type="button" wire:click="openCreateModal" class="inline-flex h-9 items-center justify-center rounded bg-red-600 px-4 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-red-700">
                <svg class="h-4 w-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                {{ __('fleet.add_driver') }}
            </button>
        </x-slot>
    </x-ui.page-header>

    @if($drivers->isEmpty())
        <x-ui.empty :title="__('fleet.drivers')" text="Belum ada data driver untuk filter ini." />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('fleet.driver_name') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.driver_gender') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.driver_phone') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.driver_languages') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.driver_status') }}</x-ui.th>
                <x-ui.th>{{ __('catalog.common.actions') }}</x-ui.th>
            </x-slot>
            @foreach($drivers as $driver)
                <x-ui.tr wire:key="driver-{{ $driver->id }}">
                    <x-ui.td class="font-medium text-neutral-900">
                        {{ $driver->name }}
                    </x-ui.td>
                    <x-ui.td>
                        @if($driver->gender === 'female')
                            <span class="inline-flex items-center rounded-full bg-pink-50 px-2.5 py-0.5 text-xs font-medium text-pink-700 border border-pink-200">
                                {{ __('fleet.gender_female') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200">
                                {{ __('fleet.gender_male') }}
                            </span>
                        @endif
                    </x-ui.td>
                    <x-ui.td class="font-mono text-xs text-neutral-600">
                        {{ $driver->phone ?? '—' }}
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex flex-wrap gap-1">
                            @forelse((array) ($driver->languages ?? []) as $lang)
                                <span class="rounded bg-neutral-100 px-1.5 py-0.5 text-[10px] font-mono text-neutral-700 uppercase">
                                    {{ $lang }}
                                </span>
                            @empty
                                <span class="text-neutral-400 text-xs">—</span>
                            @endforelse
                        </div>
                    </x-ui.td>
                    <x-ui.td>
                        <button type="button" wire:click="toggleStatus({{ $driver->id }})" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium cursor-pointer transition {{ $driver->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-neutral-100 text-neutral-500 border border-neutral-200 hover:bg-neutral-200' }}">
                            {{ $driver->is_active ? __('fleet.status_active') : __('fleet.status_inactive') }}
                        </button>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-center gap-1">
                            <x-ui.icon-button
                                variant="ghost"
                                type="button"
                                wire:click="openEditModal({{ $driver->id }})"
                                :title="__('catalog.common.edit')"
                                :aria-label="__('catalog.common.edit')"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </x-ui.icon-button>
                            <x-ui.icon-button
                                variant="ghost"
                                type="button"
                                wire:click="deleteDriver({{ $driver->id }})"
                                wire:confirm="Apakah Anda yakin ingin menghapus driver ini?"
                                class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                :title="__('catalog.common.delete')"
                                :aria-label="__('catalog.common.delete')"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </x-ui.icon-button>
                        </div>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
        </x-ui.table>

        <div class="mt-4">
            {{ $drivers->links() }}
        </div>
    @endif

    {{-- Driver Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-md rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ $editingDriverId ? __('fleet.edit_driver') : __('fleet.add_driver') }}
                </h3>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.driver_name') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" wire:model="name" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.driver_gender') }} <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-2 flex gap-4">
                            <label class="inline-flex items-center text-sm">
                                <input type="radio" wire:model="gender" value="male" class="text-red-600 focus:ring-red-500" />
                                <span class="ms-2">{{ __('fleet.gender_male') }}</span>
                            </label>
                            <label class="inline-flex items-center text-sm">
                                <input type="radio" wire:model="gender" value="female" class="text-red-600 focus:ring-red-500" />
                                <span class="ms-2">{{ __('fleet.gender_female') }}</span>
                            </label>
                        </div>
                        @error('gender') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.driver_phone') }}
                        </label>
                        <input type="text" wire:model="phone" placeholder="08xxxxxxxxxx" class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                        @error('phone') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.driver_languages') }}
                        </label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach(['id' => 'Indonesia', 'en' => 'English', 'ar' => 'Arabic', 'zh' => 'Mandarin', 'ru' => 'Russian'] as $code => $langLabel)
                                <label class="inline-flex items-center rounded border border-neutral-200 px-2 py-1 text-xs hover:bg-neutral-50 cursor-pointer">
                                    <input type="checkbox" wire:model="languages" value="{{ $code }}" class="rounded text-red-600 focus:ring-red-500" />
                                    <span class="ms-1.5">{{ $langLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="inline-flex items-center text-sm">
                            <input type="checkbox" wire:model="is_active" class="rounded text-red-600 focus:ring-red-500" />
                            <span class="ms-2 font-medium text-neutral-800">{{ __('fleet.status_active') }}</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-3 border-t border-neutral-100">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded border border-neutral-300 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50">
                            {{ __('catalog.common.cancel') }}
                        </button>
                        <button type="submit" class="rounded bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-white hover:bg-red-700">
                            {{ __('catalog.common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
