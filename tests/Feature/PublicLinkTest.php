<?php

namespace Tests\Feature;

use App\Domain\Lead\QuotationGenerator;
use App\Enums\PaymentChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class PublicLinkTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_valid_token_is_accessible_without_auth(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);

        $quotation = (new QuotationGenerator)->generate(
            lead: $lead,
            package: $package,
            pax: 2,
            previewDate: new \DateTimeImmutable('2026-06-01'),
            channel: PaymentChannel::BANK_TRANSFER,
            currency: 'IDR',
        );

        $this->get("/{$lead->locale}/q/{$quotation->token}")->assertOk();
    }

    public function test_token_with_one_character_changed_is_rejected(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);

        $quotation = (new QuotationGenerator)->generate(
            lead: $lead,
            package: $package,
            pax: 2,
            previewDate: new \DateTimeImmutable('2026-06-01'),
            channel: PaymentChannel::BANK_TRANSFER,
            currency: 'IDR',
        );

        $tamperedToken = substr($quotation->token, 0, -1).($quotation->token[-1] === 'a' ? 'b' : 'a');

        $this->get("/{$lead->locale}/q/{$tamperedToken}")->assertNotFound();
    }
}
