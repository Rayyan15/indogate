<div>
    <x-ui.page-header :eyebrow="__('pricing.eyebrow')" :title="__('pricing.simulator.title')" :lede="__('pricing.simulator.lede')" />

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <form wire:submit="calculate" class="space-y-5 rounded-lg border border-neutral-200 bg-neutral-0 p-6">
            <x-ui.field :label="__('pricing.simulator.product_type')" :error="$errors->first('product_type')">
                <select wire:model="product_type" class="admin-input">
                    @foreach($productTypes as $t)
                        <option value="{{ $t->value }}">{{ __('catalog.item.'.$t->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('pricing.simulator.departure_date')" :error="$errors->first('departure_date')">
                <input type="date" wire:model="departure_date" class="admin-input">
            </x-ui.field>

            <x-ui.field :label="__('pricing.simulator.channel')" :error="$errors->first('channel')">
                <select wire:model="channel" class="admin-input">
                    @foreach($channels as $c)
                        <option value="{{ $c->value }}">{{ __('pricing.channel_cost.'.$c->value) }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            <x-ui.field :label="__('pricing.simulator.display_currency')" :error="$errors->first('display_currency')">
                <input type="text" wire:model="display_currency" maxlength="3" class="admin-input uppercase">
            </x-ui.field>

            <div class="grid grid-cols-2 gap-5">
                <x-ui.field :label="__('pricing.simulator.cost_minor')" :error="$errors->first('cost_minor')">
                    <input type="number" min="0" wire:model="cost_minor" class="admin-input">
                </x-ui.field>
                <x-ui.field :label="__('pricing.simulator.qty')" :error="$errors->first('qty')">
                    <input type="number" min="1" wire:model="qty" class="admin-input">
                </x-ui.field>
            </div>

            <label class="flex items-center gap-2 text-sm text-neutral-700">
                <input type="checkbox" wire:model.live="use_override" class="rounded accent-red-600">
                {{ __('pricing.simulator.override_toggle') }}
            </label>

            @if($use_override)
                <x-ui.field :label="__('pricing.simulator.override_amount')" :error="$errors->first('override_amount')">
                    <input type="number" min="0" wire:model="override_amount" class="admin-input">
                </x-ui.field>
                <x-ui.field :label="__('pricing.simulator.override_reason')" :error="$errors->first('override_reason')">
                    <textarea wire:model="override_reason" class="admin-input" rows="2"></textarea>
                </x-ui.field>
            @endif

            <x-ui.button variant="primary" type="submit" wire:loading.attr="disabled">{{ __('pricing.simulator.calculate') }}</x-ui.button>
        </form>

        <div class="rounded-lg border border-neutral-200 bg-neutral-0 p-6">
            @if($errorMessage)
                <p class="text-sm text-danger">{{ $errorMessage }}</p>
            @elseif($result)
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ __('pricing.simulator.cost_total') }}</dt><dd class="text-end"><span class="block font-medium">{{ $result['cost_total_formatted'] }}</span><span class="block font-mono text-[11px] text-neutral-400">{{ $result['cost_total'] }}</span></dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ __('pricing.simulator.margin_percent') }}</dt><dd class="font-mono">{{ number_format($result['margin_percent'] / 100, 2) }}%</dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ __('pricing.simulator.margin_minor') }}</dt><dd class="text-end"><span class="block">{{ $result['margin_minor_formatted'] }}</span><span class="block font-mono text-[11px] text-neutral-400">{{ $result['margin_minor'] }}</span></dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-500">{{ __('pricing.simulator.channel_cost') }}</dt><dd class="text-end"><span class="block">{{ $result['channel_cost_formatted'] }}</span><span class="block font-mono text-[11px] text-neutral-400">{{ $result['channel_cost'] }}</span></dd></div>
                    <div class="flex justify-between border-t border-neutral-200 pt-3"><dt class="text-neutral-700 font-medium">{{ __('pricing.simulator.sell_idr_minor') }}</dt><dd class="text-end"><span class="block font-semibold">{{ $result['sell_idr_minor_formatted'] }}</span><span class="block font-mono text-[11px] text-neutral-400">{{ $result['sell_idr_minor'] }}</span></dd></div>
                    <div class="flex justify-between"><dt class="text-neutral-700 font-medium">{{ __('pricing.simulator.display_price') }}</dt><dd class="text-end"><span class="block font-semibold">{{ $result['display_price_formatted'] }}</span><span class="block font-mono text-[11px] text-neutral-400">{{ $result['display_price'] }} {{ $result['display_currency'] }}</span></dd></div>
                    @if($result['overridden'])
                        <x-ui.status status="confirmed">{{ __('pricing.simulator.overridden_badge') }}</x-ui.status>
                    @endif
                </dl>
            @else
                <p class="text-sm text-neutral-400">—</p>
            @endif
        </div>
    </div>
</div>
