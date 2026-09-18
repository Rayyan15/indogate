<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\Season;
use App\Domain\Pricing\Rules\SeasonDoesNotOverlap;
use App\Enums\SeasonType;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class SeasonForm extends Component
{
    public ?int $seasonId = null;

    public string $name = '';

    public string $date_from = '';

    public string $date_to = '';

    public string $type = '';

    #[On('create-season')]
    public function openForCreate(): void
    {
        $this->authorize('viewAny', Season::class);
        $this->reset(['seasonId', 'name', 'date_from', 'date_to', 'type']);
        $this->dispatch('open-modal', 'season-form');
    }

    #[On('edit-season')]
    public function openForEdit(int $seasonId): void
    {
        $season = Season::findOrFail($seasonId);
        $this->authorize('update', $season);

        $this->seasonId = $season->id;
        $this->name = $season->name;
        $this->date_from = $season->date_from->format('Y-m-d');
        $this->date_to = $season->date_to->format('Y-m-d');
        $this->type = $season->type->value;

        $this->dispatch('open-modal', 'season-form');
    }

    public function save(): void
    {
        $branchId = CurrentBranch::id();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from', new SeasonDoesNotOverlap($branchId, $this->date_from, $this->seasonId)],
            'type' => ['required', 'in:'.implode(',', array_column(SeasonType::cases(), 'value'))],
        ]);

        if ($this->seasonId) {
            $season = Season::findOrFail($this->seasonId);
            $this->authorize('update', $season);
        } else {
            $this->authorize('viewAny', Season::class);
            $season = new Season(['branch_id' => $branchId]);
        }

        $season->fill($validated);
        $season->save();

        $this->dispatch('season-saved');
        $this->dispatch('close-modal', 'season-form');
    }

    public function render(): View
    {
        return view('livewire.admin.pricing.season-form', [
            'types' => SeasonType::cases(),
        ]);
    }
}
