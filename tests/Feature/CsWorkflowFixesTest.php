<?php

namespace Tests\Feature;

use App\Livewire\Admin\Lead\LeadForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsLeadFixtures;
use Tests\TestCase;

class CsWorkflowFixesTest extends TestCase
{
    use BuildsLeadFixtures, RefreshDatabase;

    public function test_starting_price_comes_from_pricing_engine(): void
    {
        $this->seed();
        $package = $this->packageWithOneHotelRoom($this->baliBranch());
        $package->update(['starting_price_idr' => 15_000_000]);

        $package->refreshStartingPrice();

        // 1 room x 3 nights x IDR 1.000.000 + 20% margin, whole rupiah (IDR has no minor digits).
        $price = $package->fresh()->starting_price_idr;
        $this->assertGreaterThanOrEqual(3_600_000, $price);
        $this->assertLessThan(4_000_000, $price);
    }

    public function test_lead_assignee_list_only_has_lead_staff(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail();

        Livewire::actingAs($cs)->test(LeadForm::class)
            ->assertViewHas('users', fn ($users) => $users->pluck('name')->contains('CS Bali')
                && ! $users->pluck('name')->contains('Finance Bali')
                && ! $users->pluck('name')->contains('Sample Customer'));
    }
}
