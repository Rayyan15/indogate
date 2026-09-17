<?php

namespace Tests\Feature;

use App\Livewire\LocaleSwitcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LocaleSwitcherTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: switchTo() must redirect to /{newLocale}/<rest-of-path>,
     * not stack locale segments. Found via manual browser testing — the
     * component originally read request()->path() inside the action,
     * which resolves to the Livewire AJAX endpoint's own path rather
     * than the page the switcher is mounted on.
     */
    public function test_switching_locale_preserves_path_without_stacking(): void
    {
        Livewire::test(LocaleSwitcher::class)
            ->set('currentPath', 'id/admin/dashboard')
            ->call('switchTo', 'ar')
            ->assertRedirect('/ar/admin/dashboard');
    }

    public function test_unsupported_locale_is_ignored(): void
    {
        Livewire::test(LocaleSwitcher::class)
            ->set('currentPath', 'id/admin/dashboard')
            ->call('switchTo', 'fr')
            ->assertNoRedirect();
    }
}
