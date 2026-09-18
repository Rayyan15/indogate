<?php

namespace App\Domain\Pricing;

final class PricingBreakdown
{
    public function __construct(
        public readonly Money $costTotal,
        public readonly int $marginPercent,
        public readonly Money $marginMinor,
        public readonly Money $channelCost,
        public readonly Money $sellIdrMinor,
        public readonly Money $displayPrice,
        public readonly ?Override $override = null,
    ) {}

    public function isOverridden(): bool
    {
        return $this->override !== null;
    }
}
