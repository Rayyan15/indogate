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
@php
    // Branch-scoped automatically via Payment's BelongsToBranch global scope.
    $pendingPaymentsCount = auth()->user()?->can('payment.verify')
        ? \App\Models\Payment::where('status', 'pending')->count()
        : 0;
@endphp
<body class="bg-neutral-50 text-neutral-700 font-body antialiased">

<div class="flex min-h-screen" x-data="{ mobileOpen: false, collapsed: localStorage.getItem('sidebar-collapsed') === 'true' }" x-init="$watch('collapsed', v => localStorage.setItem('sidebar-collapsed', v))">

    <!-- Mobile backdrop -->
    <div x-show="mobileOpen" x-cloak x-transition.opacity @click="mobileOpen = false"
         class="fixed inset-0 z-40 bg-neutral-900/40 lg:hidden"></div>

    <!-- Sidebar -->
    <aside
        class="fixed inset-y-0 start-0 z-50 w-64 shrink-0 flex flex-col border-e border-neutral-200 h-screen overflow-y-auto overflow-x-hidden bg-neutral-0 transition-all duration-200 lg:sticky lg:top-0 lg:translate-x-0 rtl:lg:translate-x-0"
        :class="[mobileOpen ? 'translate-x-0' : '-translate-x-full rtl:translate-x-full lg:!translate-x-0', collapsed ? 'lg:!w-20' : 'lg:!w-64']"
        :data-collapsed="collapsed"
    >

        <!-- Logo -->
        <div class="flex h-20 shrink-0 items-center justify-between border-b border-neutral-200 px-5">
            <span class="text-xl font-bold tracking-widest text-neutral-900" x-show="!collapsed">INDO<span class="text-red-600">GATE</span></span>
            <span class="text-xl font-bold text-red-600" x-show="collapsed" x-cloak>IG</span>
            <button @click="mobileOpen = false" class="rounded p-1 text-neutral-400 hover:bg-neutral-100 lg:hidden">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <button @click="collapsed = !collapsed" class="hidden rounded p-1 text-neutral-400 hover:bg-neutral-100 lg:block" :title="collapsed ? '{{ __('nav.expand_sidebar') }}' : '{{ __('nav.collapse_sidebar') }}'">
                <svg class="h-4 w-4 transition-transform" :class="collapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 p-4">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.dashboard') }}</span>
            </a>

            @canany(['report.margin.view', 'lead.manage', 'booking.manage', 'payment.verify'])
            <a href="{{ route('admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.reports') }}</span>
            </a>
            @endcanany

            @canany(['booking.manage', 'payment.verify'])
            <x-ui.nav-group key="operations" :label="__('nav.operations')" :active="request()->routeIs(['admin.bookings.*', 'admin.payments.*'])">
                @can('booking.manage')
                <a href="{{ route('admin.bookings.index') }}" class="sidebar-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.bookings') }}</span>
                </a>
                @endcan

                @can('payment.verify')
                <a href="{{ route('admin.payments.index') }}" class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.payments') }}</span>
                    @if($pendingPaymentsCount ?? 0)
                        <span class="sidebar-badge">{{ $pendingPaymentsCount }}</span>
                    @endif
                </a>
                @endcan
            </x-ui.nav-group>
            @endcanany

            @can('catalog.manage')
            <x-ui.nav-group key="inventory" :label="__('nav.inventory')" :active="request()->routeIs(['admin.hotels.*', 'admin.flights.*', 'admin.drivers.*', 'admin.catalog.*'])">
                <a href="{{ route('admin.hotels.index') }}" class="sidebar-link {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.hotels') }}</span>
                </a>

                <a href="{{ route('admin.flights.index') }}" class="sidebar-link {{ request()->routeIs('admin.flights.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.flights') }}</span>
                </a>

                <a href="{{ route('admin.drivers.index') }}" class="sidebar-link {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.drivers') }}</span>
                </a>

                <a href="{{ route('admin.catalog.partners.index') }}" class="sidebar-link {{ request()->routeIs('admin.catalog.partners.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.partners') }}</span>
                </a>
                <a href="{{ route('admin.catalog.inventory-items.index') }}" class="sidebar-link {{ request()->routeIs('admin.catalog.inventory-items.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.inventory_items') }}</span>
                </a>
                <a href="{{ route('admin.packages.index') }}" class="sidebar-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 12l-8 4-8-4m16 0l-8-4-8 4m16 0v6l-8 4-8-4v-6"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.packages') }}</span>
                </a>
            </x-ui.nav-group>
            @endcan

            @can('booking.manage')
            <x-ui.nav-group key="package-bookings" :label="__('nav.package_bookings')" :active="request()->routeIs('admin.package-bookings.*')">
                <a href="{{ route('admin.package-bookings.index') }}" class="sidebar-link {{ request()->routeIs('admin.package-bookings.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.package_bookings') }}</span>
                </a>
            </x-ui.nav-group>
            @endcan

            @can('driver.assign')
            <x-ui.nav-group key="fleet" :label="__('nav.fleet')" :active="request()->routeIs('admin.fleet.*')">
                <a href="{{ route('admin.fleet.calendar') }}" class="sidebar-link {{ request()->routeIs('admin.fleet.calendar') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.fleet_calendar') }}</span>
                </a>
                <a href="{{ route('admin.fleet.drivers') }}" class="sidebar-link {{ request()->routeIs('admin.fleet.drivers') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.drivers') }}</span>
                </a>
                <a href="{{ route('admin.fleet.vehicles') }}" class="sidebar-link {{ request()->routeIs('admin.fleet.vehicles') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.vehicles') }}</span>
                </a>
            </x-ui.nav-group>
            @endcan

            @if(auth()->user()->can('payment.verify') || auth()->user()->can('report.margin.view'))
            <x-ui.nav-group key="finance" :label="__('nav.finance')" :active="request()->routeIs('admin.finance.*')">
                @can('payment.verify')
                <a href="{{ route('admin.finance.payments') }}" class="sidebar-link {{ request()->routeIs('admin.finance.payments') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.payments') }}</span>
                </a>
                @endcan
                <a href="{{ route('admin.finance.receivables') }}" class="sidebar-link {{ request()->routeIs('admin.finance.receivables') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.receivables') }}</span>
                </a>
                @can('payment.verify')
                <a href="{{ route('admin.finance.vendor-payments') }}" class="sidebar-link {{ request()->routeIs('admin.finance.vendor-payments') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.vendor_payments') }}</span>
                </a>
                @endcan
                @can('report.margin.view')
                <a href="{{ route('admin.finance.margin-report') }}" class="sidebar-link {{ request()->routeIs('admin.finance.margin-report') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.margin_report') }}</span>
                </a>
                @endcan
            </x-ui.nav-group>
            @endif

            @can('lead.manage')
            <x-ui.nav-group key="leads" :label="__('nav.leads')" :active="request()->routeIs('admin.leads.*')">
                <a href="{{ route('admin.leads.index') }}" class="sidebar-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.leads') }}</span>
                </a>
            </x-ui.nav-group>
            @endcan

            @can('pricing.manage')
            <x-ui.nav-group key="configuration" :label="__('nav.configuration')" :active="request()->routeIs('admin.pricing.*') || request()->routeIs('admin.pricing-engine.*')">
                <a href="{{ route('admin.pricing.index') }}" class="sidebar-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.pricing') }}</span>
                </a>
                <a href="{{ route('admin.pricing-engine.currencies') }}" class="sidebar-link {{ request()->routeIs('admin.pricing-engine.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .672-3 1.5S10.343 11 12 11s3 .672 3 1.5-1.343 1.5-3 1.5m0-6c1.11 0 2.08.402 2.599 1M12 8V6.5M12 8v.01M12 16v1.5m0-1.5c-1.11 0-2.08-.402-2.599-1M12 22a10 10 0 100-20 10 10 0 000 20z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.pricing_engine') }}</span>
                </a>
            </x-ui.nav-group>
            @endcan

            @canany(['user.manage', 'activitylog.view'])
            <x-ui.nav-group key="administration" :label="__('nav.administration')" :active="request()->routeIs('admin.users.*') || request()->routeIs('admin.security.*')">
                @can('user.manage')
                <a href="{{ route('admin.users.index') }}" class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.users') }}</span>
                </a>
                @endcan
                @can('activitylog.view')
                <a href="{{ route('admin.security.audit-logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.security.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.audit_logs') }}</span>
                </a>
                @endcan
            </x-ui.nav-group>
            @endcanany
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
                    <span class="nav-label" x-show="!collapsed" x-transition.opacity.duration.100ms>{{ __('nav.sign_out') }}</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
        @php
            // Best-effort icon per section, derived from the route name so
            // individual pages don't each have to pass one. Falls back to a
            // generic document icon.
            $routeName = request()->route()?->getName() ?? '';
            $headerIconPath = match (true) {
                str_starts_with($routeName, 'admin.bookings')                => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                str_starts_with($routeName, 'admin.payments')                => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                str_starts_with($routeName, 'admin.hotels')                  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                str_starts_with($routeName, 'admin.flights')                 => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064',
                str_starts_with($routeName, 'admin.drivers')                 => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                str_starts_with($routeName, 'admin.catalog.partners')        => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0',
                str_starts_with($routeName, 'admin.catalog.inventory-items') => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                str_starts_with($routeName, 'admin.pricing')                 => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                str_starts_with($routeName, 'admin.users')                   => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 0a4 4 0 10-8 0',
                str_starts_with($routeName, 'admin.dashboard')               => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                str_starts_with($routeName, 'admin.reports')                 => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                default                                                       => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            };
        @endphp
        <!-- Top bar -->
        <header class="flex h-16 items-center justify-between gap-3 border-b border-neutral-200 bg-neutral-0/90 px-4 backdrop-blur sm:px-8 sticky top-0 z-30">
            <div class="flex min-w-0 items-center gap-3">
                <button @click="mobileOpen = true" class="rounded p-1.5 text-neutral-500 hover:bg-neutral-100 lg:hidden">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <span class="hidden shrink-0 items-center justify-center rounded bg-red-50 p-1.5 text-red-600 sm:flex">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $headerIconPath }}"></path></svg>
                </span>
                <span class="truncate text-sm font-bold uppercase tracking-[0.1em] text-neutral-900">{{ $header ?? __('nav.dashboard') }}</span>
            </div>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                <a href="{{ route('search.index') }}" target="_blank" class="hidden items-center gap-1.5 text-xs text-neutral-500 transition-colors hover:text-neutral-800 md:flex">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span>{{ __('nav.view_site') }}</span>
                </a>
                <div class="hidden h-4 w-px bg-neutral-200 md:block"></div>
                @livewire('locale-switcher')
                <div class="hidden h-4 w-px bg-neutral-200 sm:block"></div>
                @can('branch.switch')
                    @livewire('admin.branch-switcher')
                    <div class="hidden h-4 w-px bg-neutral-200 sm:block"></div>
                @endcan
                <span class="hidden whitespace-nowrap rounded-full bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 sm:inline">
                    {{ auth()->user()->getRoleNames()->first() ?? __('nav.admin') }}
                </span>
            </div>
        </header>

        <!-- Slot -->
        <main class="flex-1 overflow-x-hidden p-4 sm:p-6 lg:p-8">

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
