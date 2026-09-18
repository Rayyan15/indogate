<?php

namespace Tests\Feature\Packaging;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Enums\InventoryItemType;
use App\Enums\PartnerType;
use App\Livewire\Admin\Packaging\PackageBuilder;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PRD M5 step 7: total nights must be consistent with the package's
 * declared duration.
 */
class ValidationTest extends TestCase
{
    use RefreshDatabase;

    private function roomItem(Branch $branch): InventoryItem
    {
        $partner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);

        return InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);
    }

    public function test_day_range_exceeding_duration_shows_warning(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $room = $this->roomItem($branch);

        $component = Livewire::actingAs($admin)->test(PackageBuilder::class)
            ->set('duration_days', 3)
            ->call('recalculate', [[
                'id' => -1, 'inventory_item_id' => $room->id, 'name' => 'Room',
                'day_from' => 0, 'day_to' => 5, 'qty' => 1, 'nights' => 5, 'sort_order' => 0,
            ]], 2);

        $this->assertNotNull($component->get('durationWarning'));
    }

    public function test_day_range_within_duration_has_no_warning(): void
    {
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $room = $this->roomItem($branch);

        $component = Livewire::actingAs($admin)->test(PackageBuilder::class)
            ->set('duration_days', 7)
            ->call('recalculate', [[
                'id' => -1, 'inventory_item_id' => $room->id, 'name' => 'Room',
                'day_from' => 0, 'day_to' => 3, 'qty' => 1, 'nights' => 4, 'sort_order' => 0,
            ]], 2);

        $this->assertNull($component->get('durationWarning'));
    }
}
