<?php

namespace Tests\Feature;

use App\Livewire\Admin\Pricing\PricingSimulator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PricingSimulatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_override_without_reason_is_rejected(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        Livewire::actingAs($admin)->test(PricingSimulator::class)
            ->set('use_override', true)
            ->set('override_amount', 500000)
            ->set('override_reason', '')
            ->call('calculate')
            ->assertHasErrors(['override_reason']);
    }

    public function test_override_with_reason_succeeds_and_is_logged(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        Livewire::actingAs($admin)->test(PricingSimulator::class)
            ->set('departure_date', '2026-07-15')
            ->set('use_override', true)
            ->set('override_amount', 500000)
            ->set('override_reason', 'Diskon negosiasi khusus agen')
            ->call('calculate')
            ->assertHasNoErrors()
            ->assertSet('result.overridden', true)
            ->assertSet('result.display_price', 500000);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'pricing',
            'description' => 'Manual price override applied via Pricing Simulator',
        ]);
    }

    public function test_calculation_without_matching_rule_shows_error(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        Livewire::actingAs($admin)->test(PricingSimulator::class)
            ->set('departure_date', '2026-10-18')
            ->call('calculate')
            ->assertSet('result', null);
    }
}
