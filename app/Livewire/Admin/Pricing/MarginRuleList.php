<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\MarginRule;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MarginRuleList extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', MarginRule::class);
    }

    #[On('margin-rule-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('livewire.admin.pricing.margin-rule-list', [
            'rules' => MarginRule::latest()->paginate(15),
        ]);
    }
}
