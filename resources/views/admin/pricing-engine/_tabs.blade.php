<div class="mb-6 flex gap-1 overflow-x-auto border-b border-neutral-200">
    @foreach ([
        'currencies' => __('pricing.tabs.currencies'),
        'exchange-rates' => __('pricing.tabs.exchange_rates'),
        'seasons' => __('pricing.tabs.seasons'),
        'margin-rules' => __('pricing.tabs.margin_rules'),
        'channel-costs' => __('pricing.tabs.channel_costs'),
        'simulator' => __('pricing.tabs.simulator'),
    ] as $slug => $label)
        <a href="{{ route('admin.pricing-engine.'.$slug) }}"
           class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium transition {{ $active === $slug ? 'border-red-600 text-red-700' : 'border-transparent text-neutral-500 hover:text-neutral-800' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
