<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRedirectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: Laravel's default middlewarePriority list bubbles
     * Authenticate ahead of any custom alias not in that list, including
     * 'setLocale' — regardless of route registration order. Without
     * bootstrap/app.php pinning SetLocale before AuthenticatesRequests in
     * the priority list, URL::defaults(['locale' => ...]) never runs
     * before an unauthenticated request's redirect-to-login tries
     * route('login'), producing a 500 (UrlGenerationException: missing
     * 'locale') instead of a clean redirect. Found via manual browser
     * testing after a session went stale post migrate:fresh.
     */
    public function test_unauthenticated_access_to_a_protected_route_redirects_to_login_instead_of_500ing(): void
    {
        $this->get('/en/admin/dashboard')
            ->assertRedirect('/en/login');
    }
}
