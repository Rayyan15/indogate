<?php

namespace Tests\Feature;

use App\Domain\Lead\Models\Lead;
use App\Livewire\Admin\Lead\LeadForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class LeadCrudTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_lead_is_recorded_from_public_form(): void
    {
        $this->seed();

        $this->post('/id/leads', [
            'name' => 'Fatima',
            'phone' => '+966511111111',
            'country' => 'SA',
        ])->assertRedirect();

        $lead = Lead::firstOrFail();
        $this->assertSame('Fatima', $lead->name);
        $this->assertSame('website', $lead->source->value);
    }

    public function test_lead_is_recorded_manually_by_cs_admin(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail();
        $branch = $this->baliBranch();

        Livewire::actingAs($cs)->test(LeadForm::class)
            ->set([
                'name' => 'Omar',
                'phone' => '+966522222222',
                'source' => 'manual',
                'status' => 'new',
            ])
            ->call('save');

        $lead = Lead::where('name', 'Omar')->firstOrFail();
        $this->assertSame($branch->id, $lead->branch_id);
        $this->assertSame('manual', $lead->source->value);
    }

    public function test_honeypot_blocks_bot_submission(): void
    {
        $this->seed();

        $this->post('/id/leads', [
            'name' => 'Bot',
            'phone' => '000',
            'website' => 'http://spam.example',
        ]);

        $this->assertSame(0, Lead::count());
    }
}
