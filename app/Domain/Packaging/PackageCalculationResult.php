<?php

namespace App\Domain\Packaging;

use App\Domain\Pricing\Money;

final class PackageCalculationResult
{
    /**
     * @param  PackageItemResult[]  $itemResults
     */
    public function __construct(
        public readonly array $itemResults,
        public readonly Money $grandCostTotal,
        public readonly Money $grandMarginMinor,
        public readonly Money $grandChannelCost,
        public readonly Money $grandSellIdrMinor,
        public readonly Money $grandDisplayPrice,
    ) {}
}
