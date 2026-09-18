<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\Currency;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CurrencyList extends Component
{
    public function mount(): void
    {
        $this->authorize('pricing.manage');
    }

    #[On('currency-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('livewire.admin.pricing.currency-list', [
            'currencies' => Currency::orderBy('code')->get(),
        ]);
    }
}
