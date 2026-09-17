<?php

namespace App\Livewire\Admin;

use App\Models\Branch;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BranchSwitcher extends Component
{
    public function switchTo(int $branchId): void
    {
        $this->authorize('branch.switch');

        CurrentBranch::switchTo($branchId);

        $this->redirect(request()->header('Referer') ?? route('admin.dashboard'));
    }

    public function render(): View
    {
        return view('livewire.admin.branch-switcher', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'currentBranchId' => CurrentBranch::id(),
        ]);
    }
}
