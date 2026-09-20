<?php

namespace Tests\Feature\Reporting;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Enums\PaymentChannel;
use App\Livewire\Admin\Dashboard\DashboardOverview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_dashboard_renders_for_authorized_users(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(DashboardOverview::class);
    }

    public function test_dashboard_overview_livewire_component_computes_correct_metrics(): void
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
            amountMinor: 2_500_000,
            currency: 'IDR',
            type: Payment::TYPE_DOWN_PAYMENT,
            channel: PaymentChannel::BANK_TRANSFER->value,
            creator: $csUser
        );
        $paymentService->verifyPayment($payment, $financeUser);

        Livewire::actingAs($financeUser)
            ->test(DashboardOverview::class)
            ->assertSet('period', DashboardMetricsService::PERIOD_THIS_MONTH)
            ->assertSee('2.500.000')
            ->assertSee($booking->code);
    }

    public function test_dashboard_period_switching_updates_metrics(): void
    {
        $this->seed();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        Livewire::actingAs($financeUser)
            ->test(DashboardOverview::class)
            ->set('period', DashboardMetricsService::PERIOD_ALL_TIME)
            ->assertSet('period', DashboardMetricsService::PERIOD_ALL_TIME)
            ->set('period', DashboardMetricsService::PERIOD_TODAY)
            ->assertSet('period', DashboardMetricsService::PERIOD_TODAY);
    }
}
