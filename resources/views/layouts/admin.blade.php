<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Localization\Direction::current() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Indogate') }} — {{ __('admin.panel_title') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:300,400,400i,500|jetbrains-mono:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-neutral-50 text-neutral-700 font-body antialiased">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 shrink-0 flex flex-col border-e border-neutral-200 sticky top-0 h-screen overflow-y-auto bg-neutral-0">

        <!-- Logo -->
        <div class="flex items-center justify-center h-20 border-b border-neutral-200">
            <span class="text-xl font-bold tracking-widest text-neutral-900">INDO<span class="text-red-600">GATE</span></span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1">
            <p class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-widest mb-1">{{ __('nav.overview') }}</p>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                {{ __('nav.dashboard') }}
            </a>

            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-widest mb-1">{{ __('nav.operations') }}</p>

                @can('booking.manage')
                <a href="{{ route('admin.bookings.index') }}" class="sidebar-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    {{ __('nav.bookings') }}
                </a>
                @endcan

                @can('payment.verify')
                <a href="{{ route('admin.payments.index') }}" class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    {{ __('nav.payments') }}
                </a>
                @endcan
            </div>

            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-widest mb-1">{{ __('nav.inventory') }}</p>

                @can('catalog.manage')
                <a href="{{ route('admin.hotels.index') }}" class="sidebar-link {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    {{ __('nav.hotels') }}
                </a>

                <a href="{{ route('admin.flights.index') }}" class="sidebar-link {{ request()->routeIs('admin.flights.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path></svg>
                    {{ __('nav.flights') }}
                </a>

                <a href="{{ route('admin.drivers.index') }}" class="sidebar-link {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    {{ __('nav.drivers') }}
                </a>
                @endcan
            </div>

            @can('catalog.manage')
            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-widest mb-1">{{ __('nav.catalog') }}</p>
                <a href="{{ route('admin.catalog.partners.index') }}" class="sidebar-link {{ request()->routeIs('admin.catalog.partners.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0"></path></svg>
                    {{ __('nav.partners') }}
                </a>
                <a href="{{ route('admin.catalog.inventory-items.index') }}" class="sidebar-link {{ request()->routeIs('admin.catalog.inventory-items.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    {{ __('nav.inventory_items') }}
                </a>
            </div>
            @endcan

            @can('pricing.manage')
            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-widest mb-1">{{ __('nav.configuration') }}</p>
                <a href="{{ route('admin.pricing.index') }}" class="sidebar-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    {{ __('nav.pricing') }}
                </a>
            </div>
            @endcan

            @can('user.manage')
            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-neutral-400 uppercase tracking-widest mb-1">{{ __('nav.administration') }}</p>
                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0"></path></svg>
                    {{ __('nav.users') }}
                </a>
            </div>
            @endcan
        </nav>

        <!-- User info at bottom -->
        <div class="p-4 border-t border-neutral-200">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm bg-red-50 text-red-700">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-neutral-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-neutral-500 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar-link w-full hover:text-danger hover:bg-danger/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    {{ __('nav.sign_out') }}
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="h-16 flex items-center justify-between px-8 border-b border-neutral-200 sticky top-0 z-30 bg-neutral-0/90 backdrop-blur">
            <div>
                <span class="text-xs font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ $header ?? __('nav.dashboard') }}</span>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('search.index') }}" target="_blank" class="text-xs text-neutral-500 hover:text-neutral-800 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    {{ __('nav.view_site') }}
                </a>
                <div class="w-px h-4 bg-neutral-200"></div>
                @livewire('locale-switcher')
                <div class="w-px h-4 bg-neutral-200"></div>
                @can('branch.switch')
                    @livewire('admin.branch-switcher')
                    <div class="w-px h-4 bg-neutral-200"></div>
                @endcan
                <span class="text-xs font-medium px-3 py-1.5 rounded-full bg-red-50 text-red-700">
                    {{ auth()->user()->getRoleNames()->first() ?? __('nav.admin') }}
                </span>
            </div>
        </header>

        <!-- Slot -->
        <main class="flex-1 p-8">

            @if(session()->has('success'))
                <div class="mb-6 flex items-center gap-3 px-5 py-4 rounded border bg-success/10 border-success/20 text-success">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="mb-6 flex items-center gap-3 px-5 py-4 rounded border bg-danger/10 border-danger/20 text-danger">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
