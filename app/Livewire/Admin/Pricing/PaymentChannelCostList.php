<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\PaymentChannelCost;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentChannelCostList extends Component
{
    public function mount(): void
    {
        $this->authorize('pricing.manage');
    }

    #[On('channel-cost-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('livewire.admin.pricing.payment-channel-cost-list', [
            'costs' => PaymentChannelCost::orderBy('channel')->get(),
        ]);
    }
}
