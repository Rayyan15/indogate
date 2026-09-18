<?php

namespace App\Domain\Pricing;

use App\Enums\InventoryItemType;
use App\Enums\PaymentChannel;
use DateTimeInterface;

final class PricingRequest
{
    /**
     * @param  PricingLineItem[]  $items
     */
    public function __construct(
        public readonly int $branchId,
        public readonly InventoryItemType $productType,
        public readonly DateTimeInterface $departureDate,
        public readonly array $items,
        public readonly PaymentChannel $channel,
        public readonly string $displayCurrency,
        public readonly ?Override $override = null,
        public readonly ?int $lockedExchangeRateId = null,
    ) {}
}
