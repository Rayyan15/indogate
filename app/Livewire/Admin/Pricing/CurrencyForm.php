<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\Currency;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CurrencyForm extends Component
{
    public ?string $originalCode = null;

    public string $code = '';

    public string $symbol = '';

    public int $decimal_places = 2;

    public bool $is_active = true;

    #[On('create-currency')]
    public function openForCreate(): void
    {
        $this->authorize('pricing.manage');
        $this->reset(['originalCode', 'code', 'symbol', 'decimal_places', 'is_active']);
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

        $this->dispatch('open-modal', 'currency-form');
    }

    public function save(): void
    {
        $this->authorize('pricing.manage');

        $validated = $this->validate([
            'code' => ['required', 'string', 'size:3', 'alpha'],
            'symbol' => ['required', 'string', 'max:8'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:4'],
            'is_active' => ['boolean'],
        ]);
        $validated['code'] = strtoupper($validated['code']);

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
