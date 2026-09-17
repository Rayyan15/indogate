<?php

namespace App\Domain\Catalog\Rules;

use App\Domain\Catalog\Models\Rate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PRD M3: rate periods for the same inventory item must never overlap.
 * Two ranges [a1,a2] and [b1,b2] overlap iff a1 <= b2 AND a2 >= b1.
 */
class RateDoesNotOverlap implements ValidationRule
{
    public function __construct(
        private readonly int $inventoryItemId,
        private readonly string $validFrom,
        private readonly ?int $ignoreRateId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $overlaps = Rate::query()
            ->where('inventory_item_id', $this->inventoryItemId)
            ->when($this->ignoreRateId, fn ($q) => $q->whereKeyNot($this->ignoreRateId))
            ->where('valid_from', '<=', $value)
            ->where('valid_to', '>=', $this->validFrom)
            ->exists();

        if ($overlaps) {
            $fail('Periode ini tumpang tindih dengan aturan harga lain untuk item ini.');
        }
    }
}
