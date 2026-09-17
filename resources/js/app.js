import './bootstrap';

// Livewire 4 bundles and starts its own Alpine instance via @livewireScripts.
// Starting a second one here (as this file used to) makes two Alpine
// runtimes fight over the same DOM, breaking directives like wire:submit —
// forms silently fall back to a native GET submit instead of the Livewire
// AJAX request. Let Livewire own Alpine; don't import/start it here.
