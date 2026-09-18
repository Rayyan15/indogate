<?php

namespace Tests\Unit;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Exceptions\NoApplicableMarginRuleException;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\Season;
use App\Domain\Pricing\Money;
use App\Domain\Pricing\Override;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Pricing\PricingLineItem;
use App\Domain\Pricing\PricingRequest;
use App\Domain\Pricing\RuleResolver;
use App\Enums\InventoryItemType;
use App\Enums\PaymentChannel;
use App\Enums\SeasonType;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEngineTest extends TestCase
{
    use RefreshDatabase;

    private function branchWithSeasons(): Branch
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);

        Season::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'name' => 'Peak', 'date_from' => '2026-12-01', 'date_to' => '2026-12-31', 'type' => SeasonType::PEAK]);
        Season::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'name' => 'High', 'date_from' => '2026-07-01', 'date_to' => '2026-07-31', 'type' => SeasonType::HIGH]);
        Season::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'name' => 'Low', 'date_from' => '2026-02-01', 'date_to' => '2026-02-28', 'type' => SeasonType::LOW]);

        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::PEAK, 'margin_percent' => 3500, 'is_active' => true]);
        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::HIGH, 'margin_percent' => 2500, 'is_active' => true]);
        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => SeasonType::LOW, 'margin_percent' => 0, 'is_active' => true]);

        return $branch;
    }

    private function engine(): PricingEngine
    {
        return new PricingEngine(new RuleResolver, new Converter);
    }

    public function test_calculates_correctly_for_peak_high_low_seasons(): void
    {
        $branch = $this->branchWithSeasons();
        $items = [new PricingLineItem(Money::of(10_000_000, 'IDR'), 1)];

        $peak = $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-12-15'), $items, PaymentChannel::BANK_TRANSFER, 'IDR'));
        $high = $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-07-15'), $items, PaymentChannel::BANK_TRANSFER, 'IDR'));
        $low = $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-02-15'), $items, PaymentChannel::BANK_TRANSFER, 'IDR'));

        $this->assertSame(13_500_000, $peak->sellIdrMinor->amountMinor);
        $this->assertSame(12_500_000, $high->sellIdrMinor->amountMinor);
        $this->assertSame(10_000_000, $low->sellIdrMinor->amountMinor);
    }

    public function test_zero_margin_means_sell_price_equals_cost(): void
    {
        $branch = $this->branchWithSeasons();
        $items = [new PricingLineItem(Money::of(5_000_000, 'IDR'), 2)];

        $result = $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-02-10'), $items, PaymentChannel::BANK_TRANSFER, 'IDR'));

        $this->assertSame(10_000_000, $result->costTotal->amountMinor);
        $this->assertSame(0, $result->marginMinor->amountMinor);
        $this->assertSame(10_000_000, $result->sellIdrMinor->amountMinor);
    }

    public function test_no_line_items_means_zero_cost_and_zero_sell_price(): void
    {
        $branch = $this->branchWithSeasons();

        $result = $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-02-10'), [], PaymentChannel::BANK_TRANSFER, 'IDR'));

        $this->assertSame(0, $result->costTotal->amountMinor);
        $this->assertSame(0, $result->sellIdrMinor->amountMinor);
    }

    public function test_no_applicable_rule_throws(): void
    {
        $branch = $this->branchWithSeasons();
        $items = [new PricingLineItem(Money::of(1_000_000, 'IDR'), 1)];

        $this->expectException(NoApplicableMarginRuleException::class);

        $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::VEHICLE, new \DateTimeImmutable('2026-02-10'), $items, PaymentChannel::BANK_TRANSFER, 'IDR'));
    }

    public function test_override_replaces_display_price_and_records_reason(): void
    {
        $branch = $this->branchWithSeasons();
        $items = [new PricingLineItem(Money::of(10_000_000, 'IDR'), 1)];
        $override = new Override(Money::of(999_000_000, 'IDR'), 'Diskon negosiasi khusus agen');

        $result = $this->engine()->calculate(new PricingRequest($branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-02-10'), $items, PaymentChannel::BANK_TRANSFER, 'IDR', $override));

        $this->assertTrue($result->isOverridden());
        $this->assertSame(999_000_000, $result->displayPrice->amountMinor);
        $this->assertSame('Diskon negosiasi khusus agen', $result->override->reason);
    }
}
