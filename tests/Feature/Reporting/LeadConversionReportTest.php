<?php

namespace Tests\Feature\Reporting;

use App\Domain\Lead\Models\Lead;
use App\Domain\Reporting\Services\DashboardMetricsService;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Livewire\Admin\Reporting\ReportsCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class LeadConversionReportTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_lead_conversion_funnel_and_lost_reasons_calculated_accurately(): void
    {
        $this->seed();
        $branch = $this->baliBranch();
        $csUser = User::role('CS Admin')->firstOrFail();

        // Create 4 leads: 1 Won, 2 Lost (with reasons), 1 New
        Lead::create([
            'branch_id' => $branch->id,
            'name' => 'Lead Satu',
            'phone' => '+62811111111',
            'status' => LeadStatus::WON,
            'source' => LeadSource::WEBSITE,
        ]);

        Lead::create([
            'branch_id' => $branch->id,
            'name' => 'Lead Dua',
            'phone' => '+62822222222',
            'status' => LeadStatus::LOST,
            'lost_reason' => 'Harga Terlalu Mahal',
            'source' => LeadSource::MANUAL,
        ]);

        Lead::create([
            'branch_id' => $branch->id,
            'name' => 'Lead Tiga',
            'phone' => '+62833333333',
            'status' => LeadStatus::LOST,
            'lost_reason' => 'Jadwal Pesawat Bentrok',
            'source' => LeadSource::MANUAL,
        ]);

        Lead::create([
            'branch_id' => $branch->id,
            'name' => 'Lead Empat',
            'phone' => '+62844444444',
            'status' => LeadStatus::NEW,
            'source' => LeadSource::WEBSITE,
        ]);

        $service = new DashboardMetricsService;
        $funnel = $service->getLeadFunnelMetrics($branch->id, null, null);

        $this->assertSame(4, $funnel['total_leads']);
        $this->assertSame(1, $funnel['won_count']);
        $this->assertSame(2, $funnel['lost_count']);
        $this->assertSame(25.0, $funnel['conversion_rate']); // 1 won out of 4 = 25%

        $this->assertArrayHasKey('Harga Terlalu Mahal', $funnel['lost_reasons']);
        $this->assertSame(1, $funnel['lost_reasons']['Harga Terlalu Mahal']);
        $this->assertArrayHasKey('Jadwal Pesawat Bentrok', $funnel['lost_reasons']);
        $this->assertSame(1, $funnel['lost_reasons']['Jadwal Pesawat Bentrok']);

        // Check Livewire rendering
        Livewire::actingAs($csUser)
            ->test(ReportsCenter::class)
            ->set('activeTab', 'lead_conversion')
            ->assertSee('25%')
            ->assertSee('Harga Terlalu Mahal')
            ->assertSee('Lead Satu');
    }
}
