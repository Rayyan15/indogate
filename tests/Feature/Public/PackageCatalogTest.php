<?php

namespace Tests\Feature\Public;

use App\Domain\Packaging\Models\Package;
use App\Livewire\Public\PackageCatalog;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackageCatalogTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::create([
            'id' => 1,
            'name' => 'Bali HQ',
            'code' => 'DPS',
            'is_active' => true,
        ]);
    }

    public function test_catalog_shows_only_published_packages(): void
    {
        $published = Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Published Luxury Tour', 'id' => 'Tur Mewah Terbit', 'ar' => 'جولة فاخرة'],
            'description' => ['en' => 'Desc', 'id' => 'Desc', 'ar' => 'Desc'],
            'duration_days' => 4,
            'starting_price_idr' => 20000000,
            'is_published' => true,
        ]);

        $unpublished = Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Draft Secret Tour', 'id' => 'Tur Rahasia Draf', 'ar' => 'جولة سرية'],
            'description' => ['en' => 'Desc', 'id' => 'Desc', 'ar' => 'Desc'],
            'duration_days' => 2,
            'starting_price_idr' => 10000000,
            'is_published' => false,
        ]);

        $response = $this->get('/en/packages');
        $response->assertStatus(200);
        $response->assertSee('Published Luxury Tour');
        $response->assertDontSee('Draft Secret Tour');
    }

    public function test_unpublished_package_cannot_be_accessed_directly_and_returns_404(): void
    {
        $unpublished = Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Draft Tour', 'id' => 'Tur Draf', 'ar' => 'جولة دmock'],
            'description' => ['en' => 'Desc', 'id' => 'Desc', 'ar' => 'Desc'],
            'duration_days' => 3,
            'starting_price_idr' => 10000000,
            'is_published' => false,
        ]);

        $response = $this->get("/en/packages/{$unpublished->id}");
        $response->assertStatus(404);
    }

    public function test_published_package_detail_returns_200(): void
    {
        $published = Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Komodo Odyssey', 'id' => 'Ekspedisi Komodo', 'ar' => 'كومودو'],
            'description' => ['en' => 'Explore Komodo island', 'id' => 'Jelajah pulau Komodo', 'ar' => 'استكشف الجزيرة'],
            'duration_days' => 3,
            'base_pax' => 4,
            'starting_price_idr' => 25000000,
            'highlights' => ['Private Speedboat', 'Halal Seafood BBQ'],
            'is_published' => true,
        ]);

        $response = $this->get("/en/packages/{$published->id}");
        $response->assertStatus(200);
        $response->assertSee('Komodo Odyssey');
        $response->assertSee('Private Speedboat');
        $response->assertSee('Halal Seafood BBQ');
    }

    public function test_catalog_livewire_filter_by_search_and_duration(): void
    {
        Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Short Ubud Getaway', 'id' => 'Liburan Singkat Ubud', 'ar' => 'أوبود القصيرة'],
            'description' => ['en' => 'Tranquil escape', 'id' => 'Liburan tenang', 'ar' => 'راحة'],
            'duration_days' => 2,
            'starting_price_idr' => 5000000,
            'is_published' => true,
        ]);

        Package::create([
            'branch_id' => $this->branch->id,
            'name' => ['en' => 'Grand Bali Expedition', 'id' => 'Ekspedisi Akbar Bali', 'ar' => 'رحلة بالي الكبرى'],
            'description' => ['en' => 'Full island tour', 'id' => 'Keliling pulau', 'ar' => 'جولة شاملة'],
            'duration_days' => 10,
            'starting_price_idr' => 30000000,
            'is_published' => true,
        ]);

        // Test search
        app()->setLocale('en');
        Livewire::test(PackageCatalog::class)
            ->set('search', 'Ubud')
            ->assertSee('Short Ubud Getaway')
            ->assertDontSee('Grand Bali Expedition');

        // Test duration filter: 8+ days
        Livewire::test(PackageCatalog::class)
            ->set('duration', '8+')
            ->assertSee('Grand Bali Expedition')
            ->assertDontSee('Short Ubud Getaway');
    }
}
