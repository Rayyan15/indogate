<?php

namespace App\Domain\Packaging;

use App\Domain\Pricing\PricingBreakdown;

final class PackageItemResult
{
    public function __construct(
        public readonly int $packageItemId,
        public readonly int $inventoryItemId,
        public readonly int $effectiveQty,
        public readonly ?PricingBreakdown $breakdown,
        public readonly bool $rateMissing,
    ) {}
}
