<?php

namespace Tests\Feature\Reporting;

use App\Livewire\Admin\Dashboard\DashboardOverview;
use App\Livewire\Admin\Reporting\ReportsCenter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BuildsBookingFixtures;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class ReportPermissionTest extends TestCase
{
    use BuildsBookingFixtures, BuildsLeadFixtures, RefreshDatabase;

    public function test_customer_cannot_reach_reports_or_dashboard(): void
    {
        $this->seed();
        $customer = User::where('email', 'customer@indogate.com')->firstOrFail();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_finance_admin_sees_full_margin_and_financials(): void
    {
        $this->seed();
        $financeUser = User::role('Finance Admin')->firstOrFail();

        $this->actingAs($financeUser)->get(route('admin.reports.index'))->assertOk();

        Livewire::actingAs($financeUser)
            ->test(DashboardOverview::class)
            ->assertViewHas('canViewFinancials', true)
            ->assertSee(__('report.gross_revenue'))
            ->assertSee(__('report.net_margin'));

        Livewire::actingAs($financeUser)
            ->test(ReportsCenter::class)
            ->assertSet('activeTab', 'sales_margin')
            ->assertViewHas('canViewFinancials', true)
            ->assertSee(__('report.tab_sales_margin'));
    }

    public function test_cs_admin_has_restricted_financials_view_and_cannot_view_full_margins(): void
    {
        $this->seed();
        $csRole = Role::findByName('CS Admin');
        $csRole->revokePermissionTo('report.margin.view');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $csUser = User::role('CS Admin')->firstOrFail();

        Livewire::actingAs($csUser)
            ->test(DashboardOverview::class)
            ->assertViewHas('canViewFinancials', false)
            ->assertSee(__('report.restricted_financials'))
            ->assertSee(__('report.restricted_financials_note'));

        // On Reports Center, activeTab defaults to lead_conversion
        Livewire::actingAs($csUser)
            ->test(ReportsCenter::class)
            ->assertSet('activeTab', 'lead_conversion')
            ->assertViewHas('canViewFinancials', false)
            ->assertDontSee(__('report.tab_sales_margin'));
    }
}
