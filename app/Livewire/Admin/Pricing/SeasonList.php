<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\Season;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class SeasonList extends Component
{
    use WithPagination;

    public function mount(): void
    {
        $this->authorize('viewAny', Season::class);
    }

    #[On('season-saved')]
    public function refresh(): void {}

    public function render(): View
    {
        return view('livewire.admin.pricing.season-list', [
            'seasons' => Season::latest('date_from')->paginate(15),
        ]);
    }
}
