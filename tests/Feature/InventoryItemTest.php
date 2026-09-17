<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\InventoryItem;
use App\Domain\Catalog\Models\Partner;
use App\Livewire\Admin\Catalog\InventoryItemManager;
use App\Models\Branch;
use App\Models\User;
use App\Support\Branch\CurrentBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_is_linked_to_the_correct_partner(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $partner = Partner::where('branch_id', $bali->id)->firstOrFail();

        $this->actingAs($superAdmin);
        session(['current_branch_id' => $bali->id]);

        Livewire::test(InventoryItemManager::class)
            ->set('name.en', 'Ocean View Suite')
            ->set('name.id', 'Suite Pemandangan Laut')
            ->set('name.ar', 'جناح بإطلالة على المحيط')
            ->set('partner_id', $partner->id)
            ->set('type', 'room')
            ->call('save')
            ->assertHasNoErrors();

        $item = InventoryItem::where('partner_id', $partner->id)->latest('id')->firstOrFail();
        $this->assertSame($partner->id, $item->partner_id);
        $this->assertSame($bali->id, $item->branch_id);
    }

    public function test_item_from_another_branch_is_not_visible_after_switching(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();

        $jktItemId = InventoryItem::where('branch_id', $jkt->id)->firstOrFail()->id;

        $this->actingAs($superAdmin);
        CurrentBranch::switchTo($bali->id);

        $this->assertNull(InventoryItem::find($jktItemId));
    }
}
