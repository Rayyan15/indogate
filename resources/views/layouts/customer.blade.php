<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Localization\Direction::current() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Indogate') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:300,400,400i,500|jetbrains-mono:400,500,600&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-neutral-50 font-body text-neutral-700 antialiased selection:bg-red-100 selection:text-red-900">

        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-neutral-200 bg-neutral-0/90 backdrop-blur">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-10">
                    <a href="{{ route('search.index') }}" class="text-xl font-bold tracking-widest text-neutral-900">INDO<span class="text-red-600">GATE</span></a>
                    <div class="hidden items-center gap-8 md:flex">
                        <a href="{{ route('search.index') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('search.*') ? 'text-red-700' : 'text-neutral-600 hover:text-neutral-900' }}">Discover</a>
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium transition-colors {{ request()->routeIs('dashboard') || request()->routeIs('customer.bookings.*') ? 'text-red-700' : 'text-neutral-600 hover:text-neutral-900' }}">My Bookings</a>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <a href="{{ route('cart.index') }}" class="relative p-2 text-neutral-500 transition-colors hover:text-neutral-900">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                        @if(session()->has('cart') && count(session('cart')) > 0)
                            <span class="absolute -end-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold leading-none text-neutral-0">{{ count(session('cart')) }}</span>
                        @endif
                    </a>

                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center gap-1.5 text-sm font-medium text-neutral-700 transition-colors hover:text-neutral-900 focus:outline-none">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-3.5 w-3.5 text-neutral-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                        </button>

                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="absolute end-0 z-50 mt-2 w-48 rounded border border-neutral-200 bg-neutral-0 py-1 shadow-md" style="display: none;">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-neutral-700 hover:bg-neutral-50">Profile</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full px-4 py-2 text-start text-sm text-danger hover:bg-danger/5">Sign out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex flex-1 flex-col">
            @isset($header)
                <div class="border-b border-neutral-200 bg-neutral-0 py-8">
                    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <div class="flex-grow">
                {{ $slot }}
            </div>

            <footer class="mt-12 border-t border-neutral-200 py-8">
                <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                    <p class="text-xs text-neutral-400">&copy; {{ date('Y') }} Indogate. All rights reserved.</p>
                </div>
            </footer>
        </main>

        @if (session()->has('success') || session()->has('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="fixed bottom-4 end-4 z-50 space-y-3">
                @if (session()->has('success'))
                    <div class="flex items-center gap-3 rounded border border-success/20 bg-neutral-0 px-5 py-4 text-success shadow-md">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="flex items-center gap-3 rounded border border-danger/20 bg-neutral-0 px-5 py-4 text-danger shadow-md">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                @endif
            </div>
        @endif

    </body>
</html>
