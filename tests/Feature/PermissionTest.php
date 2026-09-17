<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_admin_reaches_catalog_but_not_payments(): void
    {
        $this->seed();
        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $this->actingAs($cs)->get(route('admin.hotels.index'))->assertOk();
        $this->actingAs($cs)->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_finance_admin_reaches_payments_but_not_catalog(): void
    {
        $this->seed();
        $finance = User::where('email', 'finance.bali@indogate.com')->firstOrFail();

        $this->actingAs($finance)->get(route('admin.payments.index'))->assertOk();
        $this->actingAs($finance)->get(route('admin.hotels.index'))->assertForbidden();
    }

    public function test_customer_cannot_reach_admin_area(): void
    {
        $this->seed();
        $customer = User::where('email', 'customer@indogate.com')->firstOrFail();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
    }
}
