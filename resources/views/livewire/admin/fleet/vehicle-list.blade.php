<div>
    @error('fleet')<div class="mb-3 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">{{ $message }}</div>@enderror
    <x-ui.page-header eyebrow="M8 · {{ __('fleet.fleet') }}" :title="__('fleet.vehicles')" :lede="__('fleet.vehicles_lede', ['branch' => \App\Support\Branch\CurrentBranch::model()?->name])">
        <x-slot name="actions">
            <x-ui.search-input wire:model.live.debounce.300ms="search" placeholder="{{ __('fleet.vehicle_plate') }} / {{ __('fleet.vehicle_type') }}…" class="w-48 shrink-0" />

            <select wire:model.live="statusFilter" class="h-9 shrink-0 rounded border border-neutral-300 bg-neutral-0 ps-3 pe-8 min-w-[130px] text-xs text-neutral-800 focus:border-red-600 focus:ring-1 focus:ring-red-600">
                <option value="">{{ __('admin.i18n.all_status') }}</option>
                <option value="active">{{ __('fleet.status_active') }}</option>
                <option value="inactive">{{ __('fleet.status_inactive') }}</option>
            </select>

            <button type="button" wire:click="openCreateModal" class="inline-flex h-9 items-center justify-center rounded bg-red-600 px-4 text-xs font-semibold uppercase tracking-wider text-white transition hover:bg-red-700">
                <svg class="h-4 w-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                {{ __('fleet.add_vehicle') }}
            </button>
        </x-slot>
    </x-ui.page-header>

    @if($vehicles->isEmpty())
        <x-ui.empty :title="__('fleet.vehicles')" :text="__('fleet.no_vehicle_data')" />
    @else
        <x-ui.table>
            <x-slot name="head">
                <x-ui.th>{{ __('fleet.vehicle_plate') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.vehicle_type') }}</x-ui.th>
                <x-ui.th>{{ __('fleet.vehicle_capacity') }}</x-ui.th>
                <x-ui.th>Status</x-ui.th>
                <x-ui.th>{{ __('catalog.common.actions') }}</x-ui.th>
            </x-slot>
            @foreach($vehicles as $vehicle)
                <x-ui.tr wire:key="vehicle-{{ $vehicle->id }}">
                    <x-ui.td class="font-mono font-bold text-neutral-900">
                        {{ $vehicle->plate }}
                    </x-ui.td>
                    <x-ui.td class="font-medium text-neutral-800">
                        {{ $vehicle->type }}
                    </x-ui.td>
                    <x-ui.td>
                        <span class="inline-flex items-center rounded bg-neutral-100 px-2 py-0.5 text-xs text-neutral-700">
                            {{ __('fleet.capacity_pax', ['count' => $vehicle->capacity]) }}
                        </span>
                    </x-ui.td>
                    <x-ui.td>
                        <button type="button" wire:click="toggleStatus({{ $vehicle->id }})" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium cursor-pointer transition {{ $vehicle->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-neutral-100 text-neutral-500 border border-neutral-200 hover:bg-neutral-200' }}">
                            {{ $vehicle->is_active ? __('fleet.status_active') : __('fleet.status_inactive') }}
                        </button>
                    </x-ui.td>
                    <x-ui.td>
                        <div class="flex items-center justify-center gap-1">
                            <x-ui.icon-button
                                variant="ghost"
                                type="button"
                                wire:click="openEditModal({{ $vehicle->id }})"
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
                                wire:click="deleteVehicle({{ $vehicle->id }})"
                                wire:confirm="Apakah Anda yakin ingin menghapus kendaraan ini?"
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
            {{ $vehicles->links() }}
        </div>
    @endif

    {{-- Vehicle Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/40 p-4">
            <div class="w-full max-w-md rounded-lg border border-neutral-200 bg-neutral-0 p-6 shadow-xl">
                <h3 class="text-lg font-bold text-neutral-900">
                    {{ $editingVehicleId ? __('fleet.edit_vehicle') : __('fleet.add_vehicle') }}
                </h3>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.vehicle_plate') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" wire:model="plate" placeholder="DK 1234 AB" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm uppercase focus:border-red-500 focus:outline-none" />
                        @error('plate') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.vehicle_type') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="text" wire:model="type" placeholder="Toyota Innova Zenix" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                        @error('type') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-neutral-700">
                            {{ __('fleet.vehicle_capacity') }} <span class="text-red-600">*</span>
                        </label>
                        <input type="number" wire:model="capacity" min="1" max="60" required class="mt-1 block w-full rounded border border-neutral-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none" />
                        @error('capacity') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
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
