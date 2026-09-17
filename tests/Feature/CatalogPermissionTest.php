<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\InventoryItem;
use App\Livewire\Admin\Catalog\PartnerList;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_admin_cannot_open_the_partners_route(): void
    {
        $this->seed();
        $finance = User::where('email', 'finance.bali@indogate.com')->firstOrFail();

        $this->actingAs($finance)->get(route('admin.catalog.partners.index'))->assertForbidden();
    }

    public function test_finance_admin_cannot_mount_the_partner_list_component(): void
    {
        $this->seed();
        $finance = User::where('email', 'finance.bali@indogate.com')->firstOrFail();
        $this->actingAs($finance);

        try {
            (new PartnerList)->mount();
            $this->fail('Expected an AuthorizationException.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }
    }

    public function test_cs_admin_with_catalog_manage_can_view_partners(): void
    {
        $this->seed();
        $cs = User::where('email', 'cs.bali@indogate.com')->firstOrFail();

        $this->actingAs($cs)->get(route('admin.catalog.partners.index'))->assertOk();
    }

    /**
     * PRD/rule.md: direct-access to a record in another branch must 403,
     * not silently 404 via BranchScope. Route::bind('item', ...) in
     * AppServiceProvider bypasses the scope so InventoryItemPolicy gets a
     * chance to explicitly deny it.
     */
    public function test_cross_branch_inventory_item_access_is_403_not_404(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $jkt = Branch::where('code', 'JKT')->firstOrFail();
        $jktItem = InventoryItem::where('branch_id', $jkt->id)->firstOrFail();

        $this->actingAs($superAdmin);
        session(['current_branch_id' => $bali->id]);

        $this->get(route('admin.catalog.inventory-items.edit', $jktItem))->assertForbidden();
    }
}
