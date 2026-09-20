<?php

namespace Tests\Feature\Security;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Booking\Models\BookingGuest;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class EncryptionTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_customer_sensitive_columns_are_encrypted_in_database(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $plainPassport = 'PASS-987654321';

        $customer = Customer::create([
            'user_id' => $user->id,
            'full_name' => 'Sheikh Ahmed Al-Mansoor',
            'nationality' => 'SA',
            'passport_number' => $plainPassport,
        ]);

        // 1. Raw database inspection
        $rawRow = DB::table('customers')->where('id', $customer->id)->first();

        $this->assertNotNull($rawRow);
        $this->assertNotEquals($plainPassport, $rawRow->passport_number, 'Raw passport_number must not be plain text');
        $this->assertStringNotContainsString($plainPassport, (string) $rawRow->passport_number);

        // 2. Eloquent model decodes transparently
        $freshCustomer = Customer::findOrFail($customer->id);
        $this->assertEquals($plainPassport, $freshCustomer->passport_number);
    }

    public function test_booking_guest_passport_is_encrypted_in_database(): void
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

        $plainPassport = 'SA-99887766';

        $guest = BookingGuest::create([
            'booking_id' => $booking->id,
            'name' => 'Fatima Al-Zahra',
            'passport_number' => $plainPassport,
            'nationality' => 'SA',
            'is_lead_guest' => true,
        ]);

        $rawRow = DB::table('booking_guests')->where('id', $guest->id)->first();
        $this->assertNotNull($rawRow);
        $this->assertNotEquals($plainPassport, $rawRow->passport_number);
        $this->assertStringNotContainsString($plainPassport, (string) $rawRow->passport_number);

        $freshGuest = BookingGuest::findOrFail($guest->id);
        $this->assertEquals($plainPassport, $freshGuest->passport_number);
    }
}
