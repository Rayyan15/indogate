<?php

namespace Tests\Feature\Public;

use App\Domain\Lead\Models\Lead;
use App\Domain\Packaging\Models\Package;
use App\Enums\LeadSource;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestFormTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::create([
            'id' => 1,
            'name' => 'Bali HQ',
            'code' => 'DPS',
            'is_active' => true,
        ]);
    }

    public function test_valid_inquiry_form_submission_creates_lead(): void
    {
        $package = Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Ubud Sanctuary', 'id' => 'Suaka Ubud', 'ar' => 'أوبود'],
            'description' => ['en' => 'Desc', 'id' => 'Desc', 'ar' => 'Desc'],
            'duration_days' => 4,
            'starting_price_idr' => 12000000,
            'is_published' => true,
        ]);

        $payload = [
            'name' => 'Faisal Al-Otaibi',
            'phone' => '+966501234567',
            'country' => 'Saudi Arabia',
            'pax' => 4,
            'travel_date' => '2026-11-15',
            'notes' => 'Please provide private female chauffeur.',
            'package_id' => $package->id,
            'website' => '', // honeypot left blank
        ];

        $response = $this->post('/en/leads', $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'name' => 'Faisal Al-Otaibi',
            'phone' => '+966501234567',
            'country' => 'Saudi Arabia',
            'source' => LeadSource::WEBSITE->value,
            'status' => 'new',
        ]);

        $lead = Lead::where('phone', '+966501234567')->first();
        $this->assertNotNull($lead);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'type' => 'note',
        ]);
    }

    public function test_bot_submission_with_honeypot_is_silently_dropped_without_creating_lead(): void
    {
        $payload = [
            'name' => 'Spam Bot',
            'phone' => '+1234567890',
            'website' => 'https://spammy-bot-link.example.com', // honeypot triggered
        ];

        $response = $this->post('/en/leads', $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('leads', [
            'name' => 'Spam Bot',
        ]);
    }

    public function test_invalid_submission_missing_required_fields_is_rejected(): void
    {
        $response = $this->post('/en/leads', [
            'name' => '',
            'phone' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'phone']);
        $this->assertDatabaseCount('leads', 0);
    }
}
