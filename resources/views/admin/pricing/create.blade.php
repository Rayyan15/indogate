<x-admin-layout>
    <x-slot name="header">{{ __('admin.pricing.create_title') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.pricing.eyebrow')" :title="__('admin.pricing.create_title')" />

    <x-ui.button variant="ghost" :href="route('admin.pricing.index')" class="mb-6">{{ __('admin.pricing.back_to_pricing') }}</x-ui.button>

    <div class="max-w-2xl">
        <x-ui.note class="mb-6">{{ __('admin.pricing.note') }}</x-ui.note>

        <x-ui.panel>
            <form action="{{ route('admin.pricing.store') }}" method="POST" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-ui.field :label="__('admin.pricing.service_type')" :error="$errors->first('service_type')">
                        <select name="service_type" class="admin-input" required>
                            <option value="hotel" {{ old('service_type') == 'hotel' ? 'selected' : '' }}>{{ __('admin.pricing.hotel') }}</option>
                            <option value="flight" {{ old('service_type') == 'flight' ? 'selected' : '' }}>{{ __('admin.pricing.flight') }}</option>
                            <option value="driver" {{ old('service_type') == 'driver' ? 'selected' : '' }}>{{ __('admin.pricing.driver') }}</option>
                            <option value="all" {{ old('service_type') == 'all' ? 'selected' : '' }}>{{ __('admin.pricing.all_services') }}</option>
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.pricing.markup_percent')" :error="$errors->first('markup_percent')">
                        <input type="number" step="0.01" min="0" max="500" name="markup_percent" value="{{ old('markup_percent') }}" placeholder="{{ __('admin.pricing.markup_placeholder') }}" class="admin-input font-mono" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.pricing.season_start_date')" :error="$errors->first('season_start')">
                        <input type="date" name="season_start" value="{{ old('season_start') }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.pricing.season_end_date')" :error="$errors->first('season_end')">
                        <input type="date" name="season_end" value="{{ old('season_end') }}" class="admin-input" required>
                    </x-ui.field>
                    <div class="md:col-span-2">
                        <x-ui.field :label="__('admin.pricing.label_tier')" :hint="__('admin.common.optional')">
                            <input type="text" name="tier" value="{{ old('tier') }}" placeholder="{{ __('admin.pricing.label_tier_placeholder') }}" class="admin-input">
                        </x-ui.field>
                    </div>
                </div>
                <div class="flex gap-3 border-t border-neutral-200 pt-6">
                    <x-ui.button variant="primary" type="submit">{{ __('admin.pricing.save_rule') }}</x-ui.button>
                    <x-ui.button variant="secondary" :href="route('admin.pricing.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
