<?php

namespace Tests\Unit\Packaging;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Domain\Packaging\Models\Package;
use App\Domain\Packaging\PackageCalculator;
use App\Domain\Pricing\Models\MarginRule;
use App\Enums\InventoryItemType;
use App\Enums\PartnerType;
use App\Enums\PaymentChannel;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackagePaxTest extends TestCase
{
    use RefreshDatabase;

    public function test_pax_change_scales_non_room_items_but_not_room_qty(): void
    {
        $branch = Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
        $hotelPartner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);
        $vendor = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::VEHICLE_VENDOR, 'city' => $branch->name, 'name' => ['en' => 'Vendor'], 'is_active' => true]);

        $room = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $hotelPartner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);
        $van = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $vendor->id, 'type' => InventoryItemType::VEHICLE, 'name' => ['en' => 'Van'], 'is_active' => true]);

        Rate::create(['inventory_item_id' => $room->id, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'cost_minor' => 1_000_000, 'currency' => 'IDR']);
        Rate::create(['inventory_item_id' => $van->id, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'cost_minor' => 300_000, 'currency' => 'IDR']);

        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => null, 'margin_percent' => 0, 'is_active' => true]);
        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::VEHICLE, 'season_type' => null, 'margin_percent' => 0, 'is_active' => true]);

        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Test'], 'base_pax' => 2, 'duration_days' => 3]);
        $package->items()->create(['inventory_item_id' => $room->id, 'day_from' => 0, 'day_to' => 2, 'qty' => 1, 'nights' => 3, 'sort_order' => 0]);
        $package->items()->create(['inventory_item_id' => $van->id, 'day_from' => 0, 'day_to' => 0, 'qty' => 1, 'nights' => null, 'sort_order' => 1]);

        $calculator = new PackageCalculator;
        $date = new \DateTimeImmutable('2026-06-01');

        $at2Pax = $calculator->calculate($package->fresh(['items.inventoryItem']), 2, $date, PaymentChannel::BANK_TRANSFER, 'IDR');
        $at4Pax = $calculator->calculate($package->fresh(['items.inventoryItem']), 4, $date, PaymentChannel::BANK_TRANSFER, 'IDR');

        // room stays 1 room * 3 nights * 1_000_000 = 3_000_000 regardless of pax
        $roomResultAt2 = $at2Pax->itemResults[0];
        $roomResultAt4 = $at4Pax->itemResults[0];
        $this->assertSame(1, $roomResultAt2->effectiveQty);
        $this->assertSame(1, $roomResultAt4->effectiveQty);
        $this->assertSame($roomResultAt2->breakdown->costTotal->amountMinor, $roomResultAt4->breakdown->costTotal->amountMinor);

        // van scales: base_pax=2, qty=1 -> at 2 pax effectiveQty=1, at 4 pax effectiveQty=2
        $vanResultAt2 = $at2Pax->itemResults[1];
        $vanResultAt4 = $at4Pax->itemResults[1];
        $this->assertSame(1, $vanResultAt2->effectiveQty);
        $this->assertSame(2, $vanResultAt4->effectiveQty);

        $this->assertSame(3_000_000 + 300_000, $at2Pax->grandCostTotal->amountMinor);
        $this->assertSame(3_000_000 + 600_000, $at4Pax->grandCostTotal->amountMinor);
    }
}
