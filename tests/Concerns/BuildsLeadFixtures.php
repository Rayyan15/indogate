<?php

namespace Tests\Concerns;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Domain\Lead\Models\Lead;
use App\Domain\Packaging\Models\Package;
use App\Domain\Pricing\Models\MarginRule;
use App\Enums\InventoryItemType;
use App\Enums\LeadSource;
use App\Enums\PartnerType;
use App\Models\Branch;

trait BuildsLeadFixtures
{
    private function baliBranch(): Branch
    {
        return Branch::where('code', 'BALI')->firstOrFail();
    }

    private function leadFor(Branch $branch, string $locale = 'id'): Lead
    {
        return Lead::create([
            'branch_id' => $branch->id,
            'name' => 'Ahmad',
            'phone' => '+966500000000',
            'country' => 'SA',
            'locale' => $locale,
            'source' => LeadSource::MANUAL,
            'status' => 'new',
        ]);
    }

    private function packageWithOneHotelRoom(Branch $branch, int $costMinor = 1_000_000): Package
    {
        $partner = Partner::create(['branch_id' => $branch->id, 'type' => PartnerType::HOTEL, 'city' => $branch->name, 'name' => ['en' => 'Hotel'], 'is_active' => true]);
        $item = InventoryItem::create(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => InventoryItemType::ROOM, 'name' => ['en' => 'Room'], 'is_active' => true]);

        Rate::create(['inventory_item_id' => $item->id, 'valid_from' => '2026-01-01', 'valid_to' => '2026-12-31', 'cost_minor' => $costMinor, 'currency' => 'IDR']);
        MarginRule::withoutGlobalScopes()->create(['branch_id' => $branch->id, 'product_type' => InventoryItemType::ROOM, 'season_type' => null, 'margin_percent' => 2000, 'is_active' => true]);

        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Bali 3D'], 'base_pax' => 2, 'duration_days' => 3]);
        $package->items()->create(['inventory_item_id' => $item->id, 'day_from' => 0, 'day_to' => 2, 'qty' => 1, 'nights' => 3, 'sort_order' => 0]);

        return $package->fresh(['items.inventoryItem']);
    }
}
