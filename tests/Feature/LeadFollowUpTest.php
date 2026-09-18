<?php

namespace Tests\Feature;

use App\Domain\Lead\Models\Lead;
use App\Livewire\Admin\Lead\LeadForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class LeadFollowUpTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_follow_up_date_is_saved_through_the_form(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail();
        $lead = $this->leadFor($this->baliBranch());

        Livewire::actingAs($cs)->test(LeadForm::class, ['lead' => $lead])
            ->set('follow_up_at', '2026-01-01T09:00')
            ->call('save');

        $this->assertSame('2026-01-01 09:00', $lead->fresh()->follow_up_at->format('Y-m-d H:i'));
    }

    public function test_due_only_filter_excludes_won_and_lost_leads(): void
    {
        $this->seed();
        $branch = $this->baliBranch();

        $overdueOpen = Lead::create([
            'branch_id' => $branch->id, 'name' => 'Open Overdue', 'phone' => '1', 'locale' => 'id',
            'source' => 'manual', 'status' => 'new', 'follow_up_at' => now()->subDay(),
        ]);
        $overdueWon = Lead::create([
            'branch_id' => $branch->id, 'name' => 'Won Overdue', 'phone' => '2', 'locale' => 'id',
            'source' => 'manual', 'status' => 'won', 'follow_up_at' => now()->subDay(),
        ]);
        $future = Lead::create([
            'branch_id' => $branch->id, 'name' => 'Future', 'phone' => '3', 'locale' => 'id',
            'source' => 'manual', 'status' => 'new', 'follow_up_at' => now()->addWeek(),
        ]);

        $this->assertTrue($overdueOpen->fresh()->isFollowUpDue());
        $this->assertFalse($overdueWon->fresh()->isFollowUpDue());
        $this->assertFalse($future->fresh()->isFollowUpDue());
    }
}
