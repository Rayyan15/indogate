<?php

namespace Tests\Feature\Public;

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Branch::create([
            'id' => 1,
            'name' => 'Bali HQ',
            'code' => 'DPS',
            'is_active' => true,
        ]);
    }

    public function test_repeated_lead_submissions_are_rate_limited(): void
    {
        $payload = [
            'name' => 'Rapid User',
            'phone' => '+628123456789',
        ];

        // The route has throttle:10,1
        for ($i = 0; $i < 10; $i++) {
            $response = $this->post('/en/leads', $payload);
            $response->assertSessionHasNoErrors();
        }

        // 11th request should be throttled
        $response = $this->post('/en/leads', $payload);
        $response->assertStatus(429);
    }
}
