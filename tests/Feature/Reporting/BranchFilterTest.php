<?php

namespace Tests\Feature\Reporting;

use App\Domain\Booking\ConvertQuotationToBooking;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Livewire\Admin\Dashboard\DashboardOverview;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class BranchFilterTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_branch_scoping_isolates_report_and_dashboard_data(): void
    {
        $this->seed();
        $baliBranch = $this->baliBranch();
        $jakartaBranch = Branch::where('code', 'JKT')->firstOrFail();

        // 1. Create a booking in Bali
        $baliLead = $this->leadFor($baliBranch);
        $baliPackage = $this->packageWithOneHotelRoom($baliBranch);
        $baliQuotation = $this->quotationFor($baliBranch, $baliLead, $baliPackage);

        $csBali = User::role('CS Admin')->where('branch_id', $baliBranch->id)->firstOrFail();
        $baliBooking = (new ConvertQuotationToBooking)->convert(
            $baliQuotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csBali
        );

        // 2. Create a booking in Jakarta
        $jktLead = $this->leadFor($jakartaBranch);
        $jktPackage = $this->packageWithOneHotelRoom($jakartaBranch);
        $jktQuotation = $this->quotationFor($jakartaBranch, $jktLead, $jktPackage);

        $csJakarta = User::factory()->create([
            'branch_id' => $jakartaBranch->id,
            'is_active' => true,
        ]);
        $csJakarta->assignRole('CS Admin');

        $jktBooking = (new ConvertQuotationToBooking)->convert(
            $jktQuotation,
            departureDate: now()->addMonth()->toDateString(),
            returnDate: now()->addMonth()->addDays(3)->toDateString(),
            actor: $csJakarta
        );

        // 3. Bali CS Admin only sees Bali booking
        Livewire::actingAs($csBali)
            ->test(DashboardOverview::class)
            ->set('period', DashboardMetricsService::PERIOD_ALL_TIME)
            ->assertSee($baliBooking->code)
            ->assertDontSee($jktBooking->code);

        // 4. Jakarta CS Admin only sees Jakarta booking
        Livewire::actingAs($csJakarta)
            ->test(DashboardOverview::class)
            ->set('period', DashboardMetricsService::PERIOD_ALL_TIME)
            ->assertSee($jktBooking->code)
            ->assertDontSee($baliBooking->code);

        // 5. Super Admin can see both when branchId is null (Semua Cabang)
        $superAdmin = User::role('Super Admin')->firstOrFail();
        Livewire::actingAs($superAdmin)
            ->test(DashboardOverview::class)
            ->set('branchId', null)
            ->set('period', DashboardMetricsService::PERIOD_ALL_TIME)
            ->assertSee($baliBooking->code)
            ->assertSee($jktBooking->code)
            // Filter specifically to Bali
            ->set('branchId', $baliBranch->id)
            ->assertSee($baliBooking->code)
            ->assertDontSee($jktBooking->code);
    }
}
