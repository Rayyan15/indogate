<?php

namespace Tests\Feature;

use App\Domain\Lead\QuotationGenerator;
use App\Enums\PaymentChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class CreateQuotationTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_generator_persists_snapshot_not_a_live_reference(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch, 1_000_000);

        $quotation = (new QuotationGenerator)->generate(
            lead: $lead,
            package: $package,
            pax: 2,
            previewDate: new \DateTimeImmutable('2026-06-01'),
            channel: PaymentChannel::BANK_TRANSFER,
            currency: 'IDR',
        );

        $this->assertSame('IDR', $quotation->currency);
        $this->assertSame($lead->id, $quotation->lead_id);
        $this->assertSame($package->id, $quotation->package_id);
        $this->assertNotEmpty($quotation->token);
        $this->assertCount(1, $quotation->items);

        // 1_000_000 cost * 3 nights = 3_000_000, +20% margin = 3_600_000
        $this->assertSame(3_600_000, $quotation->items->first()->total_minor);
    }
}
