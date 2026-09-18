<?php

namespace Tests\Concerns;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Lead\Models\Lead;
use App\Domain\Lead\Models\Quotation;
use App\Domain\Lead\QuotationGenerator;
use App\Domain\Packaging\Models\Package;
use App\Enums\PaymentChannel;
use App\Models\Branch;

trait BuildsBookingFixtures
{
    private function quotationFor(Branch $branch, Lead $lead, Package $package): Quotation
    {
        return (new QuotationGenerator)->generate(
            lead: $lead,
            package: $package,
            pax: 2,
            previewDate: new \DateTimeImmutable('2026-06-01'),
            channel: PaymentChannel::BANK_TRANSFER,
            currency: 'IDR',
        );
    }

    private function bookingFor(Quotation $quotation): PackageBooking
    {
        return (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
        );
    }
}
