<?php

namespace Tests\Feature\Packaging;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Domain\Pricing\Models\MarginRule;
use App\Enums\InventoryItemType;
use App\Enums\PartnerType;
use App\Livewire\Admin\Packaging\PackageBuilder;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    use RefreshDatabase;

    private function roomWithRate(Branch $branch): InventoryItem
    {
        $partner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);
        $room = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);
        Rate::create(['inventory_item_id' => $room->id, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'cost_minor' => 1_000_000, 'currency' => 'IDR']);
        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => null, 'margin_percent' => 1000, 'is_active' => true]);

        return $room;
    }

    public function test_recalculate_adds_and_removes_items_and_updates_summary(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $room = $this->roomWithRate($branch);

        $component = Livewire::actingAs($admin)->test(PackageBuilder::class)
            ->call('recalculate', [[
                'id' => -1, 'inventory_item_id' => $room->id, 'name' => 'Room',
                'day_from' => 0, 'day_to' => 0, 'qty' => 1, 'nights' => 2, 'sort_order' => 0,
            ]], 2);

        $this->assertCount(1, $component->get('items'));
        $this->assertCount(1, $component->get('summary')['items']);

        $component->call('recalculate', [], 2);

        $this->assertCount(0, $component->get('items'));
        $this->assertCount(0, $component->get('summary')['items']);
    }

    public function test_save_persists_meta_and_items(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $room = $this->roomWithRate($branch);

        Livewire::actingAs($admin)->test(PackageBuilder::class)
            ->set('name.en', 'Bali Escape')
            ->set('name.id', 'Liburan Bali')
            ->set('name.ar', 'هروب بالي')
            ->set('base_pax', 2)
            ->call('recalculate', [[
                'id' => -1, 'inventory_item_id' => $room->id, 'name' => 'Room',
                'day_from' => 0, 'day_to' => 0, 'qty' => 1, 'nights' => 2, 'sort_order' => 0,
            ]], 2)
            ->call('save');

        $this->assertDatabaseHas('packages', ['base_pax' => 2]);
        $this->assertDatabaseHas('package_items', ['inventory_item_id' => $room->id, 'qty' => 1]);
    }
}
