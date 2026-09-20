<?php

namespace Tests\Feature\Public;

use App\Domain\Packaging\Models\Package;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Branch::create([
            'id' => 1,
            'name' => 'Bali HQ',
            'code' => 'DPS',
            'is_active' => true,
        ]);
    }

    public function test_landing_page_responds_with_200_across_supported_locales(): void
    {
        foreach (['id', 'en', 'ar'] as $locale) {
            $response = $this->get("/{$locale}");
            $response->assertStatus(200);
            $response->assertSee('INDO');
            $response->assertSee('GATE');
        }
    }

    public function test_arabic_locale_has_rtl_direction_and_cairo_font(): void
    {
        $response = $this->get('/ar');
        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('Cairo');
    }

    public function test_landing_page_displays_featured_packages(): void
    {
        $package = Package::create([
            'branch_id' => 1,
            'name' => ['id' => 'Paket Bulan Madu Bali', 'en' => 'Bali Honeymoon', 'ar' => 'شهر العسل في بالي'],
            'description' => ['id' => 'Deskripsi paket', 'en' => 'Package description', 'ar' => 'وصف الباقة'],
            'duration_days' => 5,
            'base_pax' => 2,
            'starting_price_idr' => 15000000,
            'is_published' => true,
            'is_featured' => true,
        ]);

        $response = $this->get('/id');
        $response->assertStatus(200);
        $response->assertSee('Paket Bulan Madu Bali');

        $responseEn = $this->get('/en');
        $responseEn->assertStatus(200);
        $responseEn->assertSee('Bali Honeymoon');
    }

    public function test_whatsapp_consultation_button_exists(): void
    {
        $response = $this->get('/en');
        $response->assertStatus(200);
        $response->assertSee('https://wa.me/', false);
    }
}
