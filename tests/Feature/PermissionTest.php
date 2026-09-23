<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_admin_reaches_leads_and_bookings_but_not_catalog_fleet_or_payments(): void
    {
        $this->seed();
        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $this->actingAs($cs)->get(route('admin.leads.index'))->assertOk();
        $this->actingAs($cs)->get(route('admin.package-bookings.index'))->assertOk();
        $this->actingAs($cs)->get(route('admin.hotels.index'))->assertForbidden();
        $this->actingAs($cs)->get(route('admin.fleet.drivers'))->assertForbidden();
        $this->actingAs($cs)->get(route('admin.finance.payments'))->assertForbidden();
        $this->actingAs($cs)->get(route('admin.finance.margin-report'))->assertForbidden();
    }

    public function test_finance_admin_reaches_payments_but_not_catalog(): void
    {
        $this->seed();
        $finance = User::where('email', 'finance.bali@indogate.com')->firstOrFail();

        $this->actingAs($finance)->get(route('admin.finance.payments'))->assertOk();
        $this->actingAs($finance)->get(route('admin.hotels.index'))->assertForbidden();
    }

    public function test_customer_cannot_reach_admin_area(): void
    {
        $this->seed();
        $customer = User::where('email', 'customer@indogate.com')->firstOrFail();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }
}
