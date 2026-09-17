{{-- Collapsible sidebar section. Open state remembered per-group in localStorage; auto-open when a child route is active.
     When the whole sidebar is icon-only ("collapsed" from the parent scope), the group header hides and children
     always show flat, ignoring the per-group toggle. --}}
@props(['label', 'key', 'active' => false])
<div x-data="{ open: localStorage.getItem('nav-open-{{ $key }}') !== null ? localStorage.getItem('nav-open-{{ $key }}') === 'true' : {{ $active ? 'true' : 'false' }} }"
     x-init="$watch('open', v => localStorage.setItem('nav-open-{{ $key }}', v))"
     class="pt-2">
    <button type="button" @click="open = !open" x-show="!collapsed" x-cloak
        class="flex w-full items-center justify-between px-4 py-2 text-xs font-semibold uppercase tracking-widest text-neutral-400 transition-colors hover:text-neutral-700">
        <span>{{ $label }}</span>
        <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-150" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>
    <div x-show="collapsed || open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-0.5">
        {{ $slot }}
    </div>
</div>
