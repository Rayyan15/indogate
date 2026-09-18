<?php

namespace Tests\Feature;

use App\Livewire\Admin\Lead\LeadForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class LeadStatusTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_lost_status_without_reason_is_rejected(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail();
        $lead = $this->leadFor($this->baliBranch());

        $this->actingAs($cs);

        Livewire::test(LeadForm::class, ['lead' => $lead])
            ->set('status', 'lost')
            ->set('lost_reason', '')
            ->call('save')
            ->assertHasErrors(['lost_reason' => 'required']);

        $this->assertSame('new', $lead->fresh()->status->value);
    }

    public function test_lost_status_with_reason_is_accepted(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail();
        $lead = $this->leadFor($this->baliBranch());

        $this->actingAs($cs);

        Livewire::test(LeadForm::class, ['lead' => $lead])
            ->set('status', 'lost')
            ->set('lost_reason', 'Budget terlalu kecil')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('lost', $lead->fresh()->status->value);
    }
}
