<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\Currency;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class CurrencyForm extends Component
{
    public ?string $originalCode = null;

    public string $code = '';

    public string $symbol = '';

    public int $decimal_places = 2;

    public bool $is_active = true;

    public int $spread_bps = 0;

    public int $display_rounding = 0;

    #[On('create-currency')]
    public function openForCreate(): void
    {
        $this->authorize('pricing.manage');
        $this->reset(['originalCode', 'code', 'symbol', 'decimal_places', 'is_active', 'spread_bps', 'display_rounding']);
        $this->decimal_places = 2;
        $this->is_active = true;
        $this->dispatch('open-modal', 'currency-form');
    }

    #[On('edit-currency')]
    public function openForEdit(string $code): void
    {
        $this->authorize('pricing.manage');
        $currency = Currency::findOrFail($code);

        $this->originalCode = $currency->code;
        $this->code = $currency->code;
        $this->symbol = $currency->symbol;
        $this->decimal_places = $currency->decimal_places;
        $this->is_active = $currency->is_active;
        $this->spread_bps = $currency->spread_bps;
        $this->display_rounding = $currency->display_rounding;

        $this->dispatch('open-modal', 'currency-form');
    }

    public function save(): void
    {
        $this->authorize('pricing.manage');

        $validated = $this->validate([
            'code' => ['required', 'string', 'size:3', 'alpha', Rule::unique('currencies', 'code')->ignore($this->originalCode, 'code')],
            'symbol' => ['required', 'string', 'max:8'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'is_active' => ['boolean'],
            'spread_bps' => ['required', 'integer', 'min:0', 'max:5000'],
            'display_rounding' => ['required', 'integer', 'min:0'],
        ]);
        $validated['code'] = strtoupper($validated['code']);

        // Code is the PK referenced by exchange_rates; never rename on edit (M-08).
        if ($this->originalCode !== null) {
            $validated['code'] = $this->originalCode;
        }

        Currency::updateOrCreate(
            ['code' => $this->originalCode ?? $validated['code']],
            $validated,
        );

        $this->dispatch('currency-saved');
        $this->dispatch('close-modal', 'currency-form');
    }

    public function render(): View
    {
        return view('livewire.admin.pricing.currency-form');
    }
}
