<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Models\Branch;
use Database\Seeders\BranchSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslatableFieldTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_name_is_stored_in_three_locales(): void
    {
        $this->seed(BranchSeeder::class);
        $branch = Branch::firstOrFail();

        $partner = new Partner(['branch_id' => $branch->id, 'type' => 'hotel']);
        $partner->setTranslation('name', 'en', 'Sunset Resort');
        $partner->setTranslation('name', 'id', 'Resor Matahari Terbenam');
        $partner->setTranslation('name', 'ar', 'منتجع الغروب');
        $partner->save();

        $fresh = Partner::withoutGlobalScopes()->findOrFail($partner->id);
        $this->assertSame('Sunset Resort', $fresh->getTranslation('name', 'en'));
        $this->assertSame('Resor Matahari Terbenam', $fresh->getTranslation('name', 'id'));
        $this->assertSame('منتجع الغروب', $fresh->getTranslation('name', 'ar'));
    }

    public function test_inventory_item_name_and_description_are_stored_in_three_locales(): void
    {
        $this->seed(BranchSeeder::class);
        $branch = Branch::firstOrFail();

        $partner = Partner::create(['branch_id' => $branch->id, 'type' => 'hotel', 'name' => ['en' => 'Test Hotel']]);

        $item = new InventoryItem(['branch_id' => $branch->id, 'partner_id' => $partner->id, 'type' => 'room']);
        $item->setTranslation('name', 'en', 'Standard Room');
        $item->setTranslation('name', 'id', 'Kamar Standar');
        $item->setTranslation('name', 'ar', 'غرفة عادية');
        $item->setTranslation('description', 'en', 'A cozy standard room.');
        $item->setTranslation('description', 'id', 'Kamar standar yang nyaman.');
        $item->setTranslation('description', 'ar', 'غرفة عادية مريحة.');
        $item->save();

        $fresh = InventoryItem::withoutGlobalScopes()->findOrFail($item->id);
        $this->assertSame('Kamar Standar', $fresh->getTranslation('name', 'id'));
        $this->assertSame('Kamar standar yang nyaman.', $fresh->getTranslation('description', 'id'));
        $this->assertSame('غرفة عادية', $fresh->getTranslation('name', 'ar'));
    }
}
