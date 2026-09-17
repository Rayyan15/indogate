<x-admin-layout>
    <x-slot name="header">{{ __('admin.pricing.eyebrow') }}</x-slot>

    <x-ui.page-header :eyebrow="__('admin.pricing.eyebrow')" :title="ucfirst($rule->service_type) . ' — ' . $rule->markup_percent . '%'" />

    <x-ui.button variant="ghost" :href="route('admin.pricing.index')" class="mb-6">{{ __('admin.pricing.back_to_pricing') }}</x-ui.button>

    <div class="max-w-2xl">
        <x-ui.panel>
            <form action="{{ route('admin.pricing.update', $rule) }}" method="POST" class="space-y-6">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <x-ui.field :label="__('admin.pricing.service_type')">
                        <select name="service_type" class="admin-input" required>
                            <option value="hotel" {{ old('service_type', $rule->service_type) == 'hotel' ? 'selected' : '' }}>{{ __('admin.pricing.hotel') }}</option>
                            <option value="flight" {{ old('service_type', $rule->service_type) == 'flight' ? 'selected' : '' }}>{{ __('admin.pricing.flight') }}</option>
                            <option value="driver" {{ old('service_type', $rule->service_type) == 'driver' ? 'selected' : '' }}>{{ __('admin.pricing.driver') }}</option>
                            <option value="all" {{ old('service_type', $rule->service_type) == 'all' ? 'selected' : '' }}>{{ __('admin.pricing.all_services') }}</option>
                        </select>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.pricing.markup_percent')">
                        <input type="number" step="0.01" min="0" max="500" name="markup_percent" value="{{ old('markup_percent', $rule->markup_percent) }}" class="admin-input font-mono" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.pricing.season_start_date')">
                        <input type="date" name="season_start" value="{{ old('season_start', $rule->season_start->format('Y-m-d')) }}" class="admin-input" required>
                    </x-ui.field>
                    <x-ui.field :label="__('admin.pricing.season_end_date')">
                        <input type="date" name="season_end" value="{{ old('season_end', $rule->season_end->format('Y-m-d')) }}" class="admin-input" required>
                    </x-ui.field>
                    <div class="md:col-span-2">
                        <x-ui.field :label="__('admin.pricing.label_tier')" :hint="__('admin.common.optional')">
                            <input type="text" name="tier" value="{{ old('tier', $rule->tier) }}" placeholder="{{ __('admin.pricing.label_tier_placeholder') }}" class="admin-input">
                        </x-ui.field>
                    </div>
                </div>
                <div class="flex items-center justify-between border-t border-neutral-200 pt-6">
                    <form action="{{ route('admin.pricing.destroy', $rule) }}" method="POST" onsubmit="return confirm('{{ __('admin.pricing.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <x-ui.button variant="danger" type="submit">{{ __('admin.pricing.delete_rule') }}</x-ui.button>
                    </form>
                    <div class="flex gap-3">
                        <x-ui.button variant="secondary" :href="route('admin.pricing.index')">{{ __('admin.common.cancel') }}</x-ui.button>
                        <x-ui.button variant="primary" type="submit">{{ __('admin.pricing.update_rule') }}</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.panel>
    </div>
</x-admin-layout>
