<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Indogate') }} — Admin Panel</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root {
            --gold: #D4AF37;
            --gold-light: #F3E5AB;
            --sidebar-bg: #0a0f1a;
            --sidebar-hover: #111827;
            --content-bg: #0f172a;
        }
        body { font-family: 'Inter', sans-serif; }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            color: #94a3b8;
            transition: all 0.2s ease;
            text-decoration: none;
            margin-bottom: 2px;
        }
        .sidebar-link:hover {
            color: #f1f5f9;
            background: rgba(255,255,255,0.06);
        }
        .sidebar-link.active {
            color: #D4AF37;
            background: rgba(212, 175, 55, 0.1);
        }
        .sidebar-link.active svg {
            color: #D4AF37;
        }
        .sidebar-link svg {
            color: #64748b;
            transition: color 0.2s;
            flex-shrink: 0;
        }
        .sidebar-link:hover svg {
            color: #94a3b8;
        }

        /* Table styles */
        .admin-table thead tr {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }
        .admin-table thead th {
            padding: 12px 20px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }
        .admin-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s;
        }
        .admin-table tbody tr:hover {
            background: rgba(255,255,255,0.03);
        }
        .admin-table tbody td {
            padding: 14px 20px;
            font-size: 0.875rem;
            color: #cbd5e1;
        }
        .admin-table tbody td:first-child {
            color: #f1f5f9;
            font-weight: 500;
        }

        /* Form inputs */
        .admin-input {
            display: block;
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 10px 14px;
            color: #f1f5f9;
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .admin-input:focus {
            border-color: rgba(212, 175, 55, 0.5);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }
        .admin-input::placeholder { color: #475569; }
        .admin-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.04em;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Buttons */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #D4AF37 0%, #AA8011 100%);
            color: #0f172a;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #F3E5AB 0%, #D4AF37 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(212, 175, 55, 0.3);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.06);
            color: #94a3b8;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            color: #f1f5f9;
        }
        .btn-danger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(99, 102, 241, 0.1);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-edit:hover {
            background: rgba(99, 102, 241, 0.2);
            color: #c7d2fe;
        }
        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(20, 184, 166, 0.1);
            color: #5eead4;
            border: 1px solid rgba(20, 184, 166, 0.2);
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-view:hover {
            background: rgba(20, 184, 166, 0.2);
        }

        /* Card */
        .admin-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 16px;
            overflow: hidden;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(212, 175, 55, 0.4); }
    </style>
</head>
<body class="bg-slate-950 text-slate-300" style="background-color: #0a0f1a;">

<div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 shrink-0 flex flex-col border-r border-white/5 sticky top-0 h-screen overflow-y-auto" style="background: #07090f;">
        
        <!-- Logo -->
        <div class="flex items-center justify-center h-20 border-b border-white/5">
            <span class="text-xl font-bold tracking-widest text-white">INDO<span style="color: #D4AF37;">GATE</span></span>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1">
            <p class="px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Overview</p>
            <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                Dashboard
            </a>

            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Operations</p>
                
                @can('manage bookings')
                <a href="{{ route('admin.bookings.index') }}" class="sidebar-link {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                    Bookings
                </a>
                @endcan

                @can('verify payments')
                <a href="{{ route('admin.payments.index') }}" class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    Payments
                </a>
                @endcan
            </div>

            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Inventory</p>

                @can('manage hotels')
                <a href="{{ route('admin.hotels.index') }}" class="sidebar-link {{ request()->routeIs('admin.hotels.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    Hotels
                </a>
                @endcan

                @can('manage flights')
                <a href="{{ route('admin.flights.index') }}" class="sidebar-link {{ request()->routeIs('admin.flights.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"></path></svg>
                    Flights
                </a>
                @endcan

                @can('manage vehicles')
                <a href="{{ route('admin.drivers.index') }}" class="sidebar-link {{ request()->routeIs('admin.drivers.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    Drivers
                </a>
                @endcan
            </div>

            @role('Super Admin')
            <div class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-slate-600 uppercase tracking-widest mb-1">Configuration</p>
                <a href="{{ route('admin.pricing.index') }}" class="sidebar-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    Dynamic Pricing
                </a>
            </div>
            @endrole
        </nav>

        <!-- User info at bottom -->
        <div class="p-4 border-t border-white/5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-sm" style="background: rgba(212,175,55,0.15); color: #D4AF37;">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-slate-500 truncate">{{ auth()->user()->getRoleNames()->first() }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="sidebar-link w-full hover:text-red-400 hover:bg-red-500/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top bar -->
        <header class="h-16 flex items-center justify-between px-8 border-b border-white/5 sticky top-0 z-30" style="background: rgba(10,15,26,0.9); backdrop-filter: blur(12px);">
            <div>
                <h1 class="text-lg font-semibold text-white">{{ $header ?? 'Dashboard' }}</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('search.index') }}" target="_blank" class="text-xs text-slate-500 hover:text-slate-300 transition-colors flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    View Site
                </a>
                <div class="w-px h-4 bg-white/10"></div>
                <span class="text-xs font-medium px-3 py-1.5 rounded-full" style="background: rgba(212,175,55,0.1); color: #D4AF37;">
                    {{ auth()->user()->getRoleNames()->first() ?? 'Admin' }}
                </span>
            </div>
        </header>

        <!-- Slot -->
        <main class="flex-1 p-8">
            
            @if(session()->has('success'))
                <div class="mb-6 flex items-center gap-3 px-5 py-4 rounded-xl border" style="background: rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.2); color: #6ee7b7;">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span class="text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif
            
            @if(session()->has('error'))
                <div class="mb-6 flex items-center gap-3 px-5 py-4 rounded-xl border" style="background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.2); color: #fca5a5;">
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
