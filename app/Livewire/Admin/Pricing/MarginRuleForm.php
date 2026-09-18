<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Money;
use App\Enums\InventoryItemType;
use App\Enums\SeasonType;
use App\Support\Branch\CurrentBranch;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MarginRuleForm extends Component
{
    public ?int $ruleId = null;

    public string $product_type = '';

    public string $season_type = '';

    public int $margin_percent = 0;

    public bool $is_active = true;

    #[On('create-margin-rule')]
    public function openForCreate(): void
    {
        $this->authorize('viewAny', MarginRule::class);
        $this->reset(['ruleId', 'product_type', 'season_type', 'margin_percent']);
        $this->is_active = true;
        $this->dispatch('open-modal', 'margin-rule-form');
    }

    #[On('edit-margin-rule')]
    public function openForEdit(int $ruleId): void
    {
        $rule = MarginRule::findOrFail($ruleId);
        $this->authorize('update', $rule);

        $this->ruleId = $rule->id;
        $this->product_type = $rule->product_type->value;
        $this->season_type = $rule->season_type?->value ?? '';
        $this->margin_percent = $rule->margin_percent;
        $this->is_active = $rule->is_active;

        $this->dispatch('open-modal', 'margin-rule-form');
    }

    #[Computed]
    public function previewSell(): int
    {
        return Money::of(1_000_000, 'IDR')->multiplyByBasisPoints($this->margin_percent)
            ->add(Money::of(1_000_000, 'IDR'))
            ->amountMinor;
    }

    public function save(): void
    {
        $branchId = CurrentBranch::id();

        $validated = $this->validate([
            'product_type' => ['required', 'in:'.implode(',', array_column(InventoryItemType::cases(), 'value'))],
            'season_type' => ['nullable', 'in:'.implode(',', array_column(SeasonType::cases(), 'value'))],
            'margin_percent' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['boolean'],
        ]);
        $validated['season_type'] = $validated['season_type'] ?: null;

        $duplicate = MarginRule::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->where('product_type', $validated['product_type'])
            ->where('season_type', $validated['season_type'])
            ->when($this->ruleId, fn ($q) => $q->whereKeyNot($this->ruleId))
            ->exists();

        if ($duplicate) {
            $this->addError('product_type', __('pricing.margin_rule.duplicate_error'));

            return;
        }

        if ($this->ruleId) {
            $rule = MarginRule::findOrFail($this->ruleId);
            $this->authorize('update', $rule);
        } else {
            $this->authorize('viewAny', MarginRule::class);
            $rule = new MarginRule(['branch_id' => $branchId]);
        }

        $rule->fill($validated);
        $rule->save();

        $this->dispatch('margin-rule-saved');
        $this->dispatch('close-modal', 'margin-rule-form');
    }

    public function render(): View
    {
        return view('livewire.admin.pricing.margin-rule-form', [
            'productTypes' => InventoryItemType::cases(),
            'seasonTypes' => SeasonType::cases(),
        ]);
    }
}
