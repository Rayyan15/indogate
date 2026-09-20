<?php

namespace Tests\Unit\Finance;

use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Models\PaymentIntent;
use App\Domain\Finance\Models\Refund;
use App\Domain\Finance\Providers\ManualTransferProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class PaymentProviderTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_provider_name_is_manual_transfer(): void
    {
        $provider = new ManualTransferProvider;
        $this->assertSame('manual_transfer', $provider->getName());
    }

    public function test_create_intent_stores_pending_intent(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $provider = new ManualTransferProvider;
        $intent = $provider->createIntent(
            booking: $booking,
            amountMinor: 5_000_000,
            currency: 'IDR',
            channel: 'manual_transfer',
            options: ['notes' => 'DP 30%']
        );

        $this->assertInstanceOf(PaymentIntent::class, $intent);
        $this->assertSame(PaymentIntent::STATUS_PENDING, $intent->status);
        $this->assertSame(5_000_000, $intent->amount_minor);
        $this->assertSame('IDR', $intent->currency);
        $this->assertSame('DP 30%', $intent->notes);
    }

    public function test_create_intent_rejects_non_positive_amount(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $provider = new ManualTransferProvider;

        $this->expectException(InvalidArgumentException::class);
        $provider->createIntent($booking, 0, 'IDR');
    }

    public function test_verify_payment_updates_status_and_timestamp(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $financeUser = User::role('Finance Admin')->firstOrFail();

        $payment = Payment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 3_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 3_000_000,
            'channel' => 'manual_transfer',
            'status' => Payment::STATUS_PENDING,
        ]);

        $provider = new ManualTransferProvider;
        $success = $provider->verifyPayment($payment, $financeUser);

        $this->assertTrue($success);
        $payment->refresh();
        $this->assertSame(Payment::STATUS_VERIFIED, $payment->status);
        $this->assertSame($financeUser->id, $payment->verified_by);
        $this->assertNotNull($payment->verified_at);
    }

    public function test_process_refund_creates_refund_record(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $financeUser = User::role('Finance Admin')->firstOrFail();

        $payment = Payment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 3_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 3_000_000,
            'channel' => 'manual_transfer',
            'status' => Payment::STATUS_VERIFIED,
            'verified_by' => $financeUser->id,
            'verified_at' => now(),
        ]);

        $provider = new ManualTransferProvider;
        $refund = $provider->processRefund($payment, 1_000_000, 'Customer cancelled hotel room', $financeUser);

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertSame(1_000_000, $refund->amount_minor);
        $this->assertSame('Customer cancelled hotel room', $refund->reason);
        $this->assertSame(Refund::STATUS_COMPLETED, $refund->status);
        $this->assertSame($financeUser->id, $refund->processed_by);
    }

    public function test_process_refund_rejects_empty_reason_or_invalid_amount(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $booking = $this->bookingFor($quotation);

        $financeUser = User::role('Finance Admin')->firstOrFail();

        $payment = Payment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 3_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 3_000_000,
            'channel' => 'manual_transfer',
            'status' => Payment::STATUS_VERIFIED,
            'verified_by' => $financeUser->id,
            'verified_at' => now(),
        ]);

        $provider = new ManualTransferProvider;

        try {
            $provider->processRefund($payment, 1_000_000, '   ', $financeUser);
            $this->fail('Expected exception for empty reason');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Alasan refund', $e->getMessage());
        }

        try {
            $provider->processRefund($payment, 4_000_000, 'Exceeding amount', $financeUser);
            $this->fail('Expected exception for exceeding amount');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Nominal refund tidak valid', $e->getMessage());
        }
    }
}
