<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Pricing\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class MultiCurrencyPaymentTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_recording_payment_in_sar_resolves_rate_and_calculates_idr_equivalent(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        // Seed exchange rate for SAR: 1 SAR = 4250 IDR
        ExchangeRate::create([
            'currency' => 'SAR',
            'rate' => 4250.00000000,
            'effective_from' => now()->subMinute(),
            'created_by' => $csUser->id,
        ]);

        $paymentService = app(PaymentService::class);

        // Record 1000 SAR (100000 minor)
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 100_000,
            currency: 'SAR',
            creator: $csUser
        );

        $this->assertSame('SAR', $payment->currency);
        $this->assertSame(100_000, $payment->amount_minor);
        $this->assertEquals(4250.0, (float) $payment->fx_rate);
        $this->assertSame(425_000_000, $payment->idr_equivalent_minor);
    }

    public function test_custom_fx_rate_overrides_stored_rate(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        $paymentService = app(PaymentService::class);

        // Custom rate 4300.0
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 50_000,
            currency: 'SAR',
            customFxRate: 4300.0,
            creator: $csUser
        );

        $this->assertEquals(4300.0, (float) $payment->fx_rate);
        $this->assertSame(215_000_000, $payment->idr_equivalent_minor);
    }

    public function test_idr_payment_always_has_unit_exchange_rate(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        $paymentService = app(PaymentService::class);

        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 10_000_000,
            currency: 'IDR',
            creator: $csUser
        );

        $this->assertSame('IDR', $payment->currency);
        $this->assertEquals(1.0, (float) $payment->fx_rate);
        $this->assertSame(10_000_000, $payment->idr_equivalent_minor);
    }
}
