<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\URL;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every route lives under {locale} now (M2). Without this, route()
     * calls in tests that never visited a locale-prefixed URL first throw
     * "missing required parameter: locale" — mirrors what SetLocale
     * middleware does for real requests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('id');
        URL::defaults(['locale' => 'id']);
    }
}
