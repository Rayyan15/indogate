<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Livewire\Admin\Catalog\InventoryItemManager;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RateOverlapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A fresh item with no pre-existing rate — CatalogSeeder's own sample
     * item already carries a full-year rate, which would otherwise
     * collide with every date range these tests try.
     */
    private function freshItem(): InventoryItem
    {
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $partner = Partner::where('branch_id', $branch->id)->firstOrFail();

        return InventoryItem::create([
            'branch_id' => $branch->id,
            'partner_id' => $partner->id,
            'type' => 'room',
            'name' => ['en' => 'Overlap Test Room'],
            'is_active' => true,
        ]);
    }

    public function test_overlapping_rate_period_is_rejected(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $item = $this->freshItem();

        Rate::create([
            'inventory_item_id' => $item->id,
            'valid_from' => '2026-06-01',
            'valid_to' => '2026-08-31',
            'cost_minor' => 100000000,
            'currency' => 'IDR',
        ]);

        Livewire::actingAs($superAdmin)->test(InventoryItemManager::class, ['item' => $item])
            ->set('rate_valid_from', '2026-08-01')
            ->set('rate_valid_to', '2026-09-30')
            ->set('rate_cost_minor', 120000000)
            ->call('addRate')
            ->assertHasErrors(['rate_valid_to']);

        $this->assertSame(1, Rate::where('inventory_item_id', $item->id)->count());
    }

    public function test_non_overlapping_rate_period_is_accepted(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $item = $this->freshItem();

        Rate::create([
            'inventory_item_id' => $item->id,
            'valid_from' => '2026-06-01',
            'valid_to' => '2026-08-31',
            'cost_minor' => 100000000,
            'currency' => 'IDR',
        ]);

        Livewire::actingAs($superAdmin)->test(InventoryItemManager::class, ['item' => $item])
            ->set('rate_valid_from', '2026-09-01')
            ->set('rate_valid_to', '2026-09-30')
            ->set('rate_cost_minor', 120000000)
            ->call('addRate')
            ->assertHasNoErrors();

        $this->assertSame(2, Rate::where('inventory_item_id', $item->id)->count());
    }
}
