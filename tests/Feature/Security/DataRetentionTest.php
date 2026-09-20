<?php

namespace Tests\Feature\Security;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Booking\Models\BookingGuest;
use App\Domain\Security\Services\DataRetentionService;
use App\Enums\BookingStatus;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class DataRetentionTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_expired_identity_documents_are_purged(): void
    {
        Storage::fake('local');
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $csUser = User::role('CS Admin')->firstOrFail();

        // 1. Booking completed 100 days ago (expired retention)
        $expiredBooking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->subDays(105)->toDateString(),
            returnDate: now()->subDays(100)->toDateString(),
            actor: $csUser
        );
        $expiredBooking->update(['status' => BookingStatus::COMPLETED->value]);

        $expiredFilePath = 'passports/guest_old.jpg';
        Storage::disk('local')->put($expiredFilePath, 'old-passport-content');

        $expiredGuest = BookingGuest::create([
            'booking_id' => $expiredBooking->id,
            'name' => 'Expired Guest',
            'passport_number' => 'EXP-12345',
            'passport_file' => $expiredFilePath,
            'nationality' => 'SA',
        ]);

        // 2. Active booking (departure next week, within retention)
        $activeBooking = (new ConvertQuotationToBooking)->convert(
            $this->quotationFor($branch, $this->leadFor($branch), $package),
            departureDate: now()->addDays(7)->toDateString(),
            returnDate: now()->addDays(10)->toDateString(),
            actor: $csUser
        );

        $activeFilePath = 'passports/guest_active.jpg';
        Storage::disk('local')->put($activeFilePath, 'active-passport-content');

        $activeGuest = BookingGuest::create([
            'booking_id' => $activeBooking->id,
            'name' => 'Active Guest',
            'passport_number' => 'ACT-99999',
            'passport_file' => $activeFilePath,
            'nationality' => 'ID',
        ]);

        // Run purge command
        $this->artisan('security:purge-expired-data --days=90')
            ->assertSuccessful();

        // Expired document must be deleted
        Storage::disk('local')->assertMissing($expiredFilePath);
        $freshExpiredGuest = BookingGuest::findOrFail($expiredGuest->id);
        $this->assertNull($freshExpiredGuest->passport_file);
        $this->assertNull($freshExpiredGuest->passport_number);

        // Active document must remain untouched
        Storage::disk('local')->assertExists($activeFilePath);
        $freshActiveGuest = BookingGuest::findOrFail($activeGuest->id);
        $this->assertEquals('ACT-99999', $freshActiveGuest->passport_number);
        $this->assertEquals($activeFilePath, $freshActiveGuest->passport_file);

        // Verify activity log recorded the purge action
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
        ]);
    }

    public function test_customer_data_anonymization_under_uu_pdp(): void
    {
        $this->seed();
        $user = User::factory()->create([
            'name' => 'Ali Al-Ghamdi',
            'email' => 'ali@example.com',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'full_name' => 'Ali Al-Ghamdi',
            'nationality' => 'SA',
            'passport_number' => 'SA-55667788',
        ]);

        $retentionService = app(DataRetentionService::class);
        $retentionService->anonymizeCustomer($customer, 'Requested via customer support form');

        // Customer record is soft deleted
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);

        $anonymizedCustomer = Customer::withTrashed()->find($customer->id);
        $this->assertEquals('ANONYMIZED_'.$customer->id, $anonymizedCustomer->full_name);
        $this->assertNull($anonymizedCustomer->passport_number);

        // Associated user account is sanitized and deactivated
        $freshUser = User::find($user->id);
        $this->assertEquals('ANONYMIZED_'.$user->id, $freshUser->name);
        $this->assertEquals("anonymized_{$user->id}@deleted.local", $freshUser->email);
        $this->assertFalse($freshUser->is_active);

        // Audit log must record the anonymization under UU PDP
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
            'description' => 'Customer personal data anonymized under UU PDP',
        ]);
    }
}
