<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\VendorPayment;
use App\Domain\Finance\Services\MarginReportService;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Pricing\Models\PaymentChannelCost;
use App\Enums\PaymentChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class MarginReportTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_margin_report_service_computes_true_margin_deducting_mdr_and_vendor_costs(): void
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

        // Configure international card MDR: 3% + 5000 IDR flat
        PaymentChannelCost::updateOrCreate(
            ['channel' => PaymentChannel::INTERNATIONAL_CARD],
            ['percent_fee' => 300, 'flat_fee_minor' => 5_000, 'is_active' => true]
        );

        $paymentService = app(PaymentService::class);

        // Record 10_000_000 IDR payment via INTERNATIONAL_CARD
        // Channel fee: 3% of 10m = 300_000 + 5_000 = 305_000
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 10_000_000,
            currency: 'IDR',
            type: Payment::TYPE_FULL_PAYMENT,
            channel: PaymentChannel::INTERNATIONAL_CARD->value,
            creator: $csUser
        );

        $this->assertSame(305_000, $payment->channel_fee_minor);

        $paymentService->verifyPayment($payment, $financeUser);

        $partner = Partner::firstOrFail();

        // Record a vendor payment for 6_000_000 IDR
        VendorPayment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'partner_id' => $partner->id,
            'description' => 'Grand Inna Bali Hotel',
            'amount_minor' => 6_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 6_000_000,
            'paid_at' => now(),
            'created_by' => $financeUser->id,
        ]);

        $marginService = app(MarginReportService::class);
        $margin = $marginService->computeBookingMargin($booking);

        // Gross Revenue: 10_000_000
        // Channel Fees: 305_000
        // Net Revenue: 9_695_000
        // Vendor Costs: 6_000_000
        // Actual Margin: 3_695_000
        // Margin Percentage: (3_695_000 / 10_000_000) * 100 = 36.95%
        $this->assertSame(10_000_000, $margin['gross_revenue_idr']);
        $this->assertSame(305_000, $margin['channel_fees_idr']);
        $this->assertSame(9_695_000, $margin['net_revenue_idr']);
        $this->assertSame(6_000_000, $margin['vendor_costs_idr']);
        $this->assertSame(3_695_000, $margin['actual_margin_idr']);
        $this->assertSame(36.95, $margin['margin_percentage']);
    }

    public function test_margin_summary_aggregates_multiple_bookings(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);

        $csUser = User::role('CS Admin')->firstOrFail();
        $financeUser = User::role('Finance Admin')->firstOrFail();
        $paymentService = app(PaymentService::class);

        // Booking 1
        $q1 = $this->quotationFor($branch, $lead, $package);
        $b1 = (new ConvertQuotationToBooking)->convert($q1, departureDate: '2026-07-01', returnDate: '2026-07-04', actor: $csUser);
        $p1 = $paymentService->recordPayment($b1, 5_000_000, 'IDR', channel: 'bank_transfer', creator: $csUser);
        $paymentService->verifyPayment($p1, $financeUser);

        // Booking 2
        $q2 = $this->quotationFor($branch, $lead, $package);
        $b2 = (new ConvertQuotationToBooking)->convert($q2, departureDate: '2026-07-10', returnDate: '2026-07-13', actor: $csUser);
        $p2 = $paymentService->recordPayment($b2, 5_000_000, 'IDR', channel: 'bank_transfer', creator: $csUser);
        $paymentService->verifyPayment($p2, $financeUser);

        $marginService = app(MarginReportService::class);
        $summary = $marginService->computeSummary(new Collection([$b1, $b2]));

        $this->assertSame(10_000_000, $summary['total_gross_idr']);
        $this->assertArrayHasKey('total_fees_idr', $summary);
        $this->assertArrayHasKey('total_net_idr', $summary);
        $this->assertArrayHasKey('total_vendor_idr', $summary);
        $this->assertArrayHasKey('total_margin_idr', $summary);
        $this->assertArrayHasKey('overall_margin_percentage', $summary);
    }
}
