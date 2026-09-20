<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Models\VendorPayment;
use App\Domain\Finance\Services\MarginReportService;
use App\Domain\Finance\Services\PaymentService;
use App\Enums\BookingStatus;
use App\Enums\PartnerType;
use App\Livewire\Admin\Finance\AccountsReceivableList;
use App\Livewire\Admin\Finance\PaymentList;
use App\Livewire\Admin\Finance\VendorPaymentList;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class FinanceComprehensiveStressTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_payment_creation_rejects_negative_or_zero_amount(): void
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

        // Zero
        try {
            $paymentService->recordPayment($booking, 0, 'IDR', creator: $csUser);
            $this->fail('Expected exception for 0 amount');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Jumlah pembayaran harus lebih dari 0', $e->getMessage());
        }

        // Negative
        try {
            $paymentService->recordPayment($booking, -500_000, 'IDR', creator: $csUser);
            $this->fail('Expected exception for negative amount');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Jumlah pembayaran harus lebih dari 0', $e->getMessage());
        }
    }

    public function test_cannot_record_payment_for_cancelled_booking(): void
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

        $booking->update(['status' => BookingStatus::CANCELLED]);

        $paymentService = app(PaymentService::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tidak dapat mencatat pembayaran untuk pemesanan yang dibatalkan');

        $paymentService->recordPayment($booking, 1_000_000, 'IDR', creator: $csUser);
    }

    public function test_double_verification_is_idempotent(): void
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
        $payment = $paymentService->recordPayment($booking, 1_000_000, 'IDR', creator: $csUser);

        // First verification
        $this->assertTrue($paymentService->verifyPayment($payment, $financeUser));
        $this->assertSame(Payment::STATUS_VERIFIED, $payment->fresh()->status);

        // Second verification should return true cleanly without error
        $this->assertTrue($paymentService->verifyPayment($payment->fresh(), $financeUser));
        $this->assertSame(Payment::STATUS_VERIFIED, $payment->fresh()->status);
    }

    public function test_cumulative_refund_limits_prevent_over_refunding(): void
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
        $payment = $paymentService->recordPayment($booking, 1_000_000, 'IDR', creator: $csUser);
        $paymentService->verifyPayment($payment, $financeUser);

        // 1. First partial refund: 600_000 (valid, remaining = 400_000)
        $r1 = $paymentService->refundPayment($payment, 600_000, 'First partial refund', $financeUser);
        $this->assertInstanceOf(Refund::class, $r1);
        $this->assertSame(600_000, $r1->amount_minor);

        // Net paid on booking should now be 1_000_000 - 600_000 = 400_000
        $this->assertSame(400_000, $booking->fresh()->totalPaidMinor());

        // 2. Second partial refund of 500_000 should FAIL (exceeds remaining 400_000)
        try {
            $paymentService->refundPayment($payment, 500_000, 'Exceeding remaining balance', $financeUser);
            $this->fail('Expected exception for exceeding refundable balance');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('melebihi sisa dana yang dapat dikembalikan', $e->getMessage());
        }

        // 3. Second partial refund of exactly 400_000 should SUCCEED (total 100% refunded)
        $r2 = $paymentService->refundPayment($payment, 400_000, 'Second partial refund to complete 100%', $financeUser);
        $this->assertInstanceOf(Refund::class, $r2);
        $this->assertSame(0, $booking->fresh()->totalPaidMinor());

        // 4. Third refund should FAIL because 0 balance remaining
        try {
            $paymentService->refundPayment($payment, 100_000, 'Third refund after 100%', $financeUser);
            $this->fail('Expected exception for fully refunded payment');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('melebihi sisa dana yang dapat dikembalikan', $e->getMessage());
        }
    }

    public function test_vendor_payment_cross_branch_validation_and_deletion(): void
    {
        Storage::fake('local');
        $this->seed();
        $bali = $this->baliBranch();
        $jakarta = Branch::where('code', 'JKT')->first() ?? Branch::create(['name' => 'Jakarta', 'code' => 'JKT']);

        $financeUser = User::role('Finance Admin')->firstOrFail();
        session(['active_branch_id' => $bali->id]);

        // Create partner in Bali
        $baliPartner = Partner::create([
            'branch_id' => $bali->id,
            'type' => PartnerType::HOTEL,
            'city' => 'Denpasar',
            'name' => ['en' => 'Bali Hotel'],
            'is_active' => true,
        ]);

        // Create partner in Jakarta
        $jktPartner = Partner::create([
            'branch_id' => $jakarta->id,
            'type' => PartnerType::HOTEL,
            'city' => 'Jakarta',
            'name' => ['en' => 'Jakarta Hotel'],
            'is_active' => true,
        ]);

        // Attempt to create vendor payment with Jakarta partner while in Bali session -> must fail validation
        Livewire::actingAs($financeUser)
            ->test(VendorPaymentList::class)
            ->set('partner_id', $jktPartner->id)
            ->set('amount_minor', 5_000_000)
            ->set('currency', 'IDR')
            ->set('paid_at', now()->toDateString())
            ->call('save')
            ->assertHasErrors(['partner_id']);

        // Create vendor payment with Bali partner -> succeeds
        $proof = UploadedFile::fake()->create('proof.pdf', 200, 'application/pdf');

        Livewire::actingAs($financeUser)
            ->test(VendorPaymentList::class)
            ->set('partner_id', $baliPartner->id)
            ->set('amount_minor', 5_000_000)
            ->set('currency', 'IDR')
            ->set('paid_at', now()->toDateString())
            ->set('description', 'Hotel booking payment')
            ->set('proofFile', $proof)
            ->call('save')
            ->assertHasNoErrors();

        $vp = VendorPayment::where('partner_id', $baliPartner->id)->firstOrFail();
        $this->assertSame(5_000_000, $vp->amount_minor);
        $this->assertSame($bali->id, $vp->branch_id);
        $this->assertNotNull($vp->proof_file);
        $this->assertTrue(Storage::disk('local')->exists($vp->proof_file));

        // Delete vendor payment via component
        Livewire::actingAs($financeUser)
            ->test(VendorPaymentList::class)
            ->call('deleteVendorPayment', $vp->id)
            ->assertSet('actionSuccess', 'Pembayaran vendor berhasil dihapus.');

        $this->assertDatabaseMissing('vendor_payments', ['id' => $vp->id]);
        $this->assertFalse(Storage::disk('local')->exists($vp->proof_file));
    }

    public function test_accounts_receivable_filters_and_wildcard_search(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        session(['active_branch_id' => $branch->id]);

        // Create booking 1: confirmed (unpaid)
        $q1 = $this->quotationFor($branch, $lead, $package);
        $b1 = (new ConvertQuotationToBooking)->convert($q1, departureDate: now()->addWeek()->toDateString(), returnDate: now()->addWeek()->addDays(3)->toDateString(), actor: $csUser);

        // Create booking 2: overdue (departure in the past, still confirmed)
        $q2 = $this->quotationFor($branch, $lead, $package);
        $b2 = (new ConvertQuotationToBooking)->convert($q2, departureDate: now()->subDays(2)->toDateString(), returnDate: now()->addDay()->toDateString(), actor: $csUser);

        // Test unpaid filter contains b1 and b2
        Livewire::actingAs($financeUser)
            ->test(AccountsReceivableList::class)
            ->set('statusFilter', 'unpaid')
            ->assertSee($b1->code)
            ->assertSee($b2->code);

        // Test overdue filter contains b2 but NOT b1
        Livewire::actingAs($financeUser)
            ->test(AccountsReceivableList::class)
            ->set('statusFilter', 'overdue')
            ->assertSee($b2->code)
            ->assertDontSee($b1->code);

        // Test search with SQL wildcards (%, _) does not throw and executes safely
        Livewire::actingAs($financeUser)
            ->test(AccountsReceivableList::class)
            ->set('search', '%_test_wildcard%')
            ->assertSuccessful();
    }

    public function test_margin_report_with_zero_revenue_and_negative_margin(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation = $this->quotationFor($branch, $lead, $package),
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        $partner = Partner::firstOrFail();

        // Record high vendor cost of 20_000_000 IDR (higher than booking revenue)
        VendorPayment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'partner_id' => $partner->id,
            'description' => 'Luxury Villa Partner',
            'amount_minor' => 20_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 20_000_000,
            'paid_at' => now(),
            'created_by' => $financeUser->id,
        ]);

        $marginService = app(MarginReportService::class);
        $margin = $marginService->computeBookingMargin($booking);

        // Revenue < Vendor Cost -> negative margin
        $this->assertLessThan(0, $margin['actual_margin_idr']);
        $this->assertLessThan(0, $margin['margin_percentage']);
    }

    public function test_payment_list_rejection_modal_workflow(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        session(['active_branch_id' => $branch->id]);

        $booking = (new ConvertQuotationToBooking)->convert(
            $this->quotationFor($branch, $lead, $package),
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csUser
        );

        $payment = app(PaymentService::class)->recordPayment($booking, 1_000_000, 'IDR', creator: $csUser);

        // Open reject modal and submit with reason
        Livewire::actingAs($financeUser)
            ->test(PaymentList::class)
            ->call('openRejectModal', $payment->id)
            ->assertSet('showRejectModal', true)
            ->set('rejectionReason', 'Nominal mutasi bank tidak sesuai')
            ->call('rejectPayment')
            ->assertSet('showRejectModal', false)
            ->assertSet('actionSuccess', __('finance.payment_rejected_success'));

        $this->assertSame(Payment::STATUS_REJECTED, $payment->fresh()->status);
        $this->assertSame('Nominal mutasi bank tidak sesuai', $payment->fresh()->rejection_reason);
    }
}
