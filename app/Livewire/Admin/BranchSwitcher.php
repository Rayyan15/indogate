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

        $branch = Branch::where('is_active', true)->findOrFail($branchId);

        CurrentBranch::switchTo($branch->id);

        // Same-host only: the raw Referer header is attacker-influenced.
        $previous = url()->previous();
        $sameHost = parse_url($previous, PHP_URL_HOST) === request()->getHost();

        $this->redirect($sameHost ? $previous : route('admin.dashboard'));
    }

    public function render(): View
    {
        return view('livewire.admin.branch-switcher', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'currentBranchId' => CurrentBranch::id(),
        ]);
    }
}
