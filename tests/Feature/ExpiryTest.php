<?php

namespace Tests\Feature;

use App\Domain\Lead\QuotationGenerator;
use App\Enums\PaymentChannel;
use App\Enums\QuotationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ExpiryTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_expire_command_marks_past_due_quotations_and_public_link_rejects_them(): void
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
            validDays: 1,
        );
        $quotation->update(['status' => QuotationStatus::SENT, 'valid_until' => now()->subDay()]);

        $this->artisan('quotations:expire')->assertSuccessful();

        $this->assertSame(QuotationStatus::EXPIRED, $quotation->fresh()->status);

        $this->get("/{$lead->locale}/q/{$quotation->token}")
            ->assertOk()
            ->assertSee(__('quotation.public.expired_title', [], $lead->locale));
    }
}
