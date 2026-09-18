<?php

namespace Tests\Feature\Packaging;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Packaging\Models\Package;
use App\Enums\InventoryItemType;
use App\Enums\PartnerType;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_creates_an_independent_copy(): void
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
        $partner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);
        $room = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);

        $original = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Bali Escape'], 'is_template' => true, 'base_pax' => 2, 'duration_days' => 5]);
        $original->items()->create(['inventory_item_id' => $room->id, 'day_from' => 0, 'day_to' => 4, 'qty' => 1, 'nights' => 5, 'sort_order' => 0]);
        $original->days()->create(['day_number' => 1, 'title' => ['en' => 'Arrival']]);

        $copy = $original->duplicate();

        $this->assertNotSame($original->id, $copy->id);
        $this->assertFalse($copy->is_template);
        $this->assertCount(1, $copy->items);
        $this->assertNotSame($original->items->first()->id, $copy->items->first()->id);
        $this->assertCount(1, $copy->days);
        $this->assertNotSame($original->days->first()->id, $copy->days->first()->id);

        // editing the copy must not touch the original
        $copy->items->first()->delete();
        $this->assertSame(1, $original->fresh()->items()->count());
        $this->assertSame(0, $copy->fresh()->items()->count());
    }
}
