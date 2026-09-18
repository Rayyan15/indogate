<?php

namespace Tests\Unit;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Models\MarginRule;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Domain\Pricing\Money;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Pricing\PricingLineItem;
use App\Domain\Pricing\PricingRequest;
use App\Domain\Pricing\RuleResolver;
use App\Enums\InventoryItemType;
use App\Enums\PaymentChannel;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_international_card_and_bank_transfer_produce_different_prices(): void
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
        Currency::create(['code' => 'IDR', 'symbol' => 'Rp', 'decimal_places' => 0, 'is_active' => true]);

        MarginRule::withoutGlobalScopes()->create([
            'branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => null, 'margin_percent' => 2000, 'is_active' => true,
        ]);

        PaymentChannelCost::create(['channel' => PaymentChannel::BANK_TRANSFER, 'percent_fee' => 0, 'flat_fee_minor' => 0, 'currency' => 'IDR']);
        PaymentChannelCost::create(['channel' => PaymentChannel::INTERNATIONAL_CARD, 'percent_fee' => 550, 'flat_fee_minor' => 0, 'currency' => 'IDR']);

        $engine = new PricingEngine(new RuleResolver, new Converter);
        $items = [new PricingLineItem(Money::of(1_000_000, 'IDR'), 1)];

        $bankTransfer = $engine->calculate(new PricingRequest(
            $branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-05-01'), $items, PaymentChannel::BANK_TRANSFER, 'IDR',
        ));

        $card = $engine->calculate(new PricingRequest(
            $branch->id, InventoryItemType::ROOM, new \DateTimeImmutable('2026-05-01'), $items, PaymentChannel::INTERNATIONAL_CARD, 'IDR',
        ));

        $this->assertTrue($card->sellIdrMinor->amountMinor > $bankTransfer->sellIdrMinor->amountMinor);
        $this->assertSame(55000, $card->channelCost->amountMinor);
        $this->assertSame(0, $bankTransfer->channelCost->amountMinor);
    }
}
