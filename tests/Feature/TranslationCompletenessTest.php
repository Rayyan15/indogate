<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TranslationCompletenessTest extends TestCase
{
    public function test_lang_missing_command_reports_no_gaps(): void
    {
        $exitCode = Artisan::call('lang:missing');

        $this->assertSame(0, $exitCode, Artisan::output());
    }
}
