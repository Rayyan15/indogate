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
    <body class="font-body text-neutral-700 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-neutral-50 px-4 py-10">
            <a href="/" class="mb-8 text-xl font-bold tracking-widest text-neutral-900">INDO<span class="text-red-600">GATE</span></a>

            <div class="w-full sm:max-w-md">
                <div class="rounded border border-neutral-200 bg-neutral-0 p-8 shadow-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
