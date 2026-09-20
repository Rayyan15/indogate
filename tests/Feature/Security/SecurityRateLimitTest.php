<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_failed_logins_trigger_rate_limiting_lockout(): void
    {
        $this->seed();
        $user = User::factory()->create([
            'email' => 'victim@target.com',
            'password' => bcrypt('correct-password'),
        ]);

        RateLimiter::clear(str('victim@target.com|127.0.0.1')->lower());

        // Perform 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/id/login', [
                'email' => 'victim@target.com',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt must be locked out by ensureIsNotRateLimited
        $response = $this->post('/id/login', [
            'email' => 'victim@target.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errorMessage);

        // Verify security activity log recorded the lockout
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'security',
            'description' => 'Authentication rate limit lockout triggered',
        ]);
    }

    public function test_public_lead_submission_is_rate_limited(): void
    {
        $this->seed();

        $payload = [
            'name' => 'Spam Bot',
            'phone' => '+6281234567890',
            'notes' => 'Spamming lead form',
        ];

        // Send 10 requests within limit
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/id/leads', $payload);
            $this->assertTrue(in_array($response->getStatusCode(), [200, 302]));
        }

        // 11th request must be throttled with HTTP 429 Too Many Requests
        $throttledResponse = $this->post('/id/leads', $payload);
        $throttledResponse->assertStatus(429);
    }
}
