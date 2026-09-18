<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Converter;
use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
use App\Domain\Pricing\Exceptions\NoApplicableMarginRuleException;
use App\Domain\Pricing\Models\Currency;
use App\Domain\Pricing\Money;
use App\Domain\Pricing\Override;
use App\Domain\Pricing\PricingEngine;
use App\Domain\Pricing\PricingLineItem;
use App\Domain\Pricing\PricingRequest;
use App\Domain\Pricing\RuleResolver;
use App\Enums\InventoryItemType;
use App\Enums\PaymentChannel;
use App\Support\Branch\CurrentBranch;
use App\Support\Localization\Money as DisplayMoney;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * PRD M4: the only place to exercise PricingEngine + Override manually,
 * since quotations (which would consume it for real) don't exist until M6.
 */
class PricingSimulator extends Component
{
    public string $product_type = InventoryItemType::ROOM->value;

    public string $departure_date = '';

    public string $channel = PaymentChannel::BANK_TRANSFER->value;

    public string $display_currency = 'IDR';

    public int $cost_minor = 1_000_000;

    public int $qty = 1;

    public bool $use_override = false;

    public int $override_amount = 0;

    public string $override_reason = '';

    /**
     * Livewire cannot serialize PricingBreakdown (no synth for arbitrary
     * value objects), so the calculated numbers are flattened into a plain
     * array here instead of holding the domain object itself.
     */
    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->authorize('pricing.manage');
        $this->departure_date = now()->addMonth()->format('Y-m-d');
    }

    public function calculate(): void
    {
        $this->authorize('pricing.manage');
        $this->result = null;
        $this->errorMessage = null;

        $this->validate([
            'product_type' => ['required', 'in:'.implode(',', array_column(InventoryItemType::cases(), 'value'))],
            'departure_date' => ['required', 'date'],
            'channel' => ['required', 'in:'.implode(',', array_column(PaymentChannel::cases(), 'value'))],
            'display_currency' => ['required', 'string', 'size:3'],
            'cost_minor' => ['required', 'integer', 'min:0'],
            'qty' => ['required', 'integer', 'min:1'],
            'override_amount' => ['nullable', 'integer', 'min:0'],
            'override_reason' => [$this->use_override ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        $override = null;
        if ($this->use_override) {
            try {
                $override = new Override(Money::of($this->override_amount, $this->display_currency), $this->override_reason);
            } catch (\InvalidArgumentException $e) {
                $this->addError('override_reason', $e->getMessage());

                return;
            }
        }

        $request = new PricingRequest(
            branchId: CurrentBranch::id(),
            productType: InventoryItemType::from($this->product_type),
            departureDate: new \DateTimeImmutable($this->departure_date),
            items: [new PricingLineItem(Money::of($this->cost_minor, 'IDR'), $this->qty)],
            channel: PaymentChannel::from($this->channel),
            displayCurrency: strtoupper($this->display_currency),
            override: $override,
        );

        try {
            $engine = new PricingEngine(new RuleResolver, new Converter);
            $breakdown = $engine->calculate($request);

            $this->result = [
                'cost_total' => $breakdown->costTotal->amountMinor,
                'cost_total_formatted' => DisplayMoney::format($breakdown->costTotal->amountMinor, 'IDR'),
                'margin_percent' => $breakdown->marginPercent,
                'margin_minor' => $breakdown->marginMinor->amountMinor,
                'margin_minor_formatted' => DisplayMoney::format($breakdown->marginMinor->amountMinor, 'IDR'),
                'channel_cost' => $breakdown->channelCost->amountMinor,
                'channel_cost_formatted' => DisplayMoney::format($breakdown->channelCost->amountMinor, 'IDR'),
                'sell_idr_minor' => $breakdown->sellIdrMinor->amountMinor,
                'sell_idr_minor_formatted' => DisplayMoney::format($breakdown->sellIdrMinor->amountMinor, 'IDR'),
                'display_price' => $breakdown->displayPrice->amountMinor,
                'display_currency' => $breakdown->displayPrice->currency,
                'display_price_formatted' => $this->formatDisplayPrice($breakdown->displayPrice->amountMinor, $breakdown->displayPrice->currency),
                'overridden' => $breakdown->isOverridden(),
            ];

            if ($override) {
                activity('pricing')
                    ->withProperties([
                        'sell_idr_minor' => $breakdown->sellIdrMinor->amountMinor,
                        'override_amount_minor' => $override->amount->amountMinor,
                        'override_currency' => $override->amount->currency,
                        'reason' => $override->reason,
                    ])
                    ->log('Manual price override applied via Pricing Simulator');
            }
        } catch (NoApplicableMarginRuleException $e) {
            $this->errorMessage = __('pricing.simulator.no_rule_error');
        } catch (ExchangeRateNotFoundException $e) {
            $this->errorMessage = __('pricing.simulator.no_rate_error');
        }
    }

    private function formatDisplayPrice(int $amountMinor, string $currency): string
    {
        $decimalPlaces = Currency::find($currency)?->decimal_places ?? 2;
        $major = $amountMinor / (10 ** $decimalPlaces);

        return DisplayMoney::format($major, $currency);
    }

    public function render(): View
    {
        return view('livewire.admin.pricing.pricing-simulator', [
            'productTypes' => InventoryItemType::cases(),
            'channels' => PaymentChannel::cases(),
        ]);
    }
}
