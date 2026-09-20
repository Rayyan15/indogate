<?php

namespace Tests\Feature\Finance;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class FinanceProofAccessTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_unsigned_proof_url_is_forbidden(): void
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

        $payment = Payment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 1_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 1_000_000,
            'channel' => 'manual_transfer',
            'status' => Payment::STATUS_PENDING,
            'proof_file' => 'payment-proofs/test_proof.jpg',
        ]);

        // Direct unsigned GET request must return 403 Forbidden
        $response = $this->actingAs($csUser)->get(route('admin.finance.proofs.download', [
            'locale' => 'id',
            'payment' => $payment->id,
        ]));
        $response->assertStatus(403);
    }

    public function test_signed_proof_url_allows_download(): void
    {
        Storage::fake('local');
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

        // Store a fake file in private disk
        $file = UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');
        $storedPath = $file->store('payment-proofs', 'local');

        $payment = Payment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 1_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 1_000_000,
            'channel' => 'manual_transfer',
            'status' => Payment::STATUS_PENDING,
            'proof_file' => $storedPath,
        ]);

        // Generate valid temporary signed URL
        $signedUrl = URL::temporarySignedRoute(
            'admin.finance.proofs.download',
            now()->addMinutes(15),
            ['locale' => 'id', 'payment' => $payment->id]
        );

        $response = $this->actingAs($csUser)->get($signedUrl);
        $response->assertOk();
    }

    public function test_invoice_and_receipt_documents_render_successfully(): void
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

        $payment = Payment::create([
            'branch_id' => $branch->id,
            'booking_id' => $booking->id,
            'type' => Payment::TYPE_DOWN_PAYMENT,
            'amount_minor' => 1_000_000,
            'currency' => 'IDR',
            'fx_rate' => 1.0,
            'idr_equivalent_minor' => 1_000_000,
            'channel' => 'manual_transfer',
            'status' => Payment::STATUS_VERIFIED,
            'verified_by' => $financeUser->id,
            'verified_at' => now(),
        ]);

        // Set session active branch
        session(['active_branch_id' => $branch->id]);

        // Invoice
        $invoiceResp = $this->actingAs($financeUser)->get(route('admin.finance.invoice', [
            'locale' => 'id',
            'packageBooking' => $booking->id,
        ]));
        $invoiceResp->assertOk();
        $invoiceResp->assertSee($booking->code);

        // Receipt
        $receiptResp = $this->actingAs($financeUser)->get(route('admin.finance.receipt', [
            'locale' => 'id',
            'payment' => $payment->id,
        ]));
        $receiptResp->assertOk();
        $receiptResp->assertSee((string) $payment->id);
    }
}
