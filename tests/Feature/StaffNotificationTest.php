<?php

namespace Tests\Feature;

use App\Console\Commands\SendScheduledNotifications;
use App\Domain\Booking\Models\PackageBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\GatewayCheckout;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Fleet\Models\DriverAssignment;
use App\Domain\Lead\Models\Lead;
use App\Models\Branch;
use App\Models\User;
use App\Notifications\DepartureWithoutDriver;
use App\Notifications\LeadFollowUpDue;
use App\Notifications\ManualPaymentPending;
use App\Notifications\NewWebsiteLead;
use App\Notifications\OnlinePaymentFailed;
use App\Notifications\OnlinePaymentReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class StaffNotificationTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['payments.provider' => 'simulator', 'payments.webhook_secret' => 'test-secret']);
        $this->seed();
        Notification::fake();
    }

    private function staff(Branch $branch, string $permission, bool $active = true): User
    {
        $role = Role::firstOrCreate(['name' => "Custom {$permission}", 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        return tap(User::factory()->create(['branch_id' => $branch->id, 'is_active' => $active]))->assignRole($role);
    }

    private function jakarta(): Branch
    {
        return Branch::whereKeyNot($this->baliBranch()->id)->firstOrFail();
    }

    private function booking(User $creator): PackageBooking
    {
        $branch = $this->baliBranch();
        $booking = $this->bookingFor($this->quotationFor($branch, $this->leadFor($branch), $this->packageWithOneHotelRoom($branch)));
        $booking->forceFill(['created_by' => $creator->id])->save();

        return $booking;
    }

    private function webhook(string $body)
    {
        return $this->call('POST', '/webhooks/payments/simulator', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => GatewayCheckout::sign($body),
        ], $body);
    }

    public function test_gateway_payment_notifies_creator_and_finance_of_same_branch_only_once(): void
    {
        $bali = $this->baliBranch();
        $cs = $this->staff($bali, 'lead.manage');
        $finance = $this->staff($bali, 'payment.verify');
        $inactive = $this->staff($bali, 'payment.verify', false);
        $otherBranch = $this->staff($this->jakarta(), 'payment.verify');

        $intent = app(GatewayCheckout::class)->startCheckout($this->booking($cs), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        $body = fn ($id) => json_encode(['event_id' => $id, 'type' => GatewayCheckout::EVENT_PAID, 'intent' => $intent->public_token, 'reference' => 'R']);
        $this->webhook($body('e1'))->assertOk();
        $this->webhook($body('e1'))->assertOk();
        $this->webhook($body('e2'))->assertOk(); // different event id, same intent

        Notification::assertSentToTimes($cs, OnlinePaymentReceived::class, 1);
        Notification::assertSentToTimes($finance, OnlinePaymentReceived::class, 1);
        Notification::assertNotSentTo([$inactive, $otherBranch], OnlinePaymentReceived::class);

        Notification::assertSentTo($finance, OnlinePaymentReceived::class, function ($n) use ($finance, $bali) {
            $data = $n->toArray($finance);

            return $data['type'] === 'online_payment_received'
                && $data['title_key'] === 'notifications.online_payment_received.title'
                && $data['branch_id'] === $bali->id
                && $data['severity'] === 'success'
                && str_contains($data['url'], '/admin/package-bookings/');
        });
    }

    public function test_failed_webhook_notifies_booking_creator(): void
    {
        $cs = $this->staff($this->baliBranch(), 'lead.manage');
        $intent = app(GatewayCheckout::class)->startCheckout($this->booking($cs), Payment::TYPE_DOWN_PAYMENT, 'bank_transfer', 'va_bca');
        $this->webhook(json_encode(['event_id' => 'f1', 'type' => GatewayCheckout::EVENT_FAILED, 'intent' => $intent->public_token]))->assertOk();

        Notification::assertSentTo($cs, OnlinePaymentFailed::class);
    }

    public function test_actor_is_not_notified_of_own_manual_payment(): void
    {
        $bali = $this->baliBranch();
        $actor = $this->staff($bali, 'payment.verify');
        $colleague = $this->staff($bali, 'payment.verify');
        $booking = $this->booking($actor);

        $this->actingAs($actor);
        app(PaymentService::class)->recordPayment($booking, 100_000, 'IDR', creator: $actor);

        Notification::assertSentTo($colleague, ManualPaymentPending::class);
        Notification::assertNotSentTo($actor, ManualPaymentPending::class);
    }

    public function test_website_lead_notifies_custom_role_with_lead_manage_in_branch(): void
    {
        $users = Branch::where('is_active', true)->get()->mapWithKeys(fn ($b) => [$b->id => $this->staff($b, 'lead.manage')]);

        $this->post('/en/leads', ['name' => 'Faisal', 'phone' => '+966501234567', 'website' => '']);

        $lead = Lead::latest('id')->firstOrFail();
        Notification::assertSentTo($users[$lead->branch_id], NewWebsiteLead::class);
        $users->toBase()->except($lead->branch_id)->each(fn ($u) => Notification::assertNotSentTo($u, NewWebsiteLead::class));
    }

    public function test_departure_job_only_selects_tomorrow_bookings_without_active_driver(): void
    {
        $dispatcher = $this->staff($this->baliBranch(), 'driver.assign');
        $tomorrow = $this->booking($dispatcher);
        $tomorrow->update(['departure_date' => now()->addDay()->toDateString()]);
        $assigned = $this->booking($dispatcher);
        $assigned->update(['departure_date' => now()->addDay()->toDateString()]);
        $later = $this->booking($dispatcher);
        $later->update(['departure_date' => now()->addDays(3)->toDateString()]);

        $this->fakeAssignment($assigned, DriverAssignment::STATUS_ASSIGNED);
        $this->fakeAssignment($tomorrow, DriverAssignment::STATUS_CANCELLED);

        $this->assertSame([$tomorrow->id], SendScheduledNotifications::departuresQuery()->pluck('id')->all());

        $this->artisan('notifications:scheduled departures')->assertSuccessful();
        Notification::assertSentToTimes($dispatcher, DepartureWithoutDriver::class, 1);
    }

    public function test_follow_up_job_notifies_assignee(): void
    {
        $bali = $this->baliBranch();
        $cs = $this->staff($bali, 'lead.manage');
        $lead = $this->leadFor($bali);
        $lead->update(['assigned_to' => $cs->id, 'follow_up_at' => now()->subHour()]);

        $this->artisan('notifications:scheduled follow-ups')->assertSuccessful();

        Notification::assertSentTo($cs, LeadFollowUpDue::class);
    }

    public function test_prune_removes_only_old_read_notifications(): void
    {
        $row = fn ($read, $age) => ['id' => (string) str()->uuid(), 'type' => 'x', 'notifiable_type' => User::class, 'notifiable_id' => 1, 'data' => '{}', 'read_at' => $read, 'created_at' => now()->subDays($age), 'updated_at' => now()];
        DB::table('notifications')->insert([$row(now(), 100), $row(null, 100), $row(now(), 10)]);

        $this->artisan('notifications:scheduled prune')->assertSuccessful();

        $this->assertSame(2, DB::table('notifications')->count());
    }

    private function fakeAssignment(PackageBooking $booking, string $status): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('driver_assignments');
        $row = array_intersect_key([
            'branch_id' => $booking->branch_id, 'booking_id' => $booking->id, 'status' => $status,
            'driver_id' => DB::table('drivers')->value('id') ?? DB::table('drivers')->insertGetId(['full_name' => 'D', 'created_at' => now(), 'updated_at' => now()]), 'date_from' => $booking->departure_date, 'date_to' => $booking->departure_date,
            'created_at' => now(), 'updated_at' => now(),
        ], array_flip($columns));
        DB::table('driver_assignments')->insert($row);
    }
}
