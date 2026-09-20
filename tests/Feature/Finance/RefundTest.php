<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Exceptions\PaymentVerificationException;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Services\PaymentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_refund_without_reason_is_rejected(): void
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
            amountMinor: 3_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment, $financeUser);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Alasan refund wajib diisi.');

        $paymentService->refundPayment($payment, 1_000_000, '   ', $financeUser);
    }

    public function test_refund_on_unverified_payment_is_rejected(): void
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
            amountMinor: 3_000_000,
            currency: 'IDR',
            creator: $csUser
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya pembayaran yang telah diverifikasi yang dapat di-refund.');

        $paymentService->refundPayment($payment, 1_000_000, 'Customer cancelled', $financeUser);
    }

    public function test_cs_admin_cannot_process_refund(): void
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
            amountMinor: 3_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment, $financeUser);

        $this->expectException(PaymentVerificationException::class);
        $this->expectExceptionMessage('Pengguna tidak memiliki izin untuk memproses refund.');

        $paymentService->refundPayment($payment, 1_000_000, 'Customer requested refund', $csUser);
    }

    public function test_finance_admin_can_successfully_process_refund(): void
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
            amountMinor: 3_000_000,
            currency: 'IDR',
            creator: $csUser
        );
        $paymentService->verifyPayment($payment, $financeUser);

        $refund = $paymentService->refundPayment($payment, 1_000_000, 'Customer changed room type', $financeUser);

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertSame(1_000_000, $refund->amount_minor);
        $this->assertSame('Customer changed room type', $refund->reason);
        $this->assertSame(Refund::STATUS_COMPLETED, $refund->status);
        $this->assertSame($financeUser->id, $refund->processed_by);
        $this->assertSame($booking->id, $refund->booking_id);
        $this->assertSame($payment->id, $refund->payment_id);
    }
}
