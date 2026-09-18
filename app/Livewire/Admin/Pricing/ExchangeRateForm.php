<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\ExchangeRate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Insert-only: this form never edits an existing rate, only creates a new
 * row. PRD M4 rule.md: exchange_rates rows are never updated.
 */
class ExchangeRateForm extends Component
{
    public string $currency = '';

    public string $rate = '';

    public string $effective_from = '';

    #[On('create-exchange-rate')]
    public function openForCreate(): void
    {
        $this->authorize('pricing.manage');
        $this->reset(['currency', 'rate', 'effective_from']);
        $this->effective_from = now()->format('Y-m-d\TH:i');
        $this->dispatch('open-modal', 'exchange-rate-form');
    }

    public function save(): void
    {
        $this->authorize('pricing.manage');

        $validated = $this->validate([
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'effective_from' => ['required', 'date'],
        ]);

        ExchangeRate::create([
            'currency' => strtoupper($validated['currency']),
            'rate' => $validated['rate'],
            'effective_from' => $validated['effective_from'],
            'created_by' => auth()->id(),
        ]);

        $this->dispatch('exchange-rate-saved');
        $this->dispatch('close-modal', 'exchange-rate-form');
    }

    public function render(): View
    {
        return view('livewire.admin.pricing.exchange-rate-form');
    }
}
