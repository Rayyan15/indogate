<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\ExchangeRate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ExchangeRateList extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('pricing.manage');
    }

    #[On('exchange-rate-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('livewire.admin.pricing.exchange-rate-list', [
            'rates' => ExchangeRate::with('creator')->latest('effective_from')->paginate(15),
        ]);
    }
}
