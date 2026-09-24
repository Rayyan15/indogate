<?php

namespace App\Domain\Packaging;

use App\Domain\Catalog\Models\BlackoutDate;
use App\Domain\Catalog\Models\Rate;
use App\Domain\Packaging\Models\Package;
use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Exceptions\CurrencyMismatchException;
use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Domain\Pricing\Exceptions\NoApplicableMarginRuleException;
use App\Domain\Pricing\Money;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Pricing\PricingLineItem;
use App\Domain\Pricing\PricingRequest;
use App\Domain\Pricing\RuleResolver;
use App\Enums\InventoryItemType;
use App\Enums\PaymentChannel;
use DateTimeInterface;

/**
 * Pure orchestration over PricingEngine, no persistence side effects.
 *
 * Pax scaling (PRD "perhitungan per pax dan konfigurasi kamar", confirmed
 * with owner): ROOM item qty is the explicit room count the builder enters
 * — it never scales with pax, because occupancy is a configuration
 * decision, not a derived multiple. Every other product type scales by
 * ceil(pax / package.base_pax).
 *
 * A missing Rate or an unresolvable margin rule for one line is a soft
 * failure — that line is flagged rate_missing and excluded from the
 * totals, the rest of the package still prices out (confirmed with owner).
 */
class PackageCalculator
{
    public function __construct(
        private readonly PricingEngine $pricingEngine = new PricingEngine(new RuleResolver, new Converter),
    ) {}

    public function calculate(
        Package $package,
        int $pax,
        DateTimeInterface $previewDate,
        PaymentChannel $channel,
        string $displayCurrency,
    ): PackageCalculationResult {
        $itemResults = [];

        foreach ($package->items as $item) {
            $anchorDate = (clone $previewDate)->modify("+{$item->day_from} days");

            $isRoom = $item->inventoryItem->type === InventoryItemType::ROOM;
            $effectiveQty = $isRoom ? $item->qty : $item->qty * (int) ceil($pax / max(1, $package->base_pax));

            // Rooms are priced per night at each night's own rate (M-01); other
            // products use the anchor date. Inactive items and blackout dates
            // make the line unpriceable.
            $nights = $isRoom ? max(1, (int) ($item->nights ?? 1)) : 1;
            $lines = [];
            $missing = ! $item->inventoryItem->is_active;

            for ($n = 0; $n < $nights && ! $missing; $n++) {
                $night = (clone $anchorDate)->modify("+{$n} days");

                $rate = Rate::query()
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->whereDate('valid_from', '<=', $night)
                    ->whereDate('valid_to', '>=', $night)
                    ->first();

                $blackedOut = BlackoutDate::query()
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->whereDate('date', $night)
                    ->exists();

                if (! $rate || $blackedOut) {
                    $missing = true;

                    break;
                }

                $lines[] = new PricingLineItem(Money::of($rate->cost_minor, $rate->currency), $effectiveQty);
            }

            if ($missing) {
                $itemResults[] = new PackageItemResult($item->id, $item->inventory_item_id, 0, null, rateMissing: true);

                continue;
            }

            $request = new PricingRequest(
                branchId: $package->branch_id,
                productType: $item->inventoryItem->type,
                departureDate: $anchorDate,
                items: $lines,
                channel: $channel,
                displayCurrency: $displayCurrency,
                includeFlatChannelFee: false,
            );

            try {
                $breakdown = $this->pricingEngine->calculate($request);
                $itemResults[] = new PackageItemResult($item->id, $item->inventory_item_id, $effectiveQty, $breakdown, rateMissing: false);
            } catch (NoApplicableMarginRuleException|ExchangeRateNotFoundException|CurrencyMismatchException) {
                $itemResults[] = new PackageItemResult($item->id, $item->inventory_item_id, $effectiveQty, null, rateMissing: true);
            }
        }

        $resolved = array_filter($itemResults, fn (PackageItemResult $r) => $r->breakdown !== null);

        $sum = fn (callable $pick) => array_reduce(
            $resolved,
            fn (Money $carry, PackageItemResult $r) => $carry->add($pick($r->breakdown)),
            Money::zero('IDR'),
        );

        // The flat channel fee applies once per package, not once per line (H-02).
        $flatFee = $resolved ? $this->pricingEngine->flatChannelFeeIdr($channel) : Money::zero('IDR');
        $grandSellIdr = $sum(fn ($b) => $b->sellIdrMinor)->add($flatFee);

        return new PackageCalculationResult(
            itemResults: $itemResults,
            grandCostTotal: $sum(fn ($b) => $b->costTotal),
            grandMarginMinor: $sum(fn ($b) => $b->marginMinor),
            grandChannelCost: $sum(fn ($b) => $b->channelCost)->add($flatFee),
            grandSellIdrMinor: $grandSellIdr,
            // Convert the grand total once instead of summing rounded per-line prices (M-03).
            grandDisplayPrice: $resolved
                ? (new Converter)->toDisplayCurrency($grandSellIdr, $displayCurrency)
                : Money::zero(strtoupper($displayCurrency)),
        );
    }
}
