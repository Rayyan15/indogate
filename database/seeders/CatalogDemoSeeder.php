<?php

namespace Database\Seeders;

use App\Domain\Catalog\Models\BlackoutDate;
use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\ItemMedia;
use App\Domain\Catalog\Models\Partner;
use App\Domain\Catalog\Models\Rate;
use App\Enums\InventoryItemType;
use App\Enums\PartnerType;
use App\Models\Branch;
use Illuminate\Database\Seeder;

/**
 * Demo data for M3 — NOT part of the default DatabaseSeeder chain. Run on
 * demand (`php artisan db:seed --class=CatalogDemoSeeder`, after the
 * normal seeders) when the CatalogSeeder's one-hotel-one-vehicle baseline
 * is too thin to show a flow — multiple partners per branch, every
 * product type PRD M4 prices against, a rate history (not just one
 * open-ended row), and a blackout date that actually blocks a real day.
 *
 * Fixed calendar dates throughout so re-running this seeder is
 * idempotent regardless of when it's run.
 */
class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Branch::all() as $branch) {
            $hotel = $this->partner($branch, PartnerType::HOTEL, "{$branch->name} Grand Hotel", "Hotel Grand {$branch->name}", "فندق غراند {$branch->name}");
            $villa = $this->partner($branch, PartnerType::VILLA, "{$branch->name} Private Villas", "Villa Pribadi {$branch->name}", "فيلات {$branch->name} الخاصة");
            $vendor = $this->partner($branch, PartnerType::VEHICLE_VENDOR, "{$branch->name} Fleet Services", "Layanan Armada {$branch->name}", "خدمات أسطول {$branch->name}");

            // Flow: two rooms at the same hotel with different cost tiers,
            // so a margin-rule preview or booking search has a real choice
            // to make instead of one item per type.
            $deluxe = $this->item($branch, $hotel, InventoryItemType::ROOM, 'Deluxe Room', 'Kamar Deluxe', 'غرفة ديلوكس', 2);
            $suite = $this->item($branch, $hotel, InventoryItemType::ROOM, 'Garden Suite', 'Suite Taman', 'جناح الحديقة', 4);
            $villaRoom = $this->item($branch, $villa, InventoryItemType::ROOM, 'One-Bedroom Villa', 'Villa Satu Kamar', 'فيلا بغرفة نوم واحدة', 3);

            $van = $this->item($branch, $vendor, InventoryItemType::VEHICLE, 'Toyota Hiace', 'Toyota Hiace', 'تويوتا هايس', 10);
            $car = $this->item($branch, $vendor, InventoryItemType::VEHICLE, 'Toyota Alphard', 'Toyota Alphard', 'تويوتا الفارد', 4);

            $ticket = $this->item($branch, $hotel, InventoryItemType::TICKET, 'Water Park Day Pass', 'Tiket Terusan Taman Air', 'تذكرة يوم كاملة لحديقة المياه', 1);
            $activity = $this->item($branch, $vendor, InventoryItemType::ACTIVITY, 'Sunset Snorkeling Trip', 'Trip Snorkeling Sunset', 'رحلة غطس عند الغروب', 8);

            // Flow: rate history — a rate that already expired, the one
            // currently in force, and one scheduled to take over next
            // year — instead of a single all-year row.
            $this->rateHistory($deluxe, 85_000_000);
            $this->rateHistory($suite, 145_000_000);
            $this->rateHistory($villaRoom, 220_000_000);
            $this->rateHistory($van, 60_000_000);
            $this->rateHistory($car, 95_000_000);
            $this->rateHistory($ticket, 15_000_000);
            $this->rateHistory($activity, 45_000_000);

            // Flow: blackout date — the deluxe room is closed for a real
            // maintenance day, so a booking search actually has something
            // to exclude.
            if (! BlackoutDate::where('inventory_item_id', $deluxe->id)->whereDate('date', '2026-10-10')->exists()) {
                BlackoutDate::create([
                    'inventory_item_id' => $deluxe->id,
                    'date' => '2026-10-10',
                    'reason' => 'Perawatan tahunan AC dan plumbing',
                ]);
            }

            // Flow: item media — a couple of placeholder image paths so
            // the gallery UI has something to loop over.
            foreach ([$deluxe, $suite, $van] as $item) {
                ItemMedia::firstOrCreate(
                    ['inventory_item_id' => $item->id, 'sort_order' => 0],
                    ['path' => "catalog-demo/{$item->type->value}-{$item->id}-1.jpg"],
                );
            }
        }

        $this->command?->info('CatalogDemoSeeder done — check Katalog > Mitra / Item Inventaris.');
    }

    private function partner(Branch $branch, PartnerType $type, string $en, string $id, string $ar): Partner
    {
        $partner = Partner::firstOrCreate(
            ['branch_id' => $branch->id, 'type' => $type->value, 'city' => $branch->name, 'name->en' => $en],
            ['name' => ['en' => $en], 'contact' => strtolower(str_replace(' ', '.', $en)).'@example.com', 'is_active' => true],
        );

        $partner->setTranslation('name', 'en', $en);
        $partner->setTranslation('name', 'id', $id);
        $partner->setTranslation('name', 'ar', $ar);
        $partner->save();

        return $partner;
    }

    private function item(Branch $branch, Partner $partner, InventoryItemType $type, string $en, string $id, string $ar, int $capacity): InventoryItem
    {
        $item = InventoryItem::firstOrCreate(
            ['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => $type->value, 'name->en' => $en],
            ['name' => ['en' => $en], 'capacity' => $capacity, 'is_active' => true],
        );

        $item->setTranslation('name', 'en', $en);
        $item->setTranslation('name', 'id', $id);
        $item->setTranslation('name', 'ar', $ar);
        $item->save();

        return $item;
    }

    private function rateHistory(InventoryItem $item, int $currentCostMinor): void
    {
        $this->rate($item, '2025-01-01', '2025-12-31', (int) ($currentCostMinor * 0.85));
        $this->rate($item, '2026-01-01', '2026-12-31', $currentCostMinor);
        $this->rate($item, '2027-01-01', '2027-12-31', (int) ($currentCostMinor * 1.1));
    }

    /**
     * firstOrCreate([...'valid_from' => $date]) is not reliable here: on
     * SQLite (this project's local driver) a `date`-cast column is stored
     * with a "00:00:00" time suffix on write, which a raw date-only string
     * in the lookup array never matches — every run would insert a fresh
     * duplicate. whereDate() normalizes the comparison across drivers.
     */
    private function rate(InventoryItem $item, string $validFrom, string $validTo, int $costMinor): void
    {
        $exists = Rate::where('inventory_item_id', $item->id)
            ->whereDate('valid_from', $validFrom)
            ->exists();

        if (! $exists) {
            Rate::create([
                'inventory_item_id' => $item->id,
                'valid_from' => $validFrom,
                'valid_to' => $validTo,
                'cost_minor' => $costMinor,
                'currency' => 'IDR',
            ]);
        }
    }
}
