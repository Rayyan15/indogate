<div>
    <x-ui.page-header :eyebrow="__('packaging.eyebrow')" :title="__('packaging.builder.title')" />

    <div
        x-data="packageBuilder({
            initialItems: @js($items),
        })"
        x-init="$watch('items', () => {})"
        class="grid grid-cols-1 gap-8 lg:grid-cols-[2fr_1fr]"
    >
        {{-- Left column: builder — Alpine owns $data.items, no server round-trip per row edit --}}
        <div class="space-y-6" wire:ignore.self>
            <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-6 space-y-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <x-ui.field :label="__('packaging.builder.meta_title')" :error="$errors->first('name.en')">
                        <input type="text" wire:model="name.en" placeholder="EN" class="admin-input mb-2">
                        <input type="text" wire:model="name.id" placeholder="ID" class="admin-input mb-2">
                        <input type="text" wire:model="name.ar" placeholder="AR" dir="rtl" class="admin-input">
                    </x-ui.field>
                    <x-ui.field :label="__('packaging.builder.base_pax')" :error="$errors->first('base_pax')">
                        <input type="number" min="1" wire:model="base_pax" class="admin-input">
                    </x-ui.field>
                    <x-ui.field :label="__('packaging.builder.duration_days')" :error="$errors->first('duration_days')">
                        <input type="number" min="1" wire:model="duration_days" class="admin-input">
                    </x-ui.field>
                </div>
                <label class="flex items-center gap-2 text-sm text-neutral-700">
                    <input type="checkbox" wire:model="is_template" class="rounded accent-red-600">
                    {{ __('packaging.builder.is_template') }}
                </label>
                @if($durationWarning)
                    <p class="text-xs text-danger">{{ $durationWarning }}</p>
                @endif

                @if($packageId)
                    <x-ui.field :label="__('packaging.builder.brochure')" :error="$errors->first('brochure')">
                        @if($brochure_path)
                            <div class="flex items-center gap-3 text-sm">
                                <a href="{{ Storage::disk('public')->url($brochure_path) }}" target="_blank" class="text-blue-600 hover:underline">{{ __('packaging.builder.view_brochure') }}</a>
                                <button type="button" wire:click="removeBrochure" class="text-xs text-danger hover:underline">{{ __('packaging.builder.remove_brochure') }}</button>
                            </div>
                        @else
                            <div class="flex items-center gap-3">
                                <input type="file" wire:model="brochure" accept=".pdf,.jpg,.jpeg,.png" class="admin-input">
                                <x-ui.button variant="secondary" type="button" wire:click="uploadBrochure" wire:loading.attr="disabled">{{ __('packaging.builder.upload_brochure') }}</x-ui.button>
                            </div>
                            <p class="mt-1 text-[11px] text-neutral-400">{{ __('packaging.builder.brochure_hint') }}</p>
                        @endif
                    </x-ui.field>
                @endif
            </div>

            <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-6 space-y-4">
                <x-ui.field :label="__('packaging.builder.add_component')">
                    <input type="text" wire:model.live.debounce.400ms="componentSearch" placeholder="{{ __('packaging.builder.component_picker_placeholder') }}" class="admin-input">
                </x-ui.field>

                @if($componentSearch !== '' && $this->componentResults->isNotEmpty())
                    <div class="divide-y divide-neutral-100 rounded border border-neutral-200">
                        @foreach($this->componentResults as $result)
                            <button type="button"
                                x-on:click="addItem({{ $result->id }}, @js($result->getTranslation('name', app()->getLocale(), false) ?? $result->getTranslation('name', 'en', false))); $wire.set('componentSearch', '')"
                                class="block w-full px-3 py-2 text-start text-sm hover:bg-neutral-50">
                                {{ $result->getTranslation('name', app()->getLocale(), false) ?? $result->getTranslation('name', 'en', false) }}
                                <span class="text-xs text-neutral-400">{{ __('catalog.item.'.$result->type->value) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                <template x-if="items.length === 0">
                    <p class="text-sm text-neutral-400">{{ __('packaging.builder.no_component') }}</p>
                </template>

                <div class="divide-y divide-neutral-100 rounded border border-neutral-200">
                    <template x-for="(row, index) in items" :key="row.id">
                        <div class="flex flex-wrap items-end gap-3 p-3">
                            <div class="min-w-[10rem] flex-1">
                                <p class="text-sm font-medium text-neutral-900" x-text="row.name"></p>
                            </div>
                            <label class="text-xs text-neutral-500">
                                {{ __('packaging.builder.day_from') }}
                                <input type="number" min="0" x-model.number="row.day_from" class="admin-input mt-1 w-20">
                            </label>
                            <label class="text-xs text-neutral-500">
                                {{ __('packaging.builder.day_to') }}
                                <input type="number" min="0" x-model.number="row.day_to" class="admin-input mt-1 w-20">
                            </label>
                            <label class="text-xs text-neutral-500">
                                {{ __('packaging.builder.qty') }}
                                <input type="number" min="1" x-model.number="row.qty" class="admin-input mt-1 w-16">
                            </label>
                            <label class="text-xs text-neutral-500">
                                {{ __('packaging.builder.nights') }}
                                <input type="number" min="0" x-model.number="row.nights" class="admin-input mt-1 w-16">
                            </label>
                            <button type="button" x-on:click="removeItem(index)" class="text-xs text-danger hover:underline">
                                {{ __('packaging.builder.remove') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-ui.button variant="secondary" type="button" x-on:click="recalculate()">{{ __('packaging.builder.recalculate') }}</x-ui.button>
                <x-ui.button variant="primary" type="button" wire:click="save">{{ __('packaging.builder.save') }}</x-ui.button>
            </div>
        </div>

        {{-- Right column: server-authoritative summary, margin-filtered server-side --}}
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-5 rounded-lg border border-neutral-200 bg-neutral-0 p-6">
                <x-ui.field :label="__('packaging.builder.preview_date')">
                    <input type="date" wire:model="preview_date" class="admin-input">
                </x-ui.field>
                <x-ui.field :label="__('packaging.builder.current_pax')">
                    <input type="number" min="1" wire:model.live.debounce.500ms="current_pax" class="admin-input">
                </x-ui.field>
                <x-ui.field :label="__('packaging.builder.channel')">
                    <select wire:model="channel" class="admin-input">
                        <option value="bank_transfer">{{ __('pricing.channel_cost.bank_transfer') }}</option>
                        <option value="international_card">{{ __('pricing.channel_cost.international_card') }}</option>
                    </select>
                </x-ui.field>
                <x-ui.field :label="__('packaging.builder.display_currency')">
                    <input type="text" wire:model="display_currency" maxlength="3" class="admin-input uppercase">
                </x-ui.field>
            </div>

            <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-6">
                <x-ui.eyebrow>{{ __('packaging.builder.summary_title') }}</x-ui.eyebrow>
                @if($summary)
                    <dl class="mt-3 space-y-2 text-sm">
                        @foreach($summary['items'] as $row)
                            <div class="flex justify-between border-b border-neutral-100 pb-2">
                                <dt class="text-neutral-500">
                                    #{{ $row['inventory_item_id'] }}
                                    @if($row['rate_missing'])
                                        <span class="text-danger">({{ __('packaging.builder.rate_missing') }})</span>
                                    @endif
                                </dt>
                                <dd class="font-mono">{{ $row['display_price_formatted'] ?? '—' }}</dd>
                            </div>
                        @endforeach
                        <div class="flex justify-between pt-2 font-semibold">
                            <dt>{{ __('packaging.builder.display_price') }}</dt>
                            <dd class="font-mono">{{ $summary['grand']['display_price_formatted'] }}</dd>
                        </div>
                        @if(array_key_exists('cost_total', $summary['grand']))
                            <div class="flex justify-between text-neutral-500">
                                <dt>{{ __('packaging.builder.cost_total') }}</dt>
                                <dd class="font-mono">{{ number_format($summary['grand']['cost_total']) }}</dd>
                            </div>
                            <div class="flex justify-between text-neutral-500">
                                <dt>{{ __('packaging.builder.margin_minor') }}</dt>
                                <dd class="font-mono">{{ number_format($summary['grand']['margin_minor']) }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="mt-3 text-sm text-neutral-400">—</p>
                @endif
            </div>

            @if($packageId)
                <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-6 space-y-3" @if($exportPending) wire:poll.3s="checkExportReady" @endif>
                    <x-ui.eyebrow>{{ __('packaging.builder.export_pdf') }}</x-ui.eyebrow>
                    <div class="flex gap-2">
                        <x-ui.button variant="secondary" type="button" wire:click="exportPdf('en')">EN</x-ui.button>
                        <x-ui.button variant="secondary" type="button" wire:click="exportPdf('id')">ID</x-ui.button>
                        <x-ui.button variant="secondary" type="button" wire:click="exportPdf('ar')">AR</x-ui.button>
                    </div>
                    @if($exportPending)
                        <p class="text-xs text-neutral-500">{{ __('packaging.builder.export_pending') }}</p>
                    @endif
                    @if($downloadUrl)
                        <a href="{{ $downloadUrl }}" target="_blank" class="text-sm text-blue-600 hover:underline">{{ __('packaging.builder.download') }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('packageBuilder', ({ initialItems, initialPax }) => ({
        items: initialItems,
        nextTempId: -1,
        addItem(inventoryItemId, name) {
            this.items.push({
                id: this.nextTempId--,
                inventory_item_id: inventoryItemId,
                name: name,
                day_from: 0,
                day_to: 0,
                qty: 1,
                nights: null,
                sort_order: this.items.length,
            });
        },
        removeItem(index) {
            this.items.splice(index, 1);
            this.items.forEach((row, i) => row.sort_order = i);
        },
        recalculate() {
            this.$wire.call('recalculate', this.items, this.$wire.current_pax);
        },
    }));
</script>
@endscript
