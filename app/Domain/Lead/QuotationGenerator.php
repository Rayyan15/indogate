<?php

namespace App\Domain\Lead;

use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Packaging\Models\Package;
use App\Domain\Packaging\PackageCalculator;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Enums\PaymentChannel;
use App\Enums\QuotationStatus;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Snapshot, not reference: prices are computed once here via the unmodified
 * M5 PackageCalculator, then persisted as plain integers on Quotation /
 * QuotationItem. Nothing re-reads PackageCalculator/PricingEngine for an
 * already-created quotation, so a later ExchangeRate/MarginRule/Season
 * change never retroactively alters an issued quotation (PRD M6 step 8,
 * RateLockTest).
 */
class QuotationGenerator
{
    public function __construct(
        private readonly PackageCalculator $calculator = new PackageCalculator,
    ) {}

    public function generate(
        Lead $lead,
        Package $package,
        int $pax,
        DateTimeInterface $previewDate,
        PaymentChannel $channel,
        string $currency,
        int $validDays = 7,
    ): Quotation {
        $currency = strtoupper($currency);
        $lockedRate = ExchangeRate::currentFor($currency)?->rate ?? '1.00000000';

        $result = $this->calculator->calculate($package, $pax, $previewDate, $channel, $currency);

        return DB::transaction(function () use ($lead, $package, $currency, $lockedRate, $result, $validDays) {
            $quotation = Quotation::create([
                'branch_id' => $lead->branch_id,
                'lead_id' => $lead->id,
                'package_id' => $package->id,
                'token' => Str::random(48),
                'currency' => $currency,
                'locked_rate' => $lockedRate,
                'valid_until' => now()->addDays($validDays),
                'status' => QuotationStatus::DRAFT,
            ]);

            foreach ($result->itemResults as $itemResult) {
                if ($itemResult->rateMissing || $itemResult->breakdown === null) {
                    continue;
                }

                $totalMinor = $itemResult->breakdown->displayPrice->amountMinor;
                $qty = max(1, $itemResult->effectiveQty);

                $quotation->items()->create([
                    'description' => $package->items->firstWhere('id', $itemResult->packageItemId)?->inventoryItem->name ?? [],
                    'qty' => $qty,
                    'unit_price_minor' => intdiv($totalMinor, $qty),
                    'total_minor' => $totalMinor,
                ]);
            }

            return $quotation->fresh('items');
        });
    }
}
