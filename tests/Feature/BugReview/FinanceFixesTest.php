<?php

namespace Tests\Feature\BugReview;

use App\Domain\Booking\Exceptions\QuotationNotConvertibleException;
use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Exceptions\SelfApprovalException;
use App\Domain\Finance\Services\PaymentService;
use App\Enums\LeadStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

/** Regression tests for bug-review BF-01, BF-02, BF-07, BF-08, BF-12. */
class FinanceFixesTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    private function booking()
    {
        $this->seed();
        $branch = $this->baliBranch();

        return $this->bookingFor($this->quotationFor($branch, $this->leadFor($branch), $this->packageWithOneHotelRoom($branch)));
    }

    public function test_verified_payment_cannot_be_rejected_and_rejected_cannot_be_verified(): void
    {
        $booking = $this->booking();
        $cs = User::role('CS Admin')->firstOrFail();
        $finance = User::role('Finance Admin')->firstOrFail();
        $service = app(PaymentService::class);

        $verified = $service->recordPayment($booking, 1_000, 'IDR', creator: $cs);
        $service->verifyPayment($verified, $finance);

        try {
            $service->rejectPayment($verified, 'fake', $finance);
            $this->fail('Verified payment was rejected.');
        } catch (PaymentVerificationException) {
        }

        $rejected = $service->recordPayment($booking, 1_000, 'IDR', creator: $cs);
        $service->rejectPayment($rejected, 'fake proof', $finance);

        $this->expectException(PaymentVerificationException::class);
        $service->verifyPayment($rejected, $finance);
    }

    public function test_payment_recorder_cannot_verify_own_payment(): void
    {
        $booking = $this->booking();
        $finance = User::role('Finance Admin')->firstOrFail();
        $service = app(PaymentService::class);

        $payment = $service->recordPayment($booking, 1_000, 'IDR', creator: $finance);

        $this->expectException(SelfApprovalException::class);
        $service->verifyPayment($payment, $finance);
    }

    public function test_refund_moves_paid_booking_back_to_partially_paid(): void
    {
        $booking = $this->booking();
        $cs = User::role('CS Admin')->firstOrFail();
        $finance = User::role('Finance Admin')->firstOrFail();
        $service = app(PaymentService::class);

        $payment = $service->recordPayment($booking, (int) $booking->total_minor, 'IDR', type: 'full_payment', creator: $cs);
        $service->verifyPayment($payment, $finance);
        $this->assertSame('paid', $booking->fresh()->status->value);

        $service->refundPayment($payment, 1, 'one guest cancelled', $finance);

        $this->assertSame('partially_paid', $booking->fresh()->status->value);
    }

    public function test_quotation_converts_once_and_marks_lead_won(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $quotation = $this->quotationFor($branch, $lead, $this->packageWithOneHotelRoom($branch));

        $this->assertSame(LeadStatus::QUOTED, $lead->fresh()->status);

        $this->bookingFor($quotation);
        $this->assertSame(LeadStatus::WON, $lead->fresh()->status);

        $this->expectException(QuotationNotConvertibleException::class);
        $this->bookingFor($quotation);
    }
}
