<?php

namespace App\Domain\Pricing;

/**
 * One line item: cost per unit (IDR minor units) and quantity. Line totals
 * are computed with pure integer math.
 */
final class PricingLineItem
{
    public function __construct(
        public readonly Money $costPerUnit,
        public readonly int $qty,
    ) {}

    public function total(): Money
    {
        return Money::of($this->costPerUnit->amountMinor * $this->qty, $this->costPerUnit->currency);
    }
}
