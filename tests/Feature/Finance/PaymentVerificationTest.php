<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Enums\BookingStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_finance_admin_can_verify_payment_and_booking_becomes_partially_paid(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);

        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        // Booking created by CS Admin
        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
        $this->assertSame($csUser->id, $booking->created_by);

        // Record a 30% Down Payment
        $dpAmount = (int) round($booking->total_minor * 0.3);
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: $dpAmount,
            currency: $booking->currency,
            type: Payment::TYPE_DOWN_PAYMENT,
            creator: $csUser
        );

        $this->assertSame(Payment::STATUS_PENDING, $payment->status);

        // Finance Admin verifies the payment
        $verified = $paymentService->verifyPayment($payment, $financeUser);

        $this->assertTrue($verified);
        $payment->refresh();
        $this->assertSame(Payment::STATUS_VERIFIED, $payment->status);
        $this->assertSame($financeUser->id, $payment->verified_by);

        // Booking status should automatically transition to PARTIALLY_PAID
        $booking->refresh();
        $this->assertSame(BookingStatus::PARTIALLY_PAID, $booking->status);
    }

    public function test_cs_admin_cannot_verify_payment_due_to_segregation_of_duties(): void
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
            amountMinor: 1_000_000,
            currency: 'IDR',
            creator: $csUser
        );

        // CS Admin attempt to verify must throw PaymentVerificationException
        $this->expectException(PaymentVerificationException::class);
        $this->expectExceptionMessage('Pengguna tidak memiliki izin untuk memverifikasi pembayaran.');

        $paymentService->verifyPayment($payment, $csUser);
    }

    public function test_full_payment_verification_transitions_booking_to_paid(): void
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

        $paymentService = app(PaymentService::class);

        // 1. First Payment: 40%
        $firstAmount = (int) round($booking->total_minor * 0.4);
        $p1 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: $firstAmount,
            currency: $booking->currency,
            type: Payment::TYPE_DOWN_PAYMENT,
            creator: $csUser
        );
        $paymentService->verifyPayment($p1, $financeUser);

        $booking->refresh();
        $this->assertSame(BookingStatus::PARTIALLY_PAID, $booking->status);

        // 2. Second Payment: remaining 60%
        $secondAmount = $booking->total_minor - $firstAmount;
        $p2 = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: $secondAmount,
            currency: $booking->currency,
            type: Payment::TYPE_FULL_PAYMENT,
            creator: $csUser
        );
        $paymentService->verifyPayment($p2, $financeUser);

        $booking->refresh();
        $this->assertSame(BookingStatus::PAID, $booking->status);
        $this->assertTrue($booking->isFullyPaid());
        $this->assertSame(0, $booking->remainingBalanceMinor());
    }

    public function test_finance_admin_can_reject_payment_with_reason(): void
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

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_000_000,
            currency: 'IDR',
            creator: $csUser
        );

        $rejected = $paymentService->rejectPayment($payment, 'Bukti transfer buram / mutasi belum masuk', $financeUser);

        $this->assertTrue($rejected);
        $payment->refresh();
        $this->assertSame(Payment::STATUS_REJECTED, $payment->status);
        $this->assertSame('Bukti transfer buram / mutasi belum masuk', $payment->rejection_reason);
        $this->assertSame($financeUser->id, $payment->verified_by);

        // Booking status should still be CONFIRMED (not partially paid)
        $booking->refresh();
        $this->assertSame(BookingStatus::CONFIRMED, $booking->status);
    }
}
