<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Exceptions\SelfApprovalException;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class SelfApprovalTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_user_who_created_booking_cannot_verify_payment_on_their_own_booking(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        // Super Admin has all permissions, including booking creation and payment verification
        $superAdmin = User::role('Super Admin')->firstOrFail();

        // Super Admin converts the quotation to booking -> created_by = Super Admin id
        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $superAdmin
        );

        $this->assertSame($superAdmin->id, $booking->created_by);

        // Record payment
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_000_000,
            currency: 'IDR',
            creator: $superAdmin
        );

        // Super Admin tries to verify payment on their own booking -> Must throw SelfApprovalException
        $this->expectException(SelfApprovalException::class);
        $this->expectExceptionMessage('Pembuat pemesanan tidak diizinkan memverifikasi pembayarannya sendiri.');

        $paymentService->verifyPayment($payment, $superAdmin);
    }

    public function test_different_finance_admin_can_verify_payment_where_creator_is_blocked(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $superAdmin = User::role('Super Admin')->firstOrFail();
        $financeAdmin = User::role('Finance Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $superAdmin
        );

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_000_000,
            currency: 'IDR',
            creator: $superAdmin
        );

        // A different user with payment.verify (Finance Admin) CAN verify the payment
        $verified = $paymentService->verifyPayment($payment, $financeAdmin);

        $this->assertTrue($verified);
        $payment->refresh();
        $this->assertSame(Payment::STATUS_VERIFIED, $payment->status);
        $this->assertSame($financeAdmin->id, $payment->verified_by);
    }
}
