<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Pricing\Exceptions\ExchangeRateNotFoundException;
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
        $this->assertSame(4_250_000, $payment->idr_equivalent_minor);
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

        // Custom rate 4300.0 — only honoured from a payment.verify holder.
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 50_000,
            currency: 'SAR',
            customFxRate: 4300.0,
            creator: User::role('Finance Admin')->firstOrFail()
        );

        $this->assertEquals(4300.0, (float) $payment->fx_rate);
        $this->assertSame(2_150_000, $payment->idr_equivalent_minor);
    }

    public function test_custom_fx_rate_from_sales_is_ignored_and_missing_rate_is_refused(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $booking = (new ConvertQuotationToBooking)->convert(
            $this->quotationFor($branch, $this->leadFor($branch), $this->packageWithOneHotelRoom($branch)),
            departureDate: now()->addMonth()->toDateString(),
            returnDate: null,
        );
        $csUser = User::role('CS Admin')->firstOrFail();

        // No stored SAR rate: refuse instead of silently using 1.0 (BF-05).
        ExchangeRate::where('currency', 'SAR')->delete();
        $this->expectException(ExchangeRateNotFoundException::class);

        app(PaymentService::class)->recordPayment(booking: $booking, amountMinor: 50_000, currency: 'SAR', customFxRate: 1_000_000.0, creator: $csUser);
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

    public function test_booking_total_paid_minor_converts_foreign_currency_to_idr(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );
        $booking->update(['currency' => 'IDR', 'total_minor' => 15_000_000]);

        $paymentService = app(PaymentService::class);

        // Payment 1: IDR 5.000.000
        $payment1 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 5_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment1, $financeUser);

        // Payment 2: 1.000 SAR @ 4250 IDR/SAR = IDR 4.250.000
        ExchangeRate::create(['currency' => 'SAR', 'rate' => 4250, 'effective_from' => now()->subMinute(), 'created_by' => $csUser->id]);
        $payment2 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 100_000,
            currency: 'SAR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment2, $financeUser);

        // Total paid in IDR booking should be 5.000.000 + 4.250.000 = 9.250.000
        $this->assertSame(9_250_000, $booking->totalPaidMinor());
    }

    public function test_booking_in_foreign_currency_converts_idr_payments_back_to_booking_currency(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );
        // Booking senilai 1.000 SAR (100.000 halala)
        $booking->update(['currency' => 'SAR', 'total_minor' => 100_000]);

        // Rate: 1 SAR = 4.250 IDR
        ExchangeRate::create([
            'currency' => 'SAR',
            'rate' => 4250.0,
            'effective_from' => now()->subMinute(),
            'created_by' => $csUser->id,
        ]);

        $paymentService = app(PaymentService::class);

        // Payment 1: 500 SAR (50.000 halala)
        $payment1 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 50_000,
            currency: 'SAR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment1, $financeUser);

        // Payment 2: Rp 2.125.000 dalam IDR (ekuivalen 500 SAR @ 4250)
        $payment2 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_125_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment2, $financeUser);

        // Total paid pada booking SAR harus tepat 100.000 halala (1.000 SAR)
        $this->assertSame(100_000, $booking->totalPaidMinor());
        $this->assertSame(0, $booking->remainingBalanceMinor());
    }
}
