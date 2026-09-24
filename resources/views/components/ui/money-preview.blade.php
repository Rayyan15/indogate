{{-- Live conversion preview. $amount/$currency are Alpine expressions (e.g. "$wire.cost_minor"); amount is in minor units of currency. Rates are inlined; no request per keystroke. --}}
@props(['amount', 'currency'])
<div x-data="{
        fx: @js(\App\Support\Pricing\FxPreview::data()),
        get rows() {
            const src = this.fx[String({{ $currency }} || '').toUpperCase()];
            const minor = Number({{ $amount }});
            if (!src || !isFinite(minor) || minor <= 0) return [];
            const idr = (minor / Math.pow(10, src.decimals)) * src.rate;
            return Object.entries(this.fx).map(([code, c]) => ({ code, value: (idr / c.rate).toLocaleString(undefined, { minimumFractionDigits: c.decimals, maximumFractionDigits: c.decimals }) }));
        }
    }" x-show="rows.length" x-cloak {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-4 gap-y-1 rounded border border-neutral-200 bg-neutral-50 px-3 py-2 font-mono text-[11px] text-neutral-600']) }}>
    <span class="font-sans font-semibold uppercase tracking-[0.14em] text-neutral-500">{{ __('pricing.preview.label') }}</span>
    <template x-for="row in rows" :key="row.code"><span><span x-text="row.code"></span> <span x-text="row.value"></span></span></template>
</div>
