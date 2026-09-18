<?php

namespace Tests\Feature;

use App\Domain\Lead\QuotationGenerator;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Enums\PaymentChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class RateLockTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_exchange_rate_change_does_not_alter_an_already_created_quotation(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch, 1_000_000);
        $creator = User::role('Finance Admin')->firstOrFail();

        ExchangeRate::create(['currency' => 'USD', 'rate' => '15000.00000000', 'effective_from' => now()->subDay(), 'created_by' => $creator->id]);

        $quotation = (new QuotationGenerator)->generate(
            lead: $lead,
            package: $package,
            pax: 2,
            previewDate: new \DateTimeImmutable('2026-06-01'),
            channel: PaymentChannel::BANK_TRANSFER,
            currency: 'USD',
        );

        $originalLockedRate = $quotation->locked_rate;
        $originalTotal = $quotation->items->first()->total_minor;

        // A new, much stronger USD rate — this must never leak into the
        // already-issued quotation (PRD M6 manual test #5).
        ExchangeRate::create(['currency' => 'USD', 'rate' => '10000.00000000', 'effective_from' => now(), 'created_by' => $creator->id]);

        $quotation->refresh()->load('items');

        $this->assertEquals($originalLockedRate, $quotation->locked_rate);
        $this->assertSame($originalTotal, $quotation->items->first()->total_minor);
    }
}
