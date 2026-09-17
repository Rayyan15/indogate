<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_prefix_sets_application_locale(): void
    {
        $this->get('/id/login')->assertOk();
        $this->assertSame('id', app()->getLocale());

        $this->get('/en/login')->assertOk();
        $this->assertSame('en', app()->getLocale());

        $this->get('/ar/login')->assertOk();
        $this->assertSame('ar', app()->getLocale());
    }

    public function test_url_without_locale_prefix_redirects_preserving_path(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect();
        $this->assertMatchesRegularExpression('#^https?://[^/]+/(id|en|ar)/login$#', $response->headers->get('Location'));
    }

    /**
     * PRD M1 test table: "locale tak dikenal dialihkan" — an unsupported
     * locale segment redirects into a supported one, it does not 404.
     */
    public function test_unsupported_locale_segment_redirects_to_a_supported_locale(): void
    {
        $response = $this->get('/fr/login');

        $response->assertRedirect();
        $this->assertMatchesRegularExpression('#^https?://[^/]+/(id|en|ar)/login$#', $response->headers->get('Location'));
    }
}
