<?php

namespace Tests\Feature;

use App\Domain\Catalog\Models\Partner;
use App\Livewire\Admin\Catalog\PartnerForm;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartnerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_partner(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $bali = Branch::where('code', 'BALI')->firstOrFail();
        $this->actingAs($superAdmin);
        session(['current_branch_id' => $bali->id]);

        Livewire::test(PartnerForm::class)
            ->call('openForCreate')
            ->set('name.en', 'Seaside Villas')
            ->set('name.id', 'Vila Tepi Pantai')
            ->set('name.ar', 'فيلات ساحلية')
            ->set('type', 'villa')
            ->set('city', 'Bali')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('partners', ['type' => 'villa', 'city' => 'Bali']);
        $partner = Partner::where('type', 'villa')->firstOrFail();
        $this->assertSame('Seaside Villas', $partner->getTranslation('name', 'en'));
        $this->assertSame('Vila Tepi Pantai', $partner->getTranslation('name', 'id'));
    }

    public function test_super_admin_can_update_and_deactivate_a_partner(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();
        $partner = Partner::first();

        Livewire::actingAs($superAdmin)->test(PartnerForm::class)
            ->call('openForEdit', $partner->id)
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($partner->fresh()->is_active);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->seed();
        $superAdmin = User::role('Super Admin')->firstOrFail();

        Livewire::actingAs($superAdmin)->test(PartnerForm::class)
            ->call('openForCreate')
            ->set('type', 'hotel')
            ->call('save')
            ->assertHasErrors(['name.en', 'name.id', 'name.ar']);
    }
}
