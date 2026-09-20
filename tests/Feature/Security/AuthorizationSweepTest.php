<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationSweepTest extends TestCase
{
    use RefreshDatabase;

    /**
     * List of core administrative routes to audit against unauthorized access.
     */
    protected function adminRoutes(): array
    {
        return [
            ['GET', '/id/admin/dashboard'],
            ['GET', '/id/admin/reports'],
            ['GET', '/id/admin/security/audit-logs'],
            ['GET', '/id/admin/users'],
            ['GET', '/id/admin/leads'],
            ['GET', '/id/admin/fleet/drivers'],
            ['GET', '/id/admin/fleet/vehicles'],
            ['GET', '/id/admin/fleet/calendar'],
            ['GET', '/id/admin/finance/payments'],
            ['GET', '/id/admin/finance/receivables'],
            ['GET', '/id/admin/finance/vendor-payments'],
            ['GET', '/id/admin/pricing-rules'],
            ['GET', '/id/admin/pricing-engine/currencies'],
        ];
    }

    public function test_unauthenticated_requests_are_redirected_to_login(): void
    {
        $this->seed();

        foreach ($this->adminRoutes() as [$method, $uri]) {
            $response = $this->call($method, $uri);
            $this->assertTrue(
                $response->isRedirect() || $response->getStatusCode() === 401,
                "Route [{$uri}] must require authentication"
            );
        }
    }

    public function test_customer_role_is_forbidden_from_all_admin_routes(): void
    {
        $this->seed();
        $customer = User::role('Customer')->firstOrFail();

        foreach ($this->adminRoutes() as [$method, $uri]) {
            $response = $this->actingAs($customer)->call($method, $uri);
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Route [{$uri}] must return 403 Forbidden for Customer role"
            );
        }
    }

    public function test_super_admin_has_authorized_access_to_all_admin_routes(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();

        foreach ($this->adminRoutes() as [$method, $uri]) {
            $response = $this->actingAs($superAdmin)->call($method, $uri);
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Route [{$uri}] must be accessible by Super Admin"
            );
        }
    }
}
