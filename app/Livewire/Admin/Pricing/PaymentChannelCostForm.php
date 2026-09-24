<?php

namespace App\Livewire\Admin\Pricing;

use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Enums\PaymentChannel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentChannelCostForm extends Component
{
    #[Locked]
    public ?int $costId = null;

    public string $channel = '';

    public int $percent_fee = 0;

    public int $flat_fee_minor = 0;

    public string $currency = 'IDR';

    #[On('create-channel-cost')]
    public function openForCreate(): void
    {
        $this->authorize('pricing.manage');
        $this->reset(['costId', 'channel', 'percent_fee', 'flat_fee_minor']);
        $this->currency = 'IDR';
        $this->dispatch('open-modal', 'channel-cost-form');
    }

    #[On('edit-channel-cost')]
    public function openForEdit(int $costId): void
    {
        $this->authorize('pricing.manage');
        $cost = PaymentChannelCost::findOrFail($costId);

        $this->costId = $cost->id;
        $this->channel = $cost->channel->value;
        $this->percent_fee = $cost->percent_fee;
        $this->flat_fee_minor = $cost->flat_fee_minor->amountMinor;
        $this->currency = $cost->flat_fee_minor->currency;

        $this->dispatch('open-modal', 'channel-cost-form');
    }

    public function save(): void
    {
        $this->authorize('pricing.manage');

        $validated = $this->validate([
            'channel' => ['required', 'in:'.implode(',', array_column(PaymentChannel::cases(), 'value'))],
            'percent_fee' => ['required', 'integer', 'min:0', 'max:10000'],
            'flat_fee_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
        ]);
        $validated['currency'] = strtoupper($validated['currency']);

        PaymentChannelCost::updateOrCreate(
            ['channel' => $validated['channel']],
            $validated,
        );

        $this->dispatch('channel-cost-saved');
        $this->dispatch('close-modal', 'channel-cost-form');
    }

    public function render(): View
    {
        return view('livewire.admin.pricing.payment-channel-cost-form', [
            'channels' => PaymentChannel::cases(),
        ]);
    }
}
