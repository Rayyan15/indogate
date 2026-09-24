<x-admin-layout>
    <x-slot name="header">{{ __('pricing.eyebrow') }}</x-slot>

    @include('admin.pricing-engine._tabs', ['active' => 'exchange-rates'])
    @php $latestFx = \App\Domain\Pricing\Models\ExchangeRate::max('effective_from'); @endphp
    @if (! $latestFx || \Illuminate\Support\Carbon::parse($latestFx)->lt(now()->subDay()))
        <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">{{ __('pricing.fx_stale') }}</div>
    @endif
    @livewire('admin.pricing.exchange-rate-list')
</x-admin-layout>
