<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Localization\Direction::current() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? __('payment.title') }} — {{ config('app.name', 'Indogate') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:400,500|jetbrains-mono:400,500,600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-neutral-50 font-body text-neutral-700 antialiased">
    <header class="border-b border-neutral-200 bg-neutral-0">
        <div class="mx-auto flex h-14 max-w-xl items-center justify-between px-4">
            <span class="text-lg font-bold tracking-widest text-neutral-900">INDO<span class="text-red-600">GATE</span></span>
            <span class="flex items-center gap-1.5 text-xs text-neutral-500">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                {{ __('payment.secure') }}
            </span>
        </div>
    </header>

    <main class="mx-auto max-w-xl px-4 py-6 sm:py-10">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-xl px-4 pb-8 text-center text-xs text-neutral-400">
        © {{ date('Y') }} PT Indo International Gate
    </footer>
    {{-- Brings Alpine (countdown, copy buttons); Livewire owns the single Alpine instance. --}}
    @livewireScripts
</body>
</html>
