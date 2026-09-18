<?php

namespace Tests\Feature;

use App\Livewire\Admin\Pricing\MarginRuleForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MarginRuleDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_product_and_season_combination_is_rejected(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        // Seeder already creates ROOM/PEAK for Bali branch.
        Livewire::actingAs($admin)->test(MarginRuleForm::class)
            ->set('product_type', 'room')
            ->set('season_type', 'peak')
            ->set('margin_percent', 4000)
            ->call('save')
            ->assertHasErrors(['product_type']);
    }

    public function test_different_season_for_same_product_is_accepted(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();

        Livewire::actingAs($admin)->test(MarginRuleForm::class)
            ->set('product_type', 'ticket')
            ->set('season_type', 'peak')
            ->set('margin_percent', 1200)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('margin_rules', [
            'product_type' => 'ticket',
            'season_type' => 'peak',
            'margin_percent' => 1200,
        ]);
    }
}
