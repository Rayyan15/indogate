<div>
    <x-ui.page-header :eyebrow="__('catalog.item.eyebrow')" :title="$itemId ? __('catalog.item.edit_title') : __('catalog.item.create_title')" />

    <x-ui.button variant="ghost" :href="route('admin.catalog.inventory-items.index')" class="mb-6">{{ __('catalog.item.back_to_items') }}</x-ui.button>

    @if (session('success'))
        <x-ui.note class="mb-6">{{ session('success') }}</x-ui.note>
    @endif

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
        <main class="space-y-6 lg:col-span-8">
            <x-ui.panel :title="__('catalog.item.basic_info')">
                <form wire:submit="save" class="space-y-6">
                    @foreach(['en' => 'English', 'id' => 'Indonesia', 'ar' => 'العربية'] as $locale => $label)
                        <x-ui.field :label="__('catalog.item.name_locale', ['locale' => $label])" :error="$errors->first('name.'.$locale)">
                            <input type="text" wire:model="name.{{ $locale }}" class="admin-input" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
                        </x-ui.field>
                        <x-ui.field :label="__('catalog.item.description_locale', ['locale' => $label])">
                            <textarea wire:model="description.{{ $locale }}" rows="2" class="admin-input" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"></textarea>
                        </x-ui.field>
                    @endforeach

                    <div class="grid grid-cols-2 gap-5">
                        <x-ui.field :label="__('catalog.item.partner')" :error="$errors->first('partner_id')">
                            <select wire:model="partner_id" class="admin-input">
                                <option value="">{{ __('catalog.item.partner_select') }}</option>
                                @foreach($partners as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                        <x-ui.field :label="__('catalog.item.type')" :error="$errors->first('type')">
                            <select wire:model="type" class="admin-input">
                                <option value="">{{ __('catalog.item.type_select') }}</option>
                                @foreach($types as $t)
                                    <option value="{{ $t->value }}">{{ __('catalog.item.'.$t->value) }}</option>
                                @endforeach
                            </select>
                        </x-ui.field>
                    </div>

                    <x-ui.field :label="__('catalog.item.capacity')" :hint="__('catalog.item.capacity_hint')" :error="$errors->first('capacity')">
                        <input type="number" min="1" wire:model="capacity" class="admin-input font-mono">
                    </x-ui.field>

                    <label class="flex items-center gap-2 text-sm text-neutral-700">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-red-600">
                        {{ __('catalog.partner.active') }}
                    </label>

                    <div class="border-t border-neutral-200 pt-6">
                        <x-ui.button variant="primary" type="submit">{{ __('catalog.item.save') }}</x-ui.button>
                    </div>
                </form>
            </x-ui.panel>

            @if($itemId)
                <x-ui.panel :title="__('catalog.item.rates_title')" flush>
                    <div class="divide-y divide-neutral-100">
                        @forelse($rates as $rate)
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <span class="font-mono text-sm font-medium text-neutral-900">{{ $rate->valid_from->format('d M Y') }} — {{ $rate->valid_to->format('d M Y') }}</span>
                                    <p class="mt-0.5 font-mono text-xs text-neutral-500">{{ $rate->currency }} {{ number_format($rate->cost_minor) }}</p>
                                </div>
                                <x-ui.button variant="danger" type="button" wire:click="deleteRate({{ $rate->id }})" wire:confirm="{{ __('catalog.common.delete') }}?">{{ __('catalog.common.delete') }}</x-ui.button>
                            </div>
                        @empty
                            <x-ui.empty :title="__('catalog.item.no_rates_yet')" />
                        @endforelse
                    </div>
                    <form wire:submit="addRate" class="grid grid-cols-2 gap-4 border-t border-neutral-200 p-5 md:grid-cols-4">
                        <x-ui.field :label="__('catalog.item.rate_from')" :error="$errors->first('rate_valid_from')">
                            <input type="date" wire:model="rate_valid_from" class="admin-input">
                        </x-ui.field>
                        <x-ui.field :label="__('catalog.item.rate_to')" :error="$errors->first('rate_valid_to')">
                            <input type="date" wire:model="rate_valid_to" class="admin-input">
                        </x-ui.field>
                        <x-ui.field :label="__('catalog.item.rate_cost')" :error="$errors->first('rate_cost_minor')">
                            <input type="number" min="0" wire:model="rate_cost_minor" class="admin-input font-mono">
                        </x-ui.field>
                        <x-ui.money-preview class="col-span-2" amount="$wire.rate_cost_minor" currency="$wire.rate_currency" />
                        <x-ui.field :label="__('catalog.item.rate_currency')" :error="$errors->first('rate_currency')">
                            <input type="text" maxlength="3" wire:model="rate_currency" class="admin-input font-mono uppercase">
                        </x-ui.field>
                        <div class="col-span-2 md:col-span-4">
                            <x-ui.button variant="secondary" type="submit">{{ __('catalog.item.add_rate') }}</x-ui.button>
                        </div>
                    </form>
                </x-ui.panel>

                <x-ui.panel :title="__('catalog.item.media_title')" flush>
                    <div class="grid grid-cols-2 gap-4 p-5 sm:grid-cols-3 md:grid-cols-4">
                        @forelse($mediaItems as $media)
                            <div class="group relative overflow-hidden rounded border border-neutral-200">
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($media->path) }}" class="h-28 w-full object-cover" alt="">
                                <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-neutral-900/70 px-1.5 py-1 opacity-0 transition group-hover:opacity-100">
                                    <button type="button" wire:click="moveMedia({{ $media->id }}, -1)" class="text-[10px] text-neutral-0">{{ __('catalog.item.move_up') }}</button>
                                    <button type="button" wire:click="moveMedia({{ $media->id }}, 1)" class="text-[10px] text-neutral-0">{{ __('catalog.item.move_down') }}</button>
                                    <button type="button" wire:click="deleteMedia({{ $media->id }})" wire:confirm="{{ __('catalog.common.delete') }}?" class="text-[10px] text-red-300">{{ __('catalog.common.delete') }}</button>
                                </div>
                            </div>
                        @empty
                            <p class="col-span-full text-sm text-neutral-500">{{ __('catalog.item.no_media_yet') }}</p>
                        @endforelse
                    </div>
                    <form wire:submit="uploadPhotos" class="flex items-center gap-3 border-t border-neutral-200 p-5">
                        <input type="file" wire:model="newPhotos" multiple accept="image/*" class="admin-input flex-1">
                        <x-ui.button variant="secondary" type="submit">{{ __('catalog.item.upload_button') }}</x-ui.button>
                    </form>
                    @error('newPhotos.*') <p class="px-5 pb-4 text-[11px] text-danger">{{ $message }}</p> @enderror
                </x-ui.panel>

                <x-ui.panel :title="__('catalog.item.blackout_title')" flush>
                    <div class="divide-y divide-neutral-100">
                        @forelse($blackoutDates as $blackout)
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <span class="font-mono text-sm font-medium text-neutral-900">{{ $blackout->date->format('d M Y') }}</span>
                                    @if($blackout->reason)<p class="mt-0.5 text-xs text-neutral-500">{{ $blackout->reason }}</p>@endif
                                </div>
                                <x-ui.button variant="danger" type="button" wire:click="deleteBlackoutDate({{ $blackout->id }})" wire:confirm="{{ __('catalog.common.delete') }}?">{{ __('catalog.common.delete') }}</x-ui.button>
                            </div>
                        @empty
                            <x-ui.empty :title="__('catalog.item.no_blackout_yet')" />
                        @endforelse
                    </div>
                    <form wire:submit="addBlackoutDate" class="flex items-end gap-4 border-t border-neutral-200 p-5">
                        <x-ui.field :label="__('catalog.item.blackout_date')" class="flex-1" :error="$errors->first('blackout_date')">
                            <input type="date" wire:model="blackout_date" class="admin-input">
                        </x-ui.field>
                        <x-ui.field :label="__('catalog.item.blackout_reason')" class="flex-1" :error="$errors->first('blackout_reason')">
                            <input type="text" wire:model="blackout_reason" class="admin-input">
                        </x-ui.field>
                        <x-ui.button variant="secondary" type="submit">{{ __('catalog.item.add_blackout') }}</x-ui.button>
                    </form>
                </x-ui.panel>
            @else
                <x-ui.note>{{ __('catalog.item.save_first_note') }}</x-ui.note>
            @endif
        </main>
    </div>
</div>
