<?php

namespace App\Domain\Pricing;

use App\Domain\Pricing\Exceptions\NoApplicableMarginRuleException;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\Season;
use App\Enums\InventoryItemType;
use DateTimeInterface;

/**
 * PRD M4: the season — and therefore the margin rule — is resolved from the
 * departure date, never the quotation's creation date.
 */
class RuleResolver
{
    public function resolve(int $branchId, InventoryItemType $productType, DateTimeInterface $departureDate): MarginRule
    {
        $season = Season::resolveFor($branchId, $departureDate);

        $rule = MarginRule::query()
            ->where('branch_id', $branchId)
            ->where('product_type', $productType->value)
            ->where('is_active', true)
            ->where('season_type', $season?->type->value)
            ->first();

        if (! $rule && $season !== null) {
            $rule = MarginRule::query()
                ->where('branch_id', $branchId)
                ->where('product_type', $productType->value)
                ->where('is_active', true)
                ->whereNull('season_type')
                ->first();
        }

        if (! $rule) {
            throw new NoApplicableMarginRuleException(
                "No active margin rule for branch {$branchId}, product {$productType->value}, season ".($season?->type->value ?? 'none').'.'
            );
        }

        return $rule;
    }
}
