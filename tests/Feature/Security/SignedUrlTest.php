<?php

namespace Tests\Feature\Security;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Booking\Models\BookingGuest;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Fleet\Services\AssignmentService;
use App\Enums\PaymentChannel;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class SignedUrlTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_passport_download_requires_valid_signed_url(): void
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

        $fakePath = 'passports/guest_test.jpg';
        Storage::disk('local')->put($fakePath, 'sample-passport-content');

        $guest = BookingGuest::create([
            'booking_id' => $booking->id,
            'name' => 'John Guest',
            'passport_number' => 'P123456',
            'passport_file' => $fakePath,
            'nationality' => 'US',
            'is_lead_guest' => true,
        ]);

        // 1. Direct unsigned request is blocked (HTTP 403)
        $unsignedUrl = route('admin.package-bookings.guests.passport-download', ['locale' => 'id', 'guest' => $guest]);
        $this->actingAs($csUser)->get($unsignedUrl)->assertForbidden();

        // 2. Tampered signed URL is blocked (HTTP 403)
        $validSignedUrl = URL::temporarySignedRoute(
            'admin.package-bookings.guests.passport-download',
            now()->addMinutes(15),
            ['locale' => 'id', 'guest' => $guest]
        );
        $tamperedUrl = $validSignedUrl.'&tampered=1';
        $this->actingAs($csUser)->get($tamperedUrl)->assertForbidden();

        // 3. Valid signed URL allows download (HTTP 200)
        $this->actingAs($csUser)->get($validSignedUrl)->assertOk();
    }

    public function test_payment_proof_download_requires_valid_signed_url(): void
    {
        Storage::fake('local');
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

        $proofFile = UploadedFile::fake()->image('transfer_receipt.jpg');
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 2_000_000,
            currency: 'IDR',
            type: Payment::TYPE_DOWN_PAYMENT,
            channel: PaymentChannel::BANK_TRANSFER->value,
            creator: $csUser,
            proofFile: $proofFile
        );

        // 1. Direct unsigned request is blocked (HTTP 403)
        $unsignedUrl = route('admin.finance.proofs.download', ['locale' => 'id', 'payment' => $payment]);
        $this->actingAs($financeUser)->get($unsignedUrl)->assertForbidden();

        // 2. Valid signed URL succeeds (HTTP 200)
        $validSignedUrl = URL::temporarySignedRoute(
            'admin.finance.proofs.download',
            now()->addMinutes(15),
            ['locale' => 'id', 'payment' => $payment]
        );
        $this->actingAs($financeUser)->get($validSignedUrl)->assertOk();
    }

    public function test_fleet_duty_letter_requires_valid_signed_url(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $csUser = User::role('CS Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addDays(5)->toDateString(),
            returnDate: now()->addDays(8)->toDateString(),
            actor: $csUser
        );

        $driver = Driver::create([
            'branch_id' => $branch->id,
            'name' => 'Made Driver',
            'full_name' => 'I Made Driver',
            'phone' => '+62812345678',
            'gender' => 'male',
            'languages' => ['id'],
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'branch_id' => $branch->id,
            'plate_number' => 'DK 1234 AB',
            'type' => 'Innova',
            'capacity' => 6,
            'is_active' => true,
        ]);

        $assignmentService = app(AssignmentService::class);
        $assignment = $assignmentService->assign(
            booking: $booking,
            driver: $driver,
            vehicle: $vehicle,
            dateFrom: $booking->departure_date->toDateString(),
            dateTo: $booking->return_date->toDateString()
        );

        // 1. Direct unsigned request is blocked (HTTP 403)
        $unsignedUrl = route('admin.fleet.assignments.duty-letter', ['locale' => 'id', 'assignment' => $assignment]);
        $this->actingAs($csUser)->get($unsignedUrl)->assertForbidden();

        // 2. Valid signed URL succeeds (HTTP 200)
        $validSignedUrl = URL::temporarySignedRoute(
            'admin.fleet.assignments.duty-letter',
            now()->addMinutes(30),
            ['locale' => 'id', 'assignment' => $assignment]
        );
        $this->actingAs($csUser)->get($validSignedUrl)->assertOk();
    }
}
