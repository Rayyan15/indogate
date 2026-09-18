<?php

namespace App\Domain\Pricing\Rules;

use App\Domain\Pricing\Models\Season;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * PRD M4: seasons must never overlap for the same branch (any product type
 * shares the calendar) — same overlap math as Catalog's RateDoesNotOverlap.
 */
class SeasonDoesNotOverlap implements ValidationRule
{
    public function __construct(
        private readonly int $branchId,
        private readonly string $dateFrom,
        private readonly ?int $ignoreSeasonId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $overlaps = Season::withoutGlobalScopes()
            ->where('branch_id', $this->branchId)
            ->when($this->ignoreSeasonId, fn ($q) => $q->whereKeyNot($this->ignoreSeasonId))
            ->where('date_from', '<=', $value)
            ->where('date_to', '>=', $this->dateFrom)
            ->exists();

        if ($overlaps) {
            $fail('Periode musim ini tumpang tindih dengan musim lain di cabang yang sama.');
        }
    }
}
