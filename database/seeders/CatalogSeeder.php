<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Enums\InventoryItemType;
use App\Enums\PartnerType;
use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * PRD M3 step 11: sample data for the next module (M4 pricing) to build
 * against. Deliberately small — a handful of partners/items/rates per
 * branch, not a full production catalog.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();

        foreach ([$bali, $jkt] as $branch) {
            $hotel = Partner::firstOrCreate(
                ['branch_id' => $branch->id, 'type' => PartnerType::HOTEL->value, 'city' => $branch->name],
                ['name' => ['en' => "{$branch->name} Grand Hotel"], 'contact' => 'reservations@example.com', 'is_active' => true]
            );
            $hotel->setTranslation('name', 'en', "{$branch->name} Grand Hotel");
            $hotel->setTranslation('name', 'id', "Hotel Grand {$branch->name}");
            $hotel->setTranslation('name', 'ar', "فندق غراند {$branch->name}");
            $hotel->save();

            $vendor = Partner::firstOrCreate(
                ['branch_id' => $branch->id, 'type' => PartnerType::VEHICLE_VENDOR->value, 'city' => $branch->name],
                ['name' => ['en' => "{$branch->name} Fleet Services"], 'contact' => 'fleet@example.com', 'is_active' => true]
            );
            $vendor->setTranslation('name', 'en', "{$branch->name} Fleet Services");
            $vendor->setTranslation('name', 'id', "Layanan Armada {$branch->name}");
            $vendor->setTranslation('name', 'ar', "خدمات أسطول {$branch->name}");
            $vendor->save();

            $room = InventoryItem::firstOrCreate(
                ['branch_id' => $branch->id, 'partner_id' => $hotel->id, 'type' => InventoryItemType::ROOM->value],
                ['name' => ['en' => 'Deluxe Room'], 'capacity' => 2, 'is_active' => true]
            );
            $room->setTranslation('name', 'en', 'Deluxe Room');
            $room->setTranslation('name', 'id', 'Kamar Deluxe');
            $room->setTranslation('name', 'ar', 'غرفة ديلوكس');
            $room->setTranslation('description', 'en', 'Spacious room with garden view.');
            $room->setTranslation('description', 'id', 'Kamar luas dengan pemandangan taman.');
            $room->setTranslation('description', 'ar', 'غرفة واسعة بإطلالة على الحديقة.');
            $room->save();

            Rate::firstOrCreate([
                'inventory_item_id' => $room->id,
                'valid_from' => now()->startOfYear()->toDateString(),
            ], [
                'valid_to' => now()->endOfYear()->toDateString(),
                'cost_minor' => 850_000,
                'currency' => 'IDR',
            ]);

            $vehicle = InventoryItem::firstOrCreate(
                ['branch_id' => $branch->id, 'partner_id' => $vendor->id, 'type' => InventoryItemType::VEHICLE->value],
                ['name' => ['en' => 'Toyota Hiace'], 'capacity' => 6, 'is_active' => true]
            );
            $vehicle->setTranslation('name', 'en', 'Toyota Hiace');
            $vehicle->setTranslation('name', 'id', 'Toyota Hiace');
            $vehicle->setTranslation('name', 'ar', 'تويوتا هايس');
            $vehicle->save();

            Rate::firstOrCreate([
                'inventory_item_id' => $vehicle->id,
                'valid_from' => now()->startOfYear()->toDateString(),
            ], [
                'valid_to' => now()->endOfYear()->toDateString(),
                'cost_minor' => 600_000,
                'currency' => 'IDR',
            ]);
        }
    }
}
