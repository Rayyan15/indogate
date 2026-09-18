<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('lead.public.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-neutral-50 text-neutral-900">
    <div class="mx-auto max-w-md px-4 py-10">
        <h1 class="font-display text-xl font-medium">{{ __('lead.public.title') }}</h1>

        @if(session('status'))
            <p class="mt-4 rounded border border-success/20 bg-success/10 p-3 text-sm text-success">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('leads.public-store') }}" class="mt-6 space-y-4">
            @csrf
            {{-- Honeypot: real visitors never see or fill this. Inline style,
                 not a Tailwind class — guaranteed to render regardless of
                 whether this exact utility survived the CSS build/purge. --}}
            <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;top:-9999px;">

            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.name') }}</label>
                <input type="text" name="name" required class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.list.phone') }}</label>
                <input type="text" name="phone" required class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-neutral-600">{{ __('lead.form.country') }}</label>
                <input type="text" name="country" class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full rounded bg-neutral-900 px-4 py-3 text-sm font-medium text-neutral-0">{{ __('lead.public.submit') }}</button>
        </form>
    </div>
</body>
</html>
