<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    /**
     * The page's own path, captured at mount() (the initial page render).
     * Reading request()->path() inside switchTo() instead would return
     * the Livewire AJAX endpoint's own path ("livewire/update"), not the
     * page the button lives on — verified empirically (it produced a
     * runaway "/id/id/id/.../ar" redirect). See docs/progress/M2-*.md.
     */
    public string $currentPath = '';

    public function mount(): void
    {
        $this->currentPath = request()->path();
    }

    public function switchTo(string $locale): void
    {
        $supported = array_keys(config('laravellocalization.supportedLocales'));

        if (! in_array($locale, $supported, true)) {
            return;
        }

        $segments = array_values(array_filter(explode('/', $this->currentPath)));

        if (isset($segments[0]) && in_array($segments[0], $supported, true)) {
            array_shift($segments);
        }

        $this->redirect('/'.$locale.($segments ? '/'.implode('/', $segments) : ''));
    }

    public function render(): View
    {
        return view('livewire.locale-switcher', [
            'locales' => config('laravellocalization.supportedLocales'),
            'current' => app()->getLocale(),
        ]);
    }
}
