<?php

namespace Tests\Feature;

use Tests\TestCase;

class RtlDirectionTest extends TestCase
{
    public function test_arabic_locale_renders_rtl(): void
    {
        $this->get('/ar/login')->assertSee('dir="rtl"', false);
    }

    public function test_indonesian_and_english_render_ltr(): void
    {
        $this->get('/id/login')->assertDontSee('dir="rtl"', false);
        $this->get('/en/login')->assertDontSee('dir="rtl"', false);
    }
}
