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

/**
 * PRD M5 manual test #6: cost/margin must not be in the network payload
 * for a role without pricing.manage — not merely hidden in the rendered
 * HTML. Asserting on the component's public property (what Livewire
 * actually serializes into wire:snapshot) is the correct proof, not a
 * null-check (a null value is still a key in the payload).
 */
class MarginVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function setUpPackageWithComponent(): int
    {
        $branch = Branch::where('code', 'BALI')->first() ?? Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
        $partner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);
        $room = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);
        Rate::create(['inventory_item_id' => $room->id, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'cost_minor' => 1_000_000, 'currency' => 'IDR']);
        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => null, 'margin_percent' => 2000, 'is_active' => true]);

        return $room->id;
    }

    public function test_cs_admin_without_pricing_manage_never_receives_margin_keys(): void
    {
        $this->seed();
        $itemId = $this->setUpPackageWithComponent();
        $cs = User::role('CS Admin')->firstOrFail();
        $cs->givePermissionTo('catalog.manage');

        $component = Livewire::actingAs($cs)->test(PackageBuilder::class)
            ->call('recalculate', [[
                'id' => -1, 'inventory_item_id' => $itemId, 'name' => 'Room',
                'day_from' => 0, 'day_to' => 0, 'qty' => 1, 'nights' => 1, 'sort_order' => 0,
            ]], 2);

        $summary = $component->get('summary');

        $this->assertArrayNotHasKey('cost_total', $summary['grand']);
        $this->assertArrayNotHasKey('margin_minor', $summary['grand']);
        $this->assertArrayNotHasKey('channel_cost', $summary['grand']);
        $this->assertArrayNotHasKey('cost_total', $summary['items'][0]);
        $this->assertArrayNotHasKey('margin_percent', $summary['items'][0]);
        $this->assertArrayNotHasKey('margin_minor', $summary['items'][0]);
        $this->assertArrayHasKey('display_price', $summary['items'][0]);
    }

    public function test_super_admin_with_pricing_manage_receives_margin_keys(): void
    {
        $this->seed();
        $itemId = $this->setUpPackageWithComponent();
        $admin = User::role('Super Admin')->firstOrFail();

        $component = Livewire::actingAs($admin)->test(PackageBuilder::class)
            ->call('recalculate', [[
                'id' => -1, 'inventory_item_id' => $itemId, 'name' => 'Room',
                'day_from' => 0, 'day_to' => 0, 'qty' => 1, 'nights' => 1, 'sort_order' => 0,
            ]], 2);

        $summary = $component->get('summary');

        $this->assertArrayHasKey('cost_total', $summary['grand']);
        $this->assertSame(1_000_000, $summary['grand']['cost_total']);
        $this->assertSame(200_000, $summary['grand']['margin_minor']);
    }
}
