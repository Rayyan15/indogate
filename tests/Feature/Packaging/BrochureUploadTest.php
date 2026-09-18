<?php

namespace Tests\Feature\Packaging;

use App\Domain\Packaging\Models\Package;
use App\Livewire\Admin\Packaging\PackageBuilder;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrochureUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_a_brochure_stores_it_and_updates_the_package(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Test'], 'base_pax' => 2, 'is_template' => false]);

        Livewire::actingAs($admin)->test(PackageBuilder::class, ['package' => $package])
            ->set('brochure', UploadedFile::fake()->create('brochure.pdf', 500, 'application/pdf'))
            ->call('uploadBrochure')
            ->assertHasNoErrors();

        $package->refresh();
        $this->assertNotNull($package->brochure_path);
        Storage::disk('public')->assertExists($package->brochure_path);
    }

    public function test_non_pdf_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $this->seed();
        $admin = User::role('Super Admin')->firstOrFail();
        $branch = Branch::where('code', 'BALI')->firstOrFail();
        $package = Package::create(['branch_id' => $branch->id, 'name' => ['en' => 'Test'], 'base_pax' => 2, 'is_template' => false]);

        Livewire::actingAs($admin)->test(PackageBuilder::class, ['package' => $package])
            ->set('brochure', UploadedFile::fake()->create('brochure.exe', 500, 'application/x-msdownload'))
            ->call('uploadBrochure')
            ->assertHasErrors(['brochure']);

        $this->assertNull($package->fresh()->brochure_path);
    }

    public function test_policy_denies_update_for_a_package_in_another_branch(): void
    {
        $this->seed();
        $cs = User::role('CS Admin')->firstOrFail(); // seeded to Bali
        $jkt = Branch::where('code', 'JKT')->firstOrFail();
        $package = Package::withoutGlobalScopes()->create(['branch_id' => $jkt->id, 'name' => ['en' => 'Test'], 'base_pax' => 2, 'is_template' => false]);

        $this->assertFalse($cs->can('update', $package));
    }
}
