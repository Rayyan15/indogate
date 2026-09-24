<?php

namespace Tests\Unit\Packaging;

use App\Domain\Catalog\Models\BlackoutDate;
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

class PackageCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function branch(): Branch
    {
        return Branch::create(['name' => 'Bali', 'code' => 'BALI', 'timezone' => 'Asia/Makassar', 'is_active' => true]);
    }

    private function hotelRoom(Branch $branch, int $costMinor): InventoryItem
    {
        $partner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);
        $item = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);

        Rate::create(['inventory_item_id' => $item->id, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'cost_minor' => $costMinor, 'currency' => 'IDR']);

        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => null, 'margin_percent' => 2000, 'is_active' => true]);

        return $item;
    }

    public function test_two_hotel_package_with_different_nights_totals_correctly(): void
    {
        $branch = $this->branch();
        $hotelA = $this->hotelRoom($branch, 1_000_000);
        $hotelB = $this->hotelRoom($branch, 500_000);

        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Bali 7D'], 'base_pax' => 2, 'duration_days' => 7]);
        $package->items()->create(['inventory_item_id' => $hotelA->id, 'day_from' => 0, 'day_to' => 3, 'qty' => 1, 'nights' => 4, 'sort_order' => 0]);
        $package->items()->create(['inventory_item_id' => $hotelB->id, 'day_from' => 4, 'day_to' => 5, 'qty' => 1, 'nights' => 2, 'sort_order' => 1]);

        $result = (new PackageCalculator)->calculate($package->fresh(['items.inventoryItem']), 2, new \DateTimeImmutable('2026-06-01'), PaymentChannel::BANK_TRANSFER, 'IDR');

        // hotel A: 1_000_000 * 4 nights = 4_000_000 cost, +20% margin = 4_800_000
        // hotel B: 500_000 * 2 nights = 1_000_000 cost, +20% margin = 1_200_000
        $this->assertSame(5_000_000, $result->grandCostTotal->amountMinor);
        $this->assertSame(6_000_000, $result->grandSellIdrMinor->amountMinor);
        $this->assertCount(2, $result->itemResults);
        $this->assertFalse($result->itemResults[0]->rateMissing);
        $this->assertFalse($result->itemResults[1]->rateMissing);
    }

    public function test_line_with_no_matching_rate_is_soft_failed_but_others_still_total(): void
    {
        $branch = $this->branch();
        $hotelA = $this->hotelRoom($branch, 1_000_000);

        $noRateItem = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::VEHICLE_VENDOR, 'city' => $branch->name, 'name' => ['en' => 'Vendor'], 'is_active' => true])->id, 'type' => InventoryItemType::VEHICLE, 'name' => ['en' => 'Van'], 'is_active' => true]);

        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Test'], 'base_pax' => 2, 'duration_days' => 3]);
        $package->items()->create(['inventory_item_id' => $hotelA->id, 'day_from' => 0, 'day_to' => 2, 'qty' => 1, 'nights' => 3, 'sort_order' => 0]);
        $package->items()->create(['inventory_item_id' => $noRateItem->id, 'day_from' => 0, 'day_to' => 0, 'qty' => 1, 'nights' => null, 'sort_order' => 1]);

        $result = (new PackageCalculator)->calculate($package->fresh(['items.inventoryItem']), 2, new \DateTimeImmutable('2026-06-01'), PaymentChannel::BANK_TRANSFER, 'IDR');

        $this->assertCount(2, $result->itemResults);
        $this->assertFalse($result->itemResults[0]->rateMissing);
        $this->assertTrue($result->itemResults[1]->rateMissing);
        // total only reflects the resolvable hotel line: 1_000_000 * 3 nights = 3_000_000 cost + 20% = 3_600_000
        $this->assertSame(3_600_000, $result->grandSellIdrMinor->amountMinor);
    }

    public function test_room_nights_use_each_nights_rate_and_blackout_flags_line(): void
    {
        $branch = $this->branch();
        $room = $this->hotelRoom($branch, 1_000_000);
        // Peak rate from 2026-06-02 onward; latest matching row wins is not assumed, so split ranges.
        Rate::where('inventory_item_id', $room->id)->update(['valid_to' => '2026-06-01']);
        Rate::create(['inventory_item_id' => $room->id, 'valid_from' => '2026-06-02', 'valid_to' => '2026-12-31', 'cost_minor' => 2_000_000, 'currency' => 'IDR']);

        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'T'], 'base_pax' => 2, 'duration_days' => 3]);
        $package->items()->create(['inventory_item_id' => $room->id, 'day_from' => 0, 'day_to' => 1, 'qty' => 1, 'nights' => 2, 'sort_order' => 0]);
        $pkg = fn () => $package->fresh(['items.inventoryItem']);
        $calc = fn () => (new PackageCalculator)->calculate($pkg(), 2, new \DateTimeImmutable('2026-06-01'), PaymentChannel::BANK_TRANSFER, 'IDR');

        // 1_000_000 + 2_000_000 cost, +20%
        $this->assertSame(3_600_000, $calc()->grandSellIdrMinor->amountMinor);

        BlackoutDate::create(['inventory_item_id' => $room->id, 'date' => '2026-06-02']);
        $this->assertTrue($calc()->itemResults[0]->rateMissing);
    }
}
