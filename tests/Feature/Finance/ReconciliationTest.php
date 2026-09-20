<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_reconciliation_only_counts_verified_payments(): void
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

        $totalRequired = $booking->total_minor;
        $this->assertGreaterThan(0, $totalRequired);

        $paymentService = app(PaymentService::class);

        // 1. Record pending payment - should NOT count toward totalPaidMinor
        $pendingPayment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 1_000_000,
            currency: 'IDR',
            creator: $csUser
        );

        $this->assertSame(0, $booking->fresh()->totalPaidMinor());
        $this->assertSame($totalRequired, $booking->fresh()->remainingBalanceMinor());

        // 2. Verify payment 1 -> now counts
        $paymentService->verifyPayment($pendingPayment, $financeUser);

        $this->assertSame(1_000_000, $booking->fresh()->totalPaidMinor());
        $this->assertSame($totalRequired - 1_000_000, $booking->fresh()->remainingBalanceMinor());

        // 3. Record and reject payment 2 -> should NOT count
        $rejectedPayment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->rejectPayment($rejectedPayment, 'Fake transfer slip', $financeUser);

        $this->assertSame(1_000_000, $booking->fresh()->totalPaidMinor());

        // 4. Record and verify payment 3
        $verifiedPayment2 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($verifiedPayment2, $financeUser);

        $this->assertSame(3_000_000, $booking->fresh()->totalPaidMinor());
        $this->assertSame($totalRequired - 3_000_000, $booking->fresh()->remainingBalanceMinor());
    }

    public function test_refund_adjusts_remaining_balance_correctly(): void
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

        $totalRequired = $booking->total_minor;
        $paymentService = app(PaymentService::class);

        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 5_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment, $financeUser);

        $this->assertSame(5_000_000, $booking->fresh()->totalPaidMinor());

        // Process refund of 1_500_000
        $paymentService->refundPayment($payment, 1_500_000, 'Partial trip cancellation', $financeUser);

        // Net paid should now be 5_000_000 - 1_500_000 = 3_500_000
        $this->assertSame(3_500_000, $booking->fresh()->totalPaidMinor());
        $this->assertSame($totalRequired - 3_500_000, $booking->fresh()->remainingBalanceMinor());
    }
}
