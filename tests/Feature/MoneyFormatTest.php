<?php

namespace Tests\Feature;

use App\Support\Localization\Money;
use Tests\TestCase;

class MoneyFormatTest extends TestCase
{
    public function test_formats_idr_for_indonesian_locale(): void
    {
        $formatted = Money::format(150000, 'IDR', 'id');

        $this->assertStringContainsString('Rp', $formatted);
        $this->assertStringContainsString('150.000', $formatted);
    }

    public function test_formats_usd_for_english_locale(): void
    {
        $formatted = Money::format(99.5, 'USD', 'en');

        $this->assertStringContainsString('$', $formatted);
        $this->assertStringContainsString('99.50', $formatted);
    }

    public function test_formats_sar_for_arabic_locale(): void
    {
        $formatted = Money::format(200, 'SAR', 'ar');

        // Arabic (ar_SA) formats with Arabic-Indic digits and the SAR
        // symbol (ر.س.) — assert on the ICU currency symbol, not on
        // Western digits which correctly don't appear here.
        $this->assertStringContainsString('ر.س', $formatted);
    }
}
