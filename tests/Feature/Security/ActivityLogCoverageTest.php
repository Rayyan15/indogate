<?php

namespace Tests\Feature\Security;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Fleet\Services\AssignmentService;
use App\Enums\PaymentChannel;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ActivityLogCoverageTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_auth_events_produce_activity_logs(): void
    {
        $this->seed();
        $user = User::role('Super Admin')->firstOrFail();

        // 1. Login event
        event(new Login('web', $user, false));
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'description' => 'User logged in successfully',
        ]);

        // 2. Logout event
        event(new Logout('web', $user));
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'causer_id' => $user->id,
            'description' => 'User logged out',
        ]);

        // 3. Failed login event
        event(new Failed('web', null, ['email' => 'hacker@unknown.local']));
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'auth',
            'description' => 'Failed login attempt',
        ]);

        // 4. Lockout event
        $request = Request::create('/id/login', 'POST', ['email' => 'bruteforce@target.local']);
        event(new Lockout($request));
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
            'description' => 'Authentication rate limit lockout triggered',
        ]);
    }

    public function test_financial_actions_produce_finance_activity_logs(): void
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

        // Record payment
        $payment = $paymentService->recordPayment(
            booking: $booking,
            amountMinor: 5_000_000,
            currency: 'IDR',
            type: Payment::TYPE_DOWN_PAYMENT,
            channel: PaymentChannel::BANK_TRANSFER->value,
            creator: $csUser
        );

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'finance',
            'description' => 'Payment recorded and awaiting verification',
            'subject_id' => $payment->id,
        ]);

        // Verify payment
        $paymentService->verifyPayment($payment, $financeUser);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'finance',
            'description' => 'Payment verified',
            'subject_id' => $payment->id,
        ]);

        // Refund payment
        $paymentService->refundPayment($payment, 1_000_000, 'Customer cancelled optional tour', $financeUser);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'finance',
            'description' => 'Payment refund processed',
        ]);
    }

    public function test_fleet_assignment_actions_produce_fleet_activity_logs(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $lead = $this->leadFor($branch);
        $package = $this->packageWithOneHotelRoom($branch);
        $quotation = $this->quotationFor($branch, $lead, $package);
        $csUser = User::role('CS Admin')->firstOrFail();

        $booking = (new ConvertQuotationToBooking)->convert(
            $quotation,
            departureDate: now()->addDays(10)->toDateString(),
            returnDate: now()->addDays(13)->toDateString(),
            actor: $csUser
        );

        $driver = Driver::create([
            'branch_id' => $branch->id,
            'name' => 'Wayan Driver',
            'full_name' => 'I Wayan Driver',
            'phone' => '+62811111111',
            'gender' => 'male',
            'languages' => ['id', 'en'],
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'branch_id' => $branch->id,
            'plate_number' => 'DK 9999 XX',
            'type' => 'HiAce',
            'capacity' => 12,
            'is_active' => true,
        ]);

        $assignmentService = app(AssignmentService::class);
        $this->actingAs($csUser);
        $assignment = $assignmentService->assign(
            booking: $booking,
            driver: $driver,
            vehicle: $vehicle,
            dateFrom: $booking->departure_date->toDateString(),
            dateTo: $booking->return_date->toDateString()
        );

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'fleet',
            'description' => "Penugasan driver {$driver->name} untuk booking {$booking->code}",
            'subject_id' => $assignment->id,
        ]);

        $assignmentService->cancel($assignment, 'Rescheduled flight itinerary');
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'fleet',
            'description' => "Penugasan driver {$driver->name} dibatalkan: Rescheduled flight itinerary",
            'subject_id' => $assignment->id,
        ]);
    }
}
