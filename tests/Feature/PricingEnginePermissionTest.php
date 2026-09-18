<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingEnginePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cs_admin_cannot_reach_pricing_engine_screens(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail();

        $this->actingAs($cs)->get('/id/admin/pricing-engine/currencies')->assertForbidden();
        $this->actingAs($cs)->get('/id/admin/pricing-engine/simulator')->assertForbidden();
    }

    public function test_super_admin_can_reach_pricing_engine_screens(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        $this->actingAs($admin)->get('/id/admin/pricing-engine/currencies')->assertOk();
        $this->actingAs($admin)->get('/id/admin/pricing-engine/simulator')->assertOk();
    }
}
