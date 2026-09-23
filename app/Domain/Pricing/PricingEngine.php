<?php

namespace App\Domain\Pricing;

use App\Domain\Finance\Fx;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Enums\PaymentChannel;

/**
 * PRD M4 formula, pure integer math throughout:
 *   cost_total     = SUM(item.cost_minor x qty)
 *   margin_minor   = cost_total x margin_percent   (RuleResolver)
 *   channel_cost   = cost_total x channel.percent + channel.flat
 *   sell_idr_minor = cost_total + margin_minor + channel_cost
 *   display_price  = sell_idr_minor / locked_rate(currency)
 */
class PricingEngine
{
    public function __construct(
        private readonly RuleResolver $ruleResolver,
        private readonly Converter $converter,
    ) {}

    public function calculate(PricingRequest $request): PricingBreakdown
    {
        $costTotal = array_reduce(
            $request->items,
            fn (Money $carry, PricingLineItem $item) => $carry->add($this->toIdr($item->total())),
            Money::zero('IDR'),
        );

        $rule = $this->ruleResolver->resolve($request->branchId, $request->productType, $request->departureDate);
        $marginMinor = $costTotal->multiplyByBasisPoints($rule->margin_percent);

        $channelCost = $this->channelCost($costTotal, $request);

        $sellIdrMinor = $costTotal->add($marginMinor)->add($channelCost);

        $lockedRate = $request->lockedExchangeRateId
            ? ExchangeRate::find($request->lockedExchangeRateId)
            : null;

        $displayPrice = $request->override
            ? $request->override->amount
            : $this->converter->toDisplayCurrency($sellIdrMinor, $request->displayCurrency, $lockedRate);

        return new PricingBreakdown(
            costTotal: $costTotal,
            marginPercent: $rule->margin_percent,
            marginMinor: $marginMinor,
            channelCost: $channelCost,
            sellIdrMinor: $sellIdrMinor,
            displayPrice: $displayPrice,
            override: $request->override,
        );
    }

    private function channelCost(Money $costTotal, PricingRequest $request): Money
    {
        $cost = PaymentChannelCost::query()->where('channel', $request->channel->value)->first();

        if (! $cost) {
            return Money::zero('IDR');
        }

        $percent = $costTotal->multiplyByBasisPoints($cost->percent_fee);

        return $request->includeFlatChannelFee ? $percent->add($this->toIdr($cost->flat_fee_minor)) : $percent;
    }

    /**
     * Engine math is IDR-only. A rate or flat fee entered in another
     * currency is converted at today's rate instead of throwing
     * CurrencyMismatchException (bug-review C-01).
     */
    public function toIdr(Money $money): Money
    {
        return $money->currency === 'IDR'
            ? $money
            : Money::of(Fx::toIdrMinor($money->amountMinor, $money->currency, Fx::rate($money->currency)), 'IDR');
    }

    public function flatChannelFeeIdr(PaymentChannel $channel): Money
    {
        $cost = PaymentChannelCost::query()->where('channel', $channel->value)->first();

        return $cost ? $this->toIdr($cost->flat_fee_minor) : Money::zero('IDR');
    }
}
